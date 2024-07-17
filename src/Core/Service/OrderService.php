<?php

declare(strict_types=1);

namespace Twint\Core\Service;

use Exception;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Twint\Core\Setting\Settings;
use Twint\Sdk\Value\FiledMerchantTransactionReference;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\NumericPairingToken;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\OrderId;
use Twint\Sdk\Value\OrderStatus;
use Twint\Sdk\Value\PairingStatus;
use Twint\Sdk\Value\QrCode;
use Twint\Sdk\Value\TransactionStatus;
use Twint\Util\Method\RegularPaymentMethod;
use Twint\Util\OrderCustomFieldInstaller;
use function Psl\Type\non_empty_string;
use function Psl\Type\uint;

class OrderService
{
    private Context $context;

    public function __construct(
        private readonly EntityRepository $orderRepository,
        private readonly EntityRepository $stateMachineRepository,
        private readonly EntityRepository $stateMachineStateRepository
    ) {
        $this->context = new Context(new SystemSource());
    }

    public function updateOrderCustomField(string $orderId, array $customFields): void
    {
        $this->orderRepository->update([[
            'id' => $orderId,
            'customFields' => $customFields,
        ]], $this->context);
    }

    public function getPendingOrders(): EntityCollection
    {
        $onlyPickOrderFromMinutes = Settings::ONLY_PICK_ORDERS_FROM_MINUTES;
        $paymentInProgressStateId = $this->getPaymentInProgressStateId();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('transactions.stateId', $paymentInProgressStateId));
        $criteria->addFilter(
            new EqualsAnyFilter('order.transactions.paymentMethod.technicalName', [
                RegularPaymentMethod::TECHNICAL_NAME,
            ])
        );
        /** @var int $time */
        $time = strtotime("-{$onlyPickOrderFromMinutes} minutes");
        $time = date('Y-m-d H:i:s', $time);
        $criteria->addFilter(
            new MultiFilter(
                MultiFilter::CONNECTION_OR,
                [
                    new RangeFilter('createdAt', [
                        RangeFilter::GT => $time,
                    ]),
                    new RangeFilter('updatedAt', [
                        RangeFilter::GT => $time,
                    ]),
                ]
            )
        );
        $criteria->addAssociation('currency');
        $criteria->addAssociation('transactions.paymentMethod');
        $criteria->addAssociation('customFields');
        return $this->orderRepository->search($criteria, $this->context)
            ->getEntities();
    }

    public function getPaymentInProgressStateId(): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_MACHINE));
        $transactionState = $this->stateMachineRepository->search($criteria, $this->context)
            ->first();
        if (!empty($transactionState)) {
            $transactionStateId = $transactionState->get('id');
            $criteriaM = new Criteria();

            $criteriaM->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
                new EqualsFilter('technicalName', OrderTransactionStates::STATE_IN_PROGRESS),
                new EqualsFilter('stateMachineId', $transactionStateId),
            ]));
            $paymentInProgressState = $this->stateMachineStateRepository->search($criteriaM, $this->context)
                ->first();
            if (!empty($paymentInProgressState)) {
                return $paymentInProgressState->get('id');
            }
        }
        return '';
    }

    public function isTwintOrder(OrderEntity $order): bool
    {
        $currency = $order->getCurrency()?->getIsoCode();
        if (!$currency) {
            throw new Exception('Missing currency for this order:' . $order->getId() . PHP_EOL);
        }

        $referenceId = $order->getId();
        if ($order->getTransactions() && $order->getTransactions()->first()) {
            $referenceId = $order->getTransactions()
                ->first()
                ->getId();
        }

        $twintApiResponse = json_decode(
            $order->getCustomFields()[OrderCustomFieldInstaller::TWINT_API_RESPONSE_CUSTOM_FIELD] ?? '{}',
            true
        );
        if (empty($twintApiResponse) || empty($twintApiResponse['id'])) {
            throw PaymentException::asyncProcessInterrupted(
                $referenceId,
                'Missing TWINT response for this order:' . $order->getId() . PHP_EOL
            );
        }
        return true;
    }

    public function getTwintOrder(OrderEntity $order): ?Order
    {
        $twintApiResponse = json_decode(
            $order->getCustomFields()[OrderCustomFieldInstaller::TWINT_API_RESPONSE_CUSTOM_FIELD] ?? '{}',
            true
        );
        if (!empty($twintApiResponse) && !empty($twintApiResponse['id']) && !empty($twintApiResponse['merchantTransactionReference'])) {
            return new Order(
                OrderId::fromString($twintApiResponse['id']),
                // @phpstan-ignore-next-line
                new FiledMerchantTransactionReference((string) $twintApiResponse['merchantTransactionReference']),
                OrderStatus::fromString($twintApiResponse['status']),
                TransactionStatus::fromString($twintApiResponse['transactionStatus']),
                new Money($twintApiResponse['amount']['currency'], $twintApiResponse['amount']['amount']),
                PairingStatus::fromString($twintApiResponse['pairingStatus']),
                new NumericPairingToken(uint()->assert($twintApiResponse['pairingToken'] ?? 0)),
                null
            );
        }
        return null;
    }

    public function isOrderPaid(OrderEntity $order): bool
    {
        $transactions = $order->getTransactions();

        if ($transactions === null) {
            return false;
        }

        $transaction = $transactions->last();
        if ($transaction === null) {
            return false;
        }

        $stateMachineState = $transaction->getStateMachineState();
        if ($stateMachineState === null) {
            return false;
        }
        return $stateMachineState->getTechnicalName() === OrderTransactionStates::STATE_PAID;
    }

    public function isCancelPaid(OrderEntity $order): bool
    {
        $transactions = $order->getTransactions();

        if ($transactions === null) {
            return false;
        }

        $transaction = $transactions->last();
        if ($transaction === null) {
            return false;
        }

        $stateMachineState = $transaction->getStateMachineState();
        if ($stateMachineState === null) {
            return false;
        }

        return $stateMachineState->getTechnicalName() === OrderTransactionStates::STATE_CANCELLED;
    }

    /**
     * Return an order entity, enriched with associations.
     *
     * @throws Exception
     */
    public function getOrder(string $orderId, Context $context): OrderEntity
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('currency');
        $criteria->addAssociation('addresses');
        $criteria->addAssociation('shippingAddress');   # important for subscription creation
        $criteria->addAssociation('billingAddress');    # important for subscription creation
        $criteria->addAssociation('billingAddress.country');
        $criteria->addAssociation('orderCustomer');
        $criteria->addAssociation('orderCustomer.customer');
        $criteria->addAssociation('orderCustomer.salutation');
        $criteria->addAssociation('language');
        $criteria->addAssociation('language.locale');
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('lineItems.product.media');
        $criteria->addAssociation('deliveries.shippingOrderAddress');
        $criteria->addAssociation('deliveries.shippingOrderAddress.country');
        $criteria->addAssociation('deliveries.shippingMethod');
        $criteria->addAssociation('deliveries.positions.orderLineItem');
        $criteria->addAssociation('transactions.paymentMethod');
        $criteria->addAssociation('transactions.paymentMethod.appPaymentMethod.app');
        $criteria->addAssociation('transactions.stateMachineState');

        $order = $this->orderRepository->search($criteria, $context)
            ->first();

        if ($order instanceof OrderEntity) {
            return $order;
        }
        throw new Exception($orderId);
    }
}

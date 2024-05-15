<?php

declare(strict_types=1);

namespace Twint\Core\Service;

use Exception;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
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
use Twint\Core\Factory\ClientBuilder;
use Twint\Core\Setting\Settings;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\OrderId;
use Twint\Sdk\Value\OrderStatus;
use Twint\Sdk\Value\UnfiledMerchantTransactionReference;
use Twint\Sdk\Value\Uuid;
use Twint\Util\Method\RegularPaymentMethod;
use Twint\Util\OrderCustomFieldInstaller;
use function Psl\Type\string;

class PaymentService
{
    private Context $context;

    public function __construct(
        private readonly EntityRepository $orderRepository,
        private readonly EntityRepository $stateMachineRepository,
        private readonly EntityRepository $stateMachineStateRepository,
        private readonly OrderTransactionStateHandler $transactionStateHandler,
        private readonly ClientBuilder $clientBuilder
    ) {
        $this->context = new Context(new SystemSource());
    }

    public function createOrder(AsyncPaymentTransactionStruct $transaction): Order
    {
        $order = $transaction->getOrder();
        if (!$order instanceof OrderEntity || $order->getCurrency() === null) {
            throw PaymentException::asyncProcessInterrupted(
                $transaction->getOrderTransaction()
                    ->getId(),
                'Missing order or currency' . PHP_EOL
            );
        }
        $currency = $order->getCurrency()
            ->getIsoCode();
        $client = $this->clientBuilder->build($order->getSalesChannelId());
        try {
            /**var non-empty-string $orderNumber**/
            $orderNumber = empty($order->getOrderNumber()) ? $order->getId() : $order->getOrderNumber();
            if ($orderNumber === '') {
                throw PaymentException::asyncProcessInterrupted($orderNumber, 'Missing order number' . PHP_EOL);
            }
            /** @var Order * */
            return $client->startOrder(
                new UnfiledMerchantTransactionReference($orderNumber),
                new Money($currency, $order->getAmountTotal())
            );
        } catch (Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $transaction->getOrderTransaction()
                    ->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }
    }

    public function checkOrderStatus(OrderEntity $order): ?Order
    {
        try {
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
                    'Missing Twint response for this order:' . $order->getId() . PHP_EOL
                );
            }
            $client = $this->clientBuilder->build($order->getSalesChannelId());
            /** @var Order * */
            $twintOrder = $client->monitorOrder(new OrderId(new Uuid($twintApiResponse['id'])));

            $transactionId = $order->getTransactions()?->first()?->getId() ?? null;
            if ($transactionId === null) {
                throw new Exception('Missing transaction id for this order:' . $referenceId . PHP_EOL);
            }
            if ($twintOrder->status()->equals(OrderStatus::SUCCESS())) {
                $this->transactionStateHandler->paid($transactionId, $this->context);
                return $client->confirmOrder(
                    new OrderId(new Uuid($twintApiResponse['id'])),
                    new Money((string) $currency, $order->getAmountTotal())
                );
            } elseif ($twintOrder->status()->equals(OrderStatus::FAILURE())) {
                $this->transactionStateHandler->cancel($transactionId, $this->context);
            }
            return $twintOrder;
        } catch (Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $order->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }
    }

    public function reverseOrder(
        string $twintOrderId,
        string $orderTransactionId,
        string $currency,
        float $amount,
        string $salesChannelId
    ): ?Order {
        try {
            $client = $this->clientBuilder->build($salesChannelId);
            /** @var Order * */
            $twintOrder = $client->monitorOrder(new OrderId(new Uuid($twintOrderId)));
            if ($twintOrder->status()->equals(OrderStatus::SUCCESS())) {
                $twintOrder = $client->reverseOrder(
                    new UnfiledMerchantTransactionReference('R-' . $twintOrderId),
                    new OrderId(new Uuid($twintOrderId)),
                    new Money($currency, $amount)
                );
                $this->transactionStateHandler->refund($orderTransactionId, $this->context);
            }
            return $twintOrder;
        } catch (Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $orderTransactionId,
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }
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

    public function getPayLinks(string $token, string $salesChannelId): array
    {
        $payLinks = [];
        try {
            $client = $this->clientBuilder->build($salesChannelId);
            $device = $client->detectDevice(string()->assert($_SERVER['HTTP_USER_AGENT'] ?? ''));
            if ($device->isAndroid()) {
                $payLinks['android'] = 'intent://payment#Intent;action=ch.twint.action.TWINT_PAYMENT;scheme=twint;S.code =' . $token . ';S.startingOrigin=EXTERNAL_WEB_BROWSER;S.browser_fallback_url=;end';
            } elseif ($device->isIos()) {
                $appList = [];
                $apps = $client->getIosAppSchemes();
                foreach ($apps as $app) {
                    $appList[] = [
                        'name' => $app->displayName(),
                        'link' => $app->scheme() . 'applinks/?al_applink_data={"app_action_type":"TWINT_PAYMENT","extras": {"code": "' . $token . '",},"referer_app_link": {"target_url": "", "url": "", "app_name": "EXTERNAL_WEB_BROWSER"}, "version": "6.0"}',
                    ];
                }
                $payLinks['ios'] = $appList;
            }
        } catch (Exception $e) {
            return $payLinks;
        }
        return $payLinks;
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

<?php

declare(strict_types=1);

namespace Twint\Core\Service;

use Exception;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Twint\Core\DataAbstractionLayer\Entity\Pairing\PairingEntity;
use Twint\Core\Repository\PairingRepository;
use Twint\Core\Setting\Settings;
use Twint\Util\Method\RegularPaymentMethod;

class OrderService
{
    private Context $context;

    public function __construct(
        private readonly EntityRepository $orderRepository,
        private readonly EntityRepository $stateMachineRepository,
        private readonly EntityRepository $stateMachineStateRepository,
        private readonly PairingRepository $pairingRepository,
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

    public function isPaymentFinished(OrderEntity $order): bool
    {
        $state = $this->getTransactionState($order);

        return in_array($state, [OrderTransactionStates::STATE_PAID, OrderTransactionStates::STATE_CANCELLED], true);
    }

    private function getTransactionState(OrderEntity $order): string
    {
        $transactions = $order->getTransactions();

        if (!$transactions instanceof OrderTransactionCollection) {
            return '';
        }

        $transaction = $transactions->last();
        if ($transaction === null) {
            return '';
        }

        $stateMachineState = $transaction->getStateMachineState();
        if ($stateMachineState === null) {
            return '';
        }

        return $stateMachineState->getTechnicalName();
    }

    public function isOrderPaid(OrderEntity $order): bool
    {
        return $this->getTransactionState($order) === OrderTransactionStates::STATE_PAID;
    }

    public function isCancelPaid(OrderEntity $order): bool
    {
        return $this->getTransactionState($order) === OrderTransactionStates::STATE_CANCELLED;
    }

    /**
     * Return an order entity, enriched with associations.
     *
     * @throws Exception
     */
    public function getOrder(string $orderId, Context $context = null, array $associations = []): OrderEntity
    {
        $defaults = [
            'currency',
            'addresses',
            'shippingAddress',
            'billingAddress',
            'billingAddress.country',
            'orderCustomer',
            'orderCustomer.customer',
            'orderCustomer.salutation',
            'language',
            'language.locale',
            'lineItems',
            'lineItems.product.media',
            'deliveries.shippingOrderAddress',
            'deliveries.shippingOrderAddress.country',
            'deliveries.shippingMethod',
            'deliveries.positions.orderLineItem',
            'transactions.paymentMethod',
            'transactions.paymentMethod.appPaymentMethod.app',
            'transactions.stateMachineState',
        ];

        $associations = $associations === [] ? $defaults : $associations;


        $criteria = new Criteria([$orderId]);
        foreach ($associations as $association) {
            $criteria->addAssociation($association);
        }

        if (!$context instanceof Context) {
            $context = Context::createDefaultContext();
        }

        $order = $this->orderRepository->search($criteria, $context)
            ->first();

        if ($order instanceof OrderEntity) {
            return $order;
        }
        throw new Exception($orderId);
    }

    /**
     * @throws Exception
     */
    public function getCurrentStatuses(string $orderId): array
    {
        $order = $this->getOrder($orderId, null, ['transactions.stateMachineState', 'transactions']);

        return [
            'orderId' => $order->getId(),
            'orderVersionId' => $order->getVersionId(),
            'paymentStateId' => $order->getTransactions()?->first()?->getStateId() ?? '',
            'orderStateId' => $order->getStateId(),
            'transactionId' => $order->getTransactions()?->first()?->getId() ?? null,
        ];
    }

    public function getPairing(string $orderId): ?PairingEntity
    {
        return $this->pairingRepository->findByOrderId($orderId);
    }
}

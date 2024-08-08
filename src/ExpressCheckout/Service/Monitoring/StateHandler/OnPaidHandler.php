<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\Service\Monitoring\StateHandler;

use Defuse\Crypto\Encoding;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartPersister;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Twint\Core\DataAbstractionLayer\Entity\Pairing\PairingEntity;
use Twint\Core\DataAbstractionLayer\Entity\TransactionLog\TwintTransactionLogDefinition;
use Twint\Core\Service\CurrencyService;
use Twint\Core\Service\OrderService;
use Twint\ExpressCheckout\Model\ApiResponse;
use Twint\ExpressCheckout\Service\ExpressPaymentService;
use Twint\ExpressCheckout\Service\Monitoring\ContextFactory as TwintContext;
use Twint\ExpressCheckout\Service\Monitoring\CustomerRegisterService;
use Twint\ExpressCheckout\Service\PairingService;
use Twint\ExpressCheckout\Util\PaymentMethodUtil;
use Twint\Sdk\Value\FastCheckoutCheckIn;
use Twint\Sdk\Value\Order;
use Twint\Util\OrderCustomFieldInstaller;

class OnPaidHandler implements StateHandlerInterface
{
    public function __construct(
        private CartService $cartService,
        private TwintContext $context,
        private CustomerRegisterService $customerService,
        private readonly PaymentMethodUtil $paymentMethodUtil,
        private readonly EntityRepository $orderRepository,
        private readonly OrderTransactionStateHandler $transactionStateHandler,
        private readonly CurrencyService $currencyService,
        private readonly PairingService $pairingService,
        private readonly ExpressPaymentService $paymentService,
        private readonly OrderService $orderService,
        private readonly Connection $connection,
        private readonly CartPersister $cartPersister
    ) {
    }

    /**
     * @throws Exception
     */
    public function handle(PairingEntity $entity, FastCheckoutCheckIn $state): void
    {
        if (empty($entity->getCustomerData()) || ($entity->getShippingMethodId() === '' || $entity->getShippingMethodId() === '0')) {
            return;
        }

        //Register customer
        list($customerEntity, $customerData) = $this->registerCustomer($entity);

        //Create context
        $this->createContext($entity, $state, $customerEntity, $customerData);

        // Place order
        $order = $this->placeOrder($entity);

        $entity->setOrder($order);

        //Start TWINT order
        $res = $this->paymentService->startFastCheckoutOrder($order, $entity);

        // Append fields to transaction log
        $this->appendLogFields($order, $res->getLog());

        $twint = $res->getReturn();

        //Update custom field
        $this->updateCustomFields($order, $twint);
        $order = $this->reloadOrder($order->getId());

        $success = $this->refreshTwintTransactionStatusUntilDone($entity, $order, $res);

        $this->massUpdateLogs($order, $entity->getId());

        //Update order_id in Pairing table
        $entity->setOrderId($order->getId());
        $this->pairingService->persistOrderId($entity, $order->getId());

        if ($success) {
            // Delete cart
            $this->cleanUpCurrentCart($entity);

            //Flag as done
            $this->pairingService->markAsDone($entity);
        } else {
            $this->pairingService->markAsCancelled($entity);
        }
    }

    protected function refreshTwintTransactionStatusUntilDone(
        PairingEntity $entity,
        OrderEntity $order,
        ApiResponse $res
    ): bool {
        $tOrder = $res->getReturn();

        if ($tOrder->isSuccessful()) {
            // Change payment status
            $this->markTransactionAsPaid($order, $entity);

            // Append fields to transaction log
            $this->appendLogFields($order, $res->getLog());

            return true;
        }

        if ($tOrder->isFailure()) {
            // Change payment status
            $this->markTransactionAsCancelled($order, $entity);

            // Append fields to transaction log
            $this->appendLogFields($order, $res->getLog());

            return false;
        }
        // Request until get success/fail. Assume TWINT API will finish within a few seconds
        $res = $this->paymentService->monitoringOrder($tOrder->id()->__toString(), $order->getSalesChannelId());

        return $this->refreshTwintTransactionStatusUntilDone($entity, $order, $res);
    }

    protected function cleanUpCurrentCart(PairingEntity $entity): void
    {
        $context = $this->context->getContext($entity->getSalesChannelId());

        $this->cartPersister->delete($entity->getCartToken(true), $context);
    }

    /**
     * @throws Exception
     */
    public function massUpdateLogs(OrderEntity $order, string $pairingId): void
    {
        $table = TwintTransactionLogDefinition::ENTITY_NAME;
        // Your SQL query
        $sql = "UPDATE {$table} SET order_id = :order_id WHERE pairing_id = :pairing_id";

        // Execute the query
        $this->connection->executeQuery($sql, [
            'order_id' => Encoding::hexToBin($order->getId()),
            'pairing_id' => $pairingId,
        ]);
    }

    protected function appendLogFields(OrderEntity $order, array $log): EntityWrittenContainerEvent
    {
        $order = $this->reloadOrder($order->getId());

        /** @var OrderTransactionEntity $transaction */
        $transaction = $order->getTransactions()
            ?->first();

        if ($transaction instanceof OrderTransactionEntity) {
            $trans = [
                'paymentStateId' => $transaction->getStateId(),
                'transactionId' => $transaction->getId(),
            ];
        }

        $appends = [
            'orderId' => $order->getId(),
            'orderVersionId' => $order->getVersionId(),
            'orderStateId' => $order->getStateId(),
        ];

        $log = array_merge($log, $appends, $trans ?? []);
        return $this->paymentService->api->saveLog($log);
    }

    protected function updateCustomFields(OrderEntity $order, ?Order $twint): void
    {
        $customFields = $order->getCustomFields();
        $response = json_encode($twint);

        $customFields[OrderCustomFieldInstaller::TWINT_API_RESPONSE_CUSTOM_FIELD] = $response;
        $this->orderService->updateOrderCustomField($order->getId(), $customFields);
    }

    protected function createContext(
        PairingEntity $entity,
        FastCheckoutCheckIn $state,
        CustomerEntity $customerEntity,
        array $customerData
    ): void {
        //Create new context for the customer
        $this->context->createContext($entity->getSalesChannelId(), [
            'customerId' => $customerEntity->getId(),
            'shippingMethodId' => (string) $state->shippingMethodId(),
            'shippingAddressId' => $customerData['defaultShippingAddressId'],
            'paymentMethodId' => $this->paymentMethodUtil->getExpressCheckoutMethodId(),
            'currencyId' => $this->currencyService->getCurrencyId(),
        ]);
    }

    protected function placeOrder(PairingEntity $entity): OrderEntity
    {
        if (!$entity->getCart() instanceof Cart) {
            throw new Exception('Cart not found');
        }
        $cart = $entity->getCart();
        $cart->setCustomerComment('');
        $orderId = $this->cartService->order(
            $cart,
            $this->context->getContext($entity->getSalesChannelId()),
            new RequestDataBag()
        );

        return $this->reloadOrder($orderId);
    }

    private function reloadOrder(string $orderId): OrderEntity
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions');
        $criteria->addAssociation('currency');

        // @phpstan-ignore-next-line: Always has record here
        return $this->orderRepository->search($criteria, Context::createDefaultContext())
            ->first();
    }

    protected function registerCustomer(PairingEntity $entity): array
    {
        return $this->customerService->register($entity, $this->context->getContext($entity->getSalesChannelId()));
    }

    /**
     * @throws Exception
     */
    protected function markTransactionAsPaid(OrderEntity $order, PairingEntity $entity): void
    {
        $order = $this->reloadOrder($order->getId());

        $transaction = $order->getTransactions()
            ?->first();

        if ($transaction === null) {
            throw new Exception('Transaction not found');
        }

        $context = $this->context->getContext($entity->getSalesChannelId());
        $this->transactionStateHandler->paid($transaction->getId(), $context->getContext());
    }

    protected function markTransactionAsCancelled(OrderEntity $order, PairingEntity $entity): void
    {
        $order = $this->reloadOrder($order->getId());

        $transaction = $order->getTransactions()
            ?->first();

        if ($transaction === null) {
            throw new Exception('Transaction not found');
        }

        $context = $this->context->getContext($entity->getSalesChannelId());
        $this->transactionStateHandler->cancel($transaction->getId(), $context->getContext());
    }
}

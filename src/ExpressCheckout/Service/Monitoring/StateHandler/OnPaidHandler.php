<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\Service\Monitoring\StateHandler;

use Doctrine\DBAL\Exception;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Twint\Core\DataAbstractionLayer\Entity\Pairing\PairingEntity;
use Twint\Core\Service\CurrencyService;
use Twint\Core\Service\OrderService;
use Twint\ExpressCheckout\Service\Monitoring\ContextFactory as TwintContext;
use Twint\ExpressCheckout\Service\Monitoring\CustomerRegisterService;
use Twint\ExpressCheckout\Service\PairingService;
use Twint\ExpressCheckout\Service\PaymentService;
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
        private readonly PaymentService $paymentService,
        private readonly OrderService $orderService,
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

        //Register
        list($customerEntity, $customerData) = $this->registerCustomer($entity);

        //Create context
        $this->createContext($entity, $state, $customerEntity, $customerData);

        // Place order
        $order = $this->placeOrder($entity);

        //Start TWINT order
        $twint = $this->paymentService->startFastCheckoutOrder($order, $entity);

        //Update custom field
        $this->updateCustomFields($order, $twint);

        // Change payment status
        $this->markTransactionAsPaid($order, $entity);

        //Update order_id in Pairing table
        $this->pairingService->persistOrderId($entity, $order->getId());
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

        $orderId = $this->cartService->order(
            $entity->getCart(),
            $this->context->getContext($entity->getSalesChannelId()),
            new RequestDataBag()
        );

        $context = $this->context->getContext($entity->getSalesChannelId());
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions');

        /** @var OrderEntity $order */
        $order = $this->orderRepository->search($criteria, $context->getContext())
            ->first();

        if ($order === null) {
            throw new Exception('Order not found: ' . $orderId);
        }

        return $order;
    }

    protected function registerCustomer(PairingEntity $entity): array
    {
        return $this->customerService->register($entity, $this->context->getContext($entity->getSalesChannelId()));
    }

    protected function markTransactionAsPaid(OrderEntity $order, PairingEntity $entity): void
    {
        $transaction = $order->getTransactions()
            ?->first();

        if ($transaction === null) {
            throw new Exception('Transaction not found');
        }

        $context = $this->context->getContext($entity->getSalesChannelId());
        $this->transactionStateHandler->paid($transaction->getId(), $context->getContext());
    }
}

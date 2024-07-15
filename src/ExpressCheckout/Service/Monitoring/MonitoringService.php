<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\Service\Monitoring;

use Doctrine\DBAL\Exception;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Throwable;
use Twint\Core\DataAbstractionLayer\Entity\Pairing\TwintPairingEntity;
use Twint\Core\Setting\Settings;
use Twint\ExpressCheckout\Repository\PairingRepository;
use Twint\ExpressCheckout\Service\ExpressPaymentService;
use Twint\ExpressCheckout\Util\PaymentMethodUtil;
use Twint\Sdk\Exception\SdkError;
use Twint\Sdk\Value\FastCheckoutCheckIn;
use Twint\Sdk\Value\FastCheckoutState;
use Twint\Sdk\Value\PairingStatus;
use Twint\Sdk\Value\ShippingMethodId;

class MonitoringService
{
    public static array $contexts = [];

    public function __construct(
        private PairingRepository $pairingRepository,
        private ExpressPaymentService $paymentService,
        private CartService $cartService,
        private AbstractSalesChannelContextFactory $contextFactory,
        private CustomerRegisterService $customerService,
        private readonly PaymentMethodUtil $paymentMethodUtil,
        private readonly EntityRepository $orderRepository,
        private readonly EntityRepository $currencyRepository,
        private readonly OrderTransactionStateHandler $transactionStateHandler,
    ) {
    }

    /**
     * @throws SdkError
     * @throws Exception
     */
    public function monitor(): void
    {
        $pairings = $this->pairingRepository->loadInProcessPairings();
        /** @var TwintPairingEntity $pairing */
        foreach ($pairings as $pairing) {
            try {
                $state = $this->paymentService->monitoring($pairing->getId(), $pairing->getSalesChannelId());
                $this->pairingRepository->fetchCart($pairing, $this->getContext($pairing->getSalesChannelId()));
                $this->updatePairing($pairing, $state);
                $this->handle($pairing, $state);

                //TODO
                $this->markPairingAsDone($pairing);
            } catch (Throwable $e) {
                $this->markPairingAsError($pairing);
                echo $e->getMessage();
                throw $e;
            }
        }
    }

    private function persistOrderForPairing(TwintPairingEntity $pairing, string $orderId): EntityWrittenContainerEvent
    {
        return $this->persistPairing($pairing, [
            'orderId' => $orderId,
        ]);
    }

    private function markPairingAsDone(TwintPairingEntity $pairing): EntityWrittenContainerEvent
    {
        return $this->persistPairing($pairing, [
            'status' => 'DONE',
        ]);
    }

    private function markPairingAsError(TwintPairingEntity $pairing): EntityWrittenContainerEvent
    {
        return $this->persistPairing($pairing, [
            'status' => 'ERROR',
        ]);
    }

    private function persistPairing(TwintPairingEntity $pairing, array $data): EntityWrittenContainerEvent
    {
        $id = [
            'id' => $pairing->getUniqueIdentifier(),
        ];

        $data = array_merge($id, $data);

        return $this->pairingRepository->update([$data]);
    }

    protected function updatePairing(
        TwintPairingEntity $pairingEntity,
        FastCheckoutState $state
    ): EntityWrittenContainerEvent {
        if (!($state instanceof FastCheckoutCheckIn)) {
            throw new Exception('Invalid state');
        }

        $pairingEntity->setPairingStatus((string) $state->pairingStatus());
        $pairingEntity->setShippingMethodId((string) $state->shippingMethodId());
        $pairingEntity->setCustomerData($state->customerData());

        //Store the updated data in the database
        $data = [
            'id' => $pairingEntity->getUniqueIdentifier(),
            'status' => (string) $state->pairingStatus(),
            'customerData' => json_decode((string) json_encode($state->customerData()), true),
        ];

        if ($state->shippingMethodId() instanceof ShippingMethodId) {
            $data['shippingMethodId'] = (string) $state->shippingMethodId();
        }

        return $this->pairingRepository->update([$data]);
    }

    /**
     * @throws Exception
     */
    protected function handle(TwintPairingEntity $entity, FastCheckoutState $state): void
    {
        switch ($state->pairingStatus()) {
            case PairingStatus::PAIRING_IN_PROGRESS:
            case PairingStatus::NO_PAIRING:
            case PairingStatus::PAIRING_ACTIVE:
                if ($state instanceof FastCheckoutCheckIn) {
                    $this->handlePaid($entity, $state);
                }

                break;

                //            case PairingStatus::PAIRING_ACTIVE:
                //                $this->handleCanceled();
                //                break;

                //            case PairingStatus::PAIRING_IN_PROGRESS:
                //                //do nothing
                //                break;
        }
    }

    protected function createContext(string $channelId, array $session = []): SalesChannelContext
    {
        $token = Random::getAlphanumericString(32);
        $context = $this->contextFactory->create($token, $channelId, $session);
        self::$contexts[$channelId] = $context;

        return $context;
    }

    public function getContext(string $channelId): SalesChannelContext
    {
        return self::$contexts[$channelId] ?? $this->createContext($channelId);
    }

    /**
     * @throws Exception
     */
    private function handlePaid(TwintPairingEntity $entity, FastCheckoutCheckIn $state): void
    {
        $channelId = $entity->getSalesChannelId();
        list($customerEntity, $customerData) = $this->customerService->register(
            $entity,
            $this->getContext($entity->getSalesChannelId())
        );

        //Create new context for the customer
        $this->createContext($channelId, [
            'customerId' => $customerEntity->getId(),
            'shippingMethodId' => (string) $state->shippingMethodId(),
            'shippingAddressId' => $customerData['defaultShippingAddressId'], //TODO
            'paymentMethodId' => $this->paymentMethodUtil->getExpressCheckoutMethodId(),
            'currencyId' => $this->getCurrencyId(),
        ]);

        if (!$entity->getCart() instanceof Cart) {
            throw new Exception('Cart not found');
        }

        $orderId = $this->cartService->order(
            $entity->getCart(),
            $this->getContext($entity->getSalesChannelId()),
            new RequestDataBag()
        );

        $this->updateOrder($orderId, $entity, $state);
        $this->persistOrderForPairing($entity, $orderId);
    }

    protected function updateOrder(string $orderId, TwintPairingEntity $entity, FastCheckoutState $state): void
    {
        $context = $this->getContext($entity->getSalesChannelId());
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions');

        /** @var OrderEntity $order */
        $order = $this->orderRepository->search($criteria, $context->getContext())
            ->first();

        if ($order === null) {
            throw new Exception('Order not found: ' . $orderId);
        }

        $transaction = $order->getTransactions()
            ?->first();

        if ($transaction === null) {
            throw new Exception('Transaction not found');
        }

        $this->transactionStateHandler->paid($transaction->getId(), $context->getContext());
    }

    protected function getCurrencyId(): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('symbol', Settings::ALLOWED_CURRENCY));

        return $this->currencyRepository->searchIds($criteria, Context::createDefaultContext())
            ->firstId();
    }
}

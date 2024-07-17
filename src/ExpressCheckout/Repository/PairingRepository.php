<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\Repository;

use Shopware\Core\Checkout\Cart\CartPersister;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Twint\Core\DataAbstractionLayer\Entity\Pairing\PairingEntity;
use Twint\ExpressCheckout\Exception\PairingException;
use Twint\Sdk\Value\PairingStatus;

class PairingRepository
{
    public function __construct(
        private EntityRepository $pairingRepository,
        private CartPersister $cartPersister,
        private EntityRepository $orderRepository,
        private CartService $cartService
    ) {
    }

    public function load(string $pairingId, SalesChannelContext $context): PairingEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $pairingId));
        $criteria->addAssociation('shippingMethod');

        $pairing = $this->pairingRepository->search($criteria, $context->getContext())
            ->first();

        if (($pairing instanceof PairingEntity) === false) {
            throw new PairingException("{$pairingId} not found");
        }

        $this->fetchCart($pairing, $context);

        return $pairing;
    }

    public function fetchCart(PairingEntity $entity, SalesChannelContext $context): PairingEntity
    {
        $cart = $this->cartPersister->load($entity->getCartToken(), $context);
        $cart = $this->cartService->recalculate($cart, $context);
        $entity->setCart($cart);

        return $entity;
    }

    public function fetchOrder(PairingEntity $entity, SalesChannelContext $context): PairingEntity
    {
        if ($entity->getOrderId() === null) {
            return $entity;
        }

        $criteria = new Criteria([$entity->getOrderId()]);
        $criteria->addAssociation('lineItems.cover')
            ->addAssociation('transactions.paymentMethod')
            ->addAssociation('deliveries.shippingMethod')
            ->addAssociation('billingAddress.salutation')
            ->addAssociation('billingAddress.country')
            ->addAssociation('billingAddress.countryState')
            ->addAssociation('deliveries.shippingOrderAddress.salutation')
            ->addAssociation('deliveries.shippingOrderAddress.country')
            ->addAssociation('deliveries.shippingOrderAddress.countryState');

        /** @var OrderEntity $order */
        $order = $this->orderRepository->search($criteria, $context->getContext())
            ->first();

        $entity->setOrder($order);
        return $entity;
    }

    public function loadInProcessPairings(): EntitySearchResult
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('status', [PairingStatus::PAIRING_IN_PROGRESS]));
        $criteria->addAssociation('shippingMethod');

        return $this->pairingRepository->search($criteria, Context::createDefaultContext());
    }

    public function update(array $data): EntityWrittenContainerEvent
    {
        return $this->pairingRepository->update($data, Context::createDefaultContext());
    }
}

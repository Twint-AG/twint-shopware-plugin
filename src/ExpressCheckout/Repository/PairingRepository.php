<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\Repository;

use Shopware\Core\Checkout\Cart\CartPersister;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Twint\Core\DataAbstractionLayer\Entity\Pairing\TwintPairingEntity;
use Twint\ExpressCheckout\Exception\PairingException;
use Twint\Sdk\Value\PairingStatus;

class PairingRepository
{
    public function __construct(
        private EntityRepository $pairingRepository,
        private CartPersister $cartPersister,
        private EntityRepository $orderRepository,
    ) {
    }

    public function load(string $pairingId, SalesChannelContext $context): TwintPairingEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $pairingId));
        $criteria->addAssociation('shippingMethod');

        $pairing = $this->pairingRepository->search($criteria, $context->getContext())
            ->first();

        if (($pairing instanceof TwintPairingEntity) === false) {
            throw new PairingException("{$pairingId} not found");
        }

        $cart = $this->cartPersister->load($pairing->getCartToken(), $context);
        $pairing->setCart($cart);

        return $pairing;
    }

    public function fetchCart(TwintPairingEntity $entity, SalesChannelContext $context): TwintPairingEntity
    {
        $cart = $this->cartPersister->load($entity->getCartToken(), $context);
        $entity->setCart($cart);

        return $entity;
    }

    public function fetchOrder(TwintPairingEntity $entity, SalesChannelContext $context): TwintPairingEntity
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
        $criteria->addFilter(new EqualsFilter('status', PairingStatus::PAIRING_IN_PROGRESS));
        $criteria->addAssociation('shippingMethod');

        return $this->pairingRepository->search($criteria, Context::createDefaultContext());
    }

    public function update(array $data): EntityWrittenContainerEvent
    {
        return $this->pairingRepository->update($data, Context::createDefaultContext());
    }
}

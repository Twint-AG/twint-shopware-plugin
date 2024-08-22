<?php

declare(strict_types=1);

namespace Twint\Core\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
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
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Twint\Core\DataAbstractionLayer\Entity\Pairing\PairingDefinition;
use Twint\Core\DataAbstractionLayer\Entity\Pairing\PairingEntity;
use Twint\ExpressCheckout\Exception\PairingException;
use Twint\Sdk\Value\OrderStatus;
use Twint\Sdk\Value\PairingStatus;

class PairingRepository
{
    public function __construct(
        private EntityRepository $repository,
        private CartPersister $cartPersister,
        private EntityRepository $orderRepository,
        private CartService $cartService,
        private Connection $db
    ) {
    }

    public function load(string $pairingId, Context $context): PairingEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $pairingId));
        $criteria->addAssociation('shippingMethod');
        $criteria->addAssociation('customer');
        $criteria->addAssociation('customer.addresses');

        $pairing = $this->repository->search($criteria, $context)
            ->first();

        if (($pairing instanceof PairingEntity) === false) {
            throw new PairingException("{$pairingId} not found");
        }

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
        $criteria->addFilter(
            new EqualsAnyFilter('status', [PairingStatus::PAIRING_IN_PROGRESS, OrderStatus::IN_PROGRESS])
        );
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::ASCENDING));
        $criteria->addAssociation('shippingMethod');
        $criteria->addAssociation('customer.addresses');
        $criteria->addAssociation('order');
        $criteria->addAssociation('order.transactions');

        return $this->repository->search($criteria, Context::createDefaultContext());
    }

    public function update(array $data): EntityWrittenContainerEvent
    {
        //validate $data to make sure always has version

        return $this->repository->update($data, Context::createDefaultContext());
    }

    public function findByOrderId(string $orderId, array $associations = []): ?PairingEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        foreach ($associations as $association) {
            $criteria->addAssociation($association);
        }


        /** @var PairingEntity $entity */
        $entity = $this->repository->search($criteria, Context::createDefaultContext())
            ->first();

        return $entity;
    }

    /**
     * @throws Exception
     */
    public function updateCheckedAt(string $pairingId): int|string
    {
        return $this->db->executeStatement("
            UPDATE ".PairingDefinition::ENTITY_NAME."
            SET checked_at = NOW();
            WHERE id = :id", [
            'id' => $pairingId
        ]);
    }
}

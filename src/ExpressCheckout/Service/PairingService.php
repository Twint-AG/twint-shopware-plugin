<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\Service;

use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Twint\Core\DataAbstractionLayer\Entity\Pairing\PairingEntity;
use Twint\ExpressCheckout\Repository\PairingRepository;
use Twint\Sdk\Value\FastCheckoutCheckIn;
use Twint\Sdk\Value\FastCheckoutState;
use Twint\Sdk\Value\ShippingMethodId;

class PairingService
{
    public const STATUS_DONE = 'DONE';

    public const STATUS_CANCELED = 'CANCELLED';

    public function __construct(
        private readonly PairingRepository $repository,
    ) {
    }

    public function fetchCart(PairingEntity $entity, SalesChannelContext $context): PairingEntity
    {
        return $this->repository->fetchCart($entity, $context);
    }

    public function loadInProcessPairings(): EntitySearchResult
    {
        return $this->repository->loadInProcessPairings();
    }

    public function persistOrderId(PairingEntity $pairing, string $orderId): EntityWrittenContainerEvent
    {
        return $this->persist($pairing, [
            'orderId' => $orderId,
        ]);
    }

    public function markAsDone(PairingEntity $pairing): EntityWrittenContainerEvent
    {
        $pairing->setStatus(self::STATUS_DONE);
        return $this->persist($pairing, [
            'status' => self::STATUS_DONE,
        ]);
    }

    public function markAsCancelled(PairingEntity $pairing): EntityWrittenContainerEvent
    {
        $pairing->setStatus(self::STATUS_CANCELED);
        return $this->persist($pairing, [
            'status' => self::STATUS_CANCELED,
        ]);
    }

    private function persist(PairingEntity $pairing, array $data): EntityWrittenContainerEvent
    {
        $id = [
            'id' => $pairing->getUniqueIdentifier(),
        ];

        $data = array_merge($id, $data);

        return $this->repository->update([$data]);
    }

    public function update(PairingEntity $entity, FastCheckoutState $state): EntityWrittenContainerEvent
    {
        if (!($state instanceof FastCheckoutCheckIn)) {
            throw new Exception('Invalid state');
        }

        $entity->setPairingStatus((string) $state->pairingStatus());
        $entity->setShippingMethodId((string) $state->shippingMethodId());
        $entity->setCustomerData($state->customerData());

        //Store the updated data in the database
        $data = [
            'id' => $entity->getUniqueIdentifier(),
            'status' => (string) $state->pairingStatus(),
            'customerData' => json_decode((string) json_encode($state->customerData()), true),
        ];

        if ($state->shippingMethodId() instanceof ShippingMethodId) {
            $data['shippingMethodId'] = (string) $state->shippingMethodId();
        }

        return $this->repository->update([$data]);
    }
}

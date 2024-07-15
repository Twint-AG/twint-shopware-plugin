<?php

declare(strict_types=1);

namespace Twint\Core\DataAbstractionLayer\Entity\Pairing;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<PairingEntity>
 */
class PairingCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PairingEntity::class;
    }
}

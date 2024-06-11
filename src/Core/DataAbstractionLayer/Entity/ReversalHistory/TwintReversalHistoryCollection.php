<?php

declare(strict_types=1);

namespace Twint\Core\DataAbstractionLayer\Entity\ReversalHistory;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<TwintReversalHistoryEntity>
 */
class TwintReversalHistoryCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return TwintReversalHistoryEntity::class;
    }
}

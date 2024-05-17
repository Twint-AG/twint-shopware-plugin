<?php

declare(strict_types=1);

namespace Twint\Core\DataAbstractionLayer\Entity\TransactionLog;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<TwintTransactionLogEntity>
 */
class TwintTransactionLogCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return TwintTransactionLogEntity::class;
    }
}

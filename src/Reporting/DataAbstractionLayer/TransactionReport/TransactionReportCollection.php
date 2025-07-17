<?php

declare(strict_types=1);

namespace Twint\Reporting\DataAbstractionLayer\TransactionReport;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 *
 * @codeCoverageIgnore
 *
 * @extends EntityCollection<TransactionReportEntity>
 */
#[Package(
    'checkout'
)]
class TransactionReportCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return TransactionReportEntity::class;
    }
}

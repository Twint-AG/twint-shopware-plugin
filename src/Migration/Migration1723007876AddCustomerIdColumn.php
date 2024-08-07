<?php

declare(strict_types=1);

namespace Twint\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('core')]
class Migration1723007876AddCustomerIdColumn extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1723007876;
    }

    public function update(Connection $connection): void
    {
        $sql = 'ALTER TABLE twint_pairing ADD COLUMN customer_id binary(16) DEFAULT NULL;';
        $connection->executeStatement($sql);

        $sql = 'ALTER TABLE `twint_pairing`
                ADD CONSTRAINT `fk.twint_pairing.customer_id`
                FOREIGN KEY (`customer_id`)
                REFERENCES `customer` (`id`)
                ON DELETE SET NULL
                ON UPDATE CASCADE;';
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}

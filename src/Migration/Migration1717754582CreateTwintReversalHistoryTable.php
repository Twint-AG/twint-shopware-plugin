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
class Migration1717754582CreateTwintReversalHistoryTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1717754582;
    }

    public function update(Connection $connection): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `twint_reversal_history` (
            `id` BINARY(16) NOT NULL,
            `order_id` BINARY(16) NOT NULL,
            `reversal_id` VARCHAR(255) NOT NULL,
            `amount` DECIMAL(10,3) unsigned NOT NULL,
            `currency` VARCHAR(3) NOT NULL,
            `reason` text NULL,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3) NULL,
            PRIMARY KEY (`id`),
            KEY `fk.twint_reversal_history.order_id` (`order_id`),
            CONSTRAINT `fk.twint_reversal_history.order_id` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}

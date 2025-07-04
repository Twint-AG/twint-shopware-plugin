<?php

declare(strict_types=1);

namespace Twint\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('checkout')]
class Migration1750704424TransactionReport extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1750704424;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `twint_transaction_report` (
                `id`                           BINARY(16)     NOT NULL,
                `order_transaction_id`         BINARY(16)     NOT NULL,
                `order_transaction_version_id` BINARY(16)     NOT NULL,
                `currency_iso`                 VARCHAR(3)     NOT NULL,
                `total_price`                  DECIMAL(20, 2) NOT NULL,
                `created_at`                   DATETIME(3)    NOT NULL,
                `updated_at`                   DATETIME(3)    NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk.twint_transaction_report.order_transaction_id` FOREIGN KEY (`order_transaction_id`, `order_transaction_version_id`) REFERENCES `order_transaction` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

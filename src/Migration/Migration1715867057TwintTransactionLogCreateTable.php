<?php

declare(strict_types=1);

namespace Twint\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1715867057TwintTransactionLogCreateTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1715867057;
    }

    public function update(Connection $connection): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `twint_transaction_log` (
            `id` BINARY(16) NOT NULL,
            `order_id` BINARY(16) NOT NULL,
            `order_version_id` BINARY(16) NOT NULL,
            `payment_state_id` BINARY(16) NOT NULL,
            `order_state_id` BINARY(16) NOT NULL,
            `transaction_id` VARCHAR(255) NOT NULL,
            `api_method` json NOT NULL,
            `request` text NOT NULL,
            `response` text NOT NULL,
            `soap_request` json NOT NULL,
            `soap_response` json NOT NULL,
            `exception` text NULL,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3) NULL,
            PRIMARY KEY (`id`),
            KEY `fk.twint_transaction_log.order_id` (`order_id`,`order_version_id`),
            CONSTRAINT `fk.twint_transaction_log.order_id` FOREIGN KEY (`order_id`,`order_version_id`) REFERENCES `order` (`id`,`version_id`),
            CONSTRAINT `fk.twint_transaction_log.payment_state_id` FOREIGN KEY (`payment_state_id`) REFERENCES `state_machine_state` (`id`),
            CONSTRAINT `fk.twint_transaction_log.order_state_id` FOREIGN KEY (`order_state_id`) REFERENCES `state_machine_state` (`id`) 
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}

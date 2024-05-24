<?php

declare(strict_types=1);

namespace Twint\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1716437509CreatePairingTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1716437509;
    }

    public function update(Connection $connection): void
    {
        $sql = 'CREATE TABLE `twint_pairing` (
            `id` VARCHAR(255) NOT NULL,
            `cart_token` VARCHAR(255) NOT NULL,
            `status` VARCHAR(255) NOT NULL,
            `token` VARCHAR(255) NOT NULL,
            `sales_channel_id` BINARY(16) NULL,
            `shipping_method_id` BINARY(16) NULL,
            `order_id` BINARY(16) NULL,
            `customer_data` JSON NULL,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3) NULL,
            PRIMARY KEY (`id`),
            CONSTRAINT `json.twint_pairing.customer_data` CHECK (JSON_VALID(`customer_data`)),
            KEY `fk.twint_pairing.sales_channel_id` (`sales_channel_id`),
            KEY `fk.twint_pairing.shipping_method_id` (`shipping_method_id`),
            KEY `fk.twint_pairing.order_id` (`order_id`),
            CONSTRAINT `fk.twint_pairing.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
            CONSTRAINT `fk.twint_pairing.shipping_method_id` FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_method` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
            CONSTRAINT `fk.twint_pairing.order_id` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

        $connection->executeStatement($sql);
        $this->createIndex($connection);
    }

    private function createIndex(Connection $connection): void
    {
        $sql = 'CREATE INDEX twint_pairing_status_IDX USING BTREE ON twint_pairing (status);';
        $connection->executeStatement($sql);
    }
}

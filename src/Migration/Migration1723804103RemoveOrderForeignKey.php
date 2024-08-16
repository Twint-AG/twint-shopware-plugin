<?php

declare(strict_types=1);

namespace Twint\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('core')]
class Migration1723804103RemoveOrderForeignKey extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1723804103;
    }

    /**
     * @throws Exception
     */
    public function update(Connection $connection): void
    {
        $sqls = [
            'ALTER TABLE twint_pairing DROP CONSTRAINT `fk.twint_pairing.order_id`;',
            'CREATE INDEX twint_pairing_order_IDX USING BTREE ON twint_pairing (order_id);',
            'ALTER TABLE twint_pairing ADD COLUMN `is_express` bool NOT NULL DEFAULT false;',
            'ALTER TABLE twint_pairing ADD COLUMN `amount` DECIMAL(19,2) unsigned NOT NULL;',
            'ALTER TABLE twint_pairing ADD COLUMN `pairing_status` VARCHAR(255) NULL;',
            'ALTER TABLE twint_pairing ADD COLUMN `transaction_status` VARCHAR(255) NULL;',
            'ALTER TABLE twint_pairing MODIFY COLUMN `token` VARCHAR(255) NULL;',
            'ALTER TABLE twint_pairing MODIFY COLUMN `cart_token` VARCHAR(255) NULL;',
        ];

        foreach ($sqls as $sql) {
            $connection->executeStatement($sql);
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}

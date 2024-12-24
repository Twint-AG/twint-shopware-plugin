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
        if ($this->constraintExists($connection, 'twint_pairing', 'fk.twint_pairing.order_id', 'FOREIGN KEY')) {
            $connection->executeStatement('ALTER TABLE twint_pairing DROP CONSTRAINT `fk.twint_pairing.order_id`');
        }
        if (!$this->indexExists($connection, 'twint_pairing', 'twint_pairing_order_IDX')) {
            $connection->executeStatement(
                'CREATE INDEX twint_pairing_order_IDX USING BTREE ON twint_pairing (order_id)'
            );
        }

        if (!$this->columnExists($connection, 'twint_pairing', 'is_express')) {
            $sqls = [
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
    }

    public function constraintExists(
        Connection $connection,
        string $table,
        string $constraint,
        string $type = 'FOREIGN KEY'
    ): bool {
        $sql = sprintf("SELECT CONSTRAINT_NAME
                FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
                WHERE TABLE_NAME = '%s'
                AND CONSTRAINT_NAME = '%s'
                AND CONSTRAINT_TYPE = '%s'
                AND CONSTRAINT_SCHEMA = DATABASE();", $table, $constraint, $type);
        return $connection->executeQuery($sql)
            ->rowCount() > 0;
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}

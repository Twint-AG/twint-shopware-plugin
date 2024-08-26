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
class Migration1724385873AddOrderingColumn extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1724385873;
    }

    /**
     * @throws Exception
     */
    public function update(Connection $connection): void
    {
        $sqls = [
            'ALTER TABLE twint_pairing ADD COLUMN `is_ordering` int unsigned NOT NULL DEFAULT 0;',
            'DROP VIEW IF EXISTS twint_pairing_view;',
            'CREATE VIEW twint_pairing_view AS
                SELECT
                  tp.*,
                  (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(tp.checked_at)) AS checked_ago
                FROM twint_pairing tp;',
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

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
class Migration1727074907AddCreatedAgoColumn extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1727074907;
    }

    public function update(Connection $connection): void
    {
        $sqls = [
            'CREATE OR REPLACE VIEW twint_pairing_view AS
                SELECT 
                    tp.*,
                    (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(tp.checked_at)) AS checked_ago,
                    (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(tp.created_at)) AS created_ago
                FROM 
                    twint_pairing tp;',
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

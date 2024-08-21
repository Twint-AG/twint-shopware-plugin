<?php declare(strict_types=1);

namespace Twint\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('core')]
class Migration1724213001addColumnsToPairingTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1724213001;
    }

    /**
     * @throws Exception
     */
    public function update(Connection $connection): void
    {
        $sqls = [
            'ALTER TABLE twint_pairing ADD COLUMN `pid` int NULL;',
            'ALTER TABLE twint_pairing ADD COLUMN `version` int unsigned NOT NULL DEFAULT 1;',
            "            
            CREATE TRIGGER before_update_pairing
            BEFORE UPDATE ON twint_pairing
            FOR EACH ROW
            BEGIN
            
                -- Perform version check
                IF OLD.version <> NEW.version THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Version conflict detected. Update aborted.';
                END IF;
               
               
               
               IF OLD.pid = NEW.pid THEN
                  SET NEW.version = OLD.version + 1;
               END IF;  
                    
            END;            
           "
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

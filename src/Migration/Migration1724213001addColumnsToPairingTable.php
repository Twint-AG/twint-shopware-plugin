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
            'ALTER TABLE twint_pairing ADD COLUMN `checked_at` datetime(3) NULL;',
            'ALTER TABLE twint_pairing ADD COLUMN `version` int unsigned NOT NULL DEFAULT 1;',
            "            
            CREATE TRIGGER `before_update_twint_pairing` BEFORE UPDATE ON `twint_pairing` FOR EACH ROW BEGIN
	
                DECLARE changed_columns INT;
            
                -- Perform version check
                IF OLD.version <> NEW.version THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Version conflict detected. Update aborted.';
                END IF;
                
                SET changed_columns = 0;
            
                IF NEW.cart_token <> OLD.cart_token OR (NEW.cart_token IS NULL XOR OLD.cart_token IS NULL) THEN
                    SET changed_columns = changed_columns + 1;
                END IF;
                
                IF NEW.status <> OLD.status THEN
                    SET changed_columns = changed_columns + 1;
                END IF;
                
                IF NEW.token <> OLD.token OR (NEW.token IS NULL XOR OLD.token IS NULL) THEN
                    SET changed_columns = changed_columns + 1;
                END IF;
                
                IF NEW.sales_channel_id <> OLD.sales_channel_id OR (NEW.sales_channel_id IS NULL XOR OLD.sales_channel_id IS NULL) THEN
                    SET changed_columns = changed_columns + 1;
                END IF;
                
                IF NEW.shipping_method_id <> OLD.shipping_method_id OR (NEW.shipping_method_id IS NULL XOR OLD.shipping_method_id IS NULL) THEN
                    SET changed_columns = changed_columns + 1;
                END IF;
                
                IF NEW.order_id <> OLD.order_id OR (NEW.order_id IS NULL XOR OLD.order_id IS NULL) THEN
                    SET changed_columns = changed_columns + 1;
                END IF;
                
                IF NEW.customer_data <> OLD.customer_data OR (NEW.customer_data IS NULL XOR OLD.customer_data IS NULL) THEN
                    SET changed_columns = changed_columns + 1;
                END IF;
               
                
                IF NEW.customer_id <> OLD.customer_id OR (NEW.customer_id IS NULL XOR OLD.customer_id IS NULL) THEN
                    SET changed_columns = changed_columns + 1;
                END IF;
                
                IF NEW.is_express <> OLD.is_express THEN
                    SET changed_columns = changed_columns + 1;
                END IF;
                
                IF NEW.amount <> OLD.amount THEN
                    SET changed_columns = changed_columns + 1;
                END IF;
                
                IF NEW.pairing_status <> OLD.pairing_status OR (NEW.pairing_status IS NULL XOR OLD.pairing_status IS NULL) THEN
                    SET changed_columns = changed_columns + 1;
                END IF;
                
                IF NEW.transaction_status <> OLD.transaction_status OR (NEW.transaction_status IS NULL XOR OLD.transaction_status IS NULL) THEN
                    SET changed_columns = changed_columns + 1;
                END IF;          
               
               IF changed_columns > 0 THEN
                  SET NEW.version = OLD.version + 1;
               END IF;  
                    
            END;",
            // Create view
            'CREATE VIEW twint_pairing_view AS
                SELECT
                  tp.*,
                  (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(tp.checked_at)) AS checked_ago
                FROM twint_pairing tp;'
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

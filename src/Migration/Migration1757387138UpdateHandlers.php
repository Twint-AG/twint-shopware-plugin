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
class Migration1757387138UpdateHandlers extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1757387138;
    }

    public function update(Connection $connection): void
    {
        $mappings = [
            // old handler => new handler
            'Twint\\Core\Handler\\TwintRegularPaymentHandler' => 'twint.regular.handler',
            'Twint\\Core\Handler\\TwintExpressPaymentHandler' => 'twint.express.handler',
        ];

        foreach ($mappings as $oldHandler => $newHandler) {
            $connection->executeStatement(
                'UPDATE `payment_method`
                 SET `handler_identifier` = :newHandler
                 WHERE `handler_identifier` = :oldHandler',
                [
                    'newHandler' => $newHandler,
                    'oldHandler' => $oldHandler,
                ]
            );
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        $mappings = [
            // new handler => old handler (revert)
            'twint.regular.handler' => 'Twint\\Core\\Handler\\TwintRegularPaymentHandler',
            'twint.express.handler' => 'Twint\\Core\\Handler\\TwintExpressPaymentHandler',
        ];

        foreach ($mappings as $newHandler => $oldHandler) {
            $connection->executeStatement(
                'UPDATE `payment_method`
                 SET `handler_identifier` = :oldHandler
                 WHERE `handler_identifier` = :newHandler',
                [
                    'oldHandler' => $oldHandler,
                    'newHandler' => $newHandler,
                ]
            );
        }
    }
}

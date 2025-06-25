<?php

declare(strict_types=1);

namespace Twint\Reporting\ScheduledTask;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('checkout')]
class TurnoverReportingTask extends ScheduledTask
{
    private const TIME_INTERVAL = 86400;

    public static function getTaskName(): string
    {
        return 'twint.turnover_reporting';
    }

    public static function getDefaultInterval(): int
    {
        return self::TIME_INTERVAL;
    }
}

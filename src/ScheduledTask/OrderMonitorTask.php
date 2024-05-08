<?php

declare(strict_types=1);

namespace Twint\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class OrderMonitorTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'twint.order-monitor.task';
    }

    public static function getDefaultInterval(): int
    {
        return 60;
    }
}

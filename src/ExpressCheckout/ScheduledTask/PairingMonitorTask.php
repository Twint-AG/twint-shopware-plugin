<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class PairingMonitorTask extends ScheduledTask
{
    // the class body
    public static function getTaskName(): string
    {
        return 'twint.pairing-monitor.task';
    }

    public static function getDefaultInterval(): int
    {
        return 60;
    }
}

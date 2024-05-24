<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Twint\ExpressCheckout\Service\Monitoring\MonitoringService;

#[AsMessageHandler(handles: PairingMonitorTask::class)]
class PairingMonitorTaskHandler extends ScheduledTaskHandler
{
    private MonitoringService $service;

    public static function getHandledMessages(): iterable
    {
        return [PairingMonitorTask::class];
    }

    public function setMonitoringService(MonitoringService $service): void
    {
        $this->service = $service;
    }

    public function run(): void
    {
        $this->service->monitor();

        $this->exceptionLogger?->info('Cron ran successfully');
    }
}

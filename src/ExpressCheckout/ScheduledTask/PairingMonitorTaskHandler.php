<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Twint\ExpressCheckout\Service\Monitoring\MonitoringService;

$interface = 'Symfony\Component\Messenger\Handler\MessageSubscriberInterface';
$interfaces = class_implements(ScheduledTaskHandler::class);

if (interface_exists('Symfony\Component\Messenger\Handler\MessageSubscriberInterface') && in_array(
    $interface,
    $interfaces,
    true
)) {
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
        }
    }
} else {
    #[AsMessageHandler(handles: PairingMonitorTask::class)]
    class PairingMonitorTaskHandler extends ScheduledTaskHandler
    {
        private MonitoringService $service;

        public function setMonitoringService(MonitoringService $service): void
        {
            $this->service = $service;
        }

        public function run(): void
        {
            $this->service->monitor();

            // @phpstan-ignore-next-line: compatible backwards compatibility
            $this->exceptionLogger?->info('Cron ran successfully');
        }
    }
}

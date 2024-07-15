<?php

declare(strict_types=1);

namespace Twint\ScheduledTask;

use Exception;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Twint\Core\Service\OrderMonitoringService;

$interface = 'Symfony\Component\Messenger\Handler\MessageSubscriberInterface';
$interfaces = class_implements(ScheduledTaskHandler::class);

if (interface_exists('Symfony\Component\Messenger\Handler\MessageSubscriberInterface') && in_array(
    $interface,
    $interfaces,
    true
)) {
    class OrderMonitorTaskHandler extends ScheduledTaskHandler
    {
        private OrderMonitoringService $service;

        public static function getHandledMessages(): iterable
        {
            return [OrderMonitorTask::class];
        }

        public function setMonitoringService(OrderMonitoringService $service): void
        {
            $this->service = $service;
        }

        /**
         * @throws Exception
         */
        public function run(): void
        {
            $this->service->monitor(null);
        }
    }
} else {
    #[AsMessageHandler(handles: OrderMonitorTask::class)]
    class OrderMonitorTaskHandler extends ScheduledTaskHandler
    {
        private OrderMonitoringService $service;

        public function setMonitoringService(OrderMonitoringService $service): void
        {
            $this->service = $service;
        }

        public function run(): void
        {
            $this->service->monitor($this->exceptionLogger);

            // @phpstan-ignore-next-line: compatible backwards compatibility
            $this->exceptionLogger?->info('Cron ran successfully');
        }
    }
}

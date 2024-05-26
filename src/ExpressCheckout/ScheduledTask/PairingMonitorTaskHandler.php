<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\ScheduledTask;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Twint\ExpressCheckout\Service\Monitoring\MonitoringService;

#[AsMessageHandler(handles: PairingMonitorTask::class)]
class PairingMonitorTaskHandler extends ScheduledTaskHandler
{
    private MonitoringService $service;

    private LoggerInterface $logger;

    public function __construct(
        EntityRepository $scheduledTaskRepository,
        LoggerInterface $logger
    ) {
        parent::__construct($scheduledTaskRepository);
        $this->logger = $logger;
    }

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

        $this->logger->info('Cron ran successfully');
    }
}

<?php

declare(strict_types=1);

namespace Twint\ScheduledTask;

use Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Twint\Core\Service\PaymentService;

class OrderMonitorTaskHandler extends ScheduledTaskHandler
{
    private PaymentService $paymentService;

    private LoggerInterface $logger;

    public function __construct(
        EntityRepository $scheduledTaskRepository,
        LoggerInterface $logger,
        PaymentService $paymentService
    ) {
        parent::__construct($scheduledTaskRepository);
        $this->paymentService = $paymentService;
        $this->logger = $logger;
    }

    public static function getHandledMessages(): iterable
    {
        return [OrderMonitorTask::class];
    }

    /**
     * @throws Exception
     */
    public function run(): void
    {
        $pendingOrders = $this->paymentService->getPendingOrders();
        if (count($pendingOrders) > 0) {
            /** @var OrderEntity $order */
            foreach ($pendingOrders as $order) {
                try {
                    $this->paymentService->checkOrderStatus($order);
                } catch (Exception $e) {
                    $this->logger->error(
                        sprintf(
                            'TWINT order status cannot be updated: %s with error code: %s',
                            $e->getMessage(),
                            $e->getCode()
                        )
                    );
                }
            }
        }
        $this->logger->info('Cron ran successfully');
    }
}

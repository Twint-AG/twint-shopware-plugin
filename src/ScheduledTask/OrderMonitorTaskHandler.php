<?php declare(strict_types=1);

namespace Twint\ScheduledTask;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Twint\Core\Service\PaymentService;

/**
 * Class OrderMonitorTaskHandler
 */
class OrderMonitorTaskHandler extends ScheduledTaskHandler
{
    /**
     * @var PaymentService
     */
    private PaymentService $paymentService;

    private LoggerInterface $logger;

    /**
     * @param EntityRepository $scheduledTaskRepository
     * @param PaymentService $paymentService
     * @param LoggerInterface $logger
     */
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        PaymentService $paymentService,
        LoggerInterface $logger
    )
    {
        parent::__construct($scheduledTaskRepository);
        $this->paymentService = $paymentService;
        $this->logger = $logger;
    }

    public static function getHandledMessages(): iterable
    {
        return [OrderMonitorTask::class];
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function run(): void
    {
        $result = [];
        $pendingOrders = $this->paymentService->getPendingOrders();
        if(count($pendingOrders) > 0){
            foreach($pendingOrders as $order){
                try{
                    $result[] = $this->paymentService->checkOrderStatus($order);
                }
                catch (\Exception $e) {
                    $this->logger->error("Could not update the order status:". $e->getMessage() . 'Error Code:' . $e->getCode());
                }
            }


        }
        $this->logger->info('cron ran successfully');
    }
}

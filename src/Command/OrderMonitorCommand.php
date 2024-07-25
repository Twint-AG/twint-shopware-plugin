<?php

declare(strict_types=1);

namespace Twint\Command;

use Exception;
use Shopware\Core\Checkout\Order\OrderEntity;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twint\Core\Service\OrderService;
use Twint\Core\Service\PaymentService;
use Twint\Sdk\Value\Order;
use Twint\TwintPayment;

class OrderMonitorCommand extends Command
{
    private PaymentService $paymentService;

    private OrderService $orderService;

    public function __construct(PaymentService $paymentService, OrderService $orderService)
    {
        $this->paymentService = $paymentService;
        $this->orderService = $orderService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('twint:order-monitor:scan');
        $this->setDescription('Check all pending TWINT orders for updates');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $style->info('Start scanning all pending TWINT orders for updates');
        $pendingOrders = $this->orderService->getPendingOrders();
        if (count($pendingOrders) > 0) {
            $style->info(sprintf('These total: %d TWINT orders will be processed', count($pendingOrders)));
            foreach ($pendingOrders as $order) {
                /** @var OrderEntity $order */
                $style->info('Process for order ' . $order->getOrderNumber());
                try {
                    $twintOrder = $this->paymentService->checkOrderStatus($order);
                    if ($twintOrder instanceof Order) {
                        $style->success(
                            sprintf('TWINT order "%s" was updated successfully!', $order->getOrderNumber())
                        );
                    }
                } catch (Exception $e) {
                    $style->error(
                        sprintf(
                            'TWINT order status cannot be updated: %s with error code: %s',
                            $e->getMessage(),
                            $e->getCode()
                        )
                    );
                }
            }
        }
        return TwintPayment::EXIT_CODE_SUCCESS;
    }
}

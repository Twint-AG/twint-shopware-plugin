<?php

declare(strict_types=1);

namespace Twint\Command;

use Exception;
use Shopware\Core\Checkout\Order\OrderEntity;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twint\Core\Service\PaymentService;
use Twint\TwintPayment;

final class OrderMonitorCommand extends Command
{
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('twint:order-monitor:scan');
        $this->setDescription('Scan all pending orders for checking');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $style->info('Start to scan all pending orders for checking');
        $pendingOrders = $this->paymentService->getPendingOrders();
        if (count($pendingOrders) > 0) {
            $style->info('' . sprintf('These total: %d orders are going to process soon !', count($pendingOrders)));
            foreach ($pendingOrders as $order) {
                if ($order instanceof OrderEntity) {
                    $style->info('Process for order ' . $order->getOrderNumber());
                    try {
                        $twintOrder = $this->paymentService->checkOrderStatus($order);
                        if ($twintOrder) {
                            $style->success('Update order ' . $order->getOrderNumber() . ' successful!');
                        }
                    } catch (Exception $e) {
                        $style->error(
                            'Could not update the order status:' . $e->getMessage() . 'Error Code:' . $e->getCode()
                        );
                    }
                }
            }
        }
        return TwintPayment::EXIT_CODE_SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace Twint\Core\Service;

use Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\OrderEntity;

class OrderMonitoringService
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly PaymentService $paymentService,
    ) {
    }

    public function monitor(LoggerInterface $logger = null): void
    {
        $orders = $this->orderService->getPendingOrders();
        if (count($orders) > 0) {
            /** @var OrderEntity $order */
            foreach ($orders as $order) {
                try {
                    $this->paymentService->checkOrderStatus($order);
                } catch (Exception $e) {
                    $logger?->error(
                        sprintf(
                            'TWINT order status cannot be updated: %s with error code: %s',
                            $e->getMessage(),
                            $e->getCode()
                        )
                    );
                }
            }
        }
    }
}

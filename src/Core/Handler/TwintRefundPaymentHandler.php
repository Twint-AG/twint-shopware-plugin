<?php

declare(strict_types=1);

namespace Twint\Core\Handler;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransactionCaptureRefund\OrderTransactionCaptureRefundStateHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\RefundPaymentHandlerInterface;
use Shopware\Core\Framework\Api\ApiException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Twint\Core\Service\PaymentService;
use Twint\Sdk\Value\Order;

/**
 * @internal
 */
#[Package('checkout')]
class TwintRefundPaymentHandler implements RefundPaymentHandlerInterface
{
    public function __construct(
        private readonly OrderTransactionCaptureRefundStateHandler $stateHandler,
        private readonly PaymentService $paymentService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function refund(string $refundId, Context $context): void
    {
        $this->stateHandler->complete($refundId, $context);
        $order = $this->paymentService->getOrder($refundId, $context);
        try {
            $twintOrder = $this->paymentService->reverseOrder($order);
            if ($twintOrder instanceof Order) {
                $this->logger->info(
                    sprintf('TWINT order "%s" is reversed successfully!', $order->getOrderNumber())
                );
            }
        } catch (ApiException $e) {
            $this->logger->warning($e->getMessage());
        }
    }
}

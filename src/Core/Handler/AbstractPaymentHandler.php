<?php

declare(strict_types=1);

namespace Twint\Core\Handler;

use Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\RefundPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Api\ApiException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Twint\Core\Service\OrderService;
use Twint\Core\Service\PairingService;
use Twint\Core\Service\PaymentService;
use Twint\Core\Util\CryptoHandler;
use Twint\Sdk\Value\Order;

abstract class AbstractPaymentHandler implements AsynchronousPaymentHandlerInterface, RefundPaymentHandlerInterface
{
    public function __construct(
        private readonly OrderTransactionStateHandler $transactionStateHandler,
        private readonly PaymentService $paymentService,
        private readonly CryptoHandler $cryptoService,
        private readonly RouterInterface $router,
        private readonly LoggerInterface $logger,
        private readonly OrderService $orderService,
        private readonly PairingService $pairingService,
    ) {
    }

    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        // Method that sends the return URL to the external gateway and gets a redirect URL back
        try {
            $res = $this->paymentService->createOrder($transaction);
            $this->transactionStateHandler->process(
                $transaction->getOrderTransaction()
                    ->getId(),
                $salesChannelContext->getContext()
            );

            $pairing = $this->pairingService->create($res, $transaction->getOrder(), $salesChannelContext);
        } catch (Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $transaction->getOrderTransaction()
                    ->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }

        return new RedirectResponse($this->router->generate('frontend.twint.waiting', [
            'pairingId' => $this->cryptoService->hash($pairing->getId()),
        ]));
    }

    public function finalize(
        AsyncPaymentTransactionStruct $transaction,
        Request $request,
        SalesChannelContext $salesChannelContext
    ): void {
        $transactionId = $transaction->getOrderTransaction()
            ->getId();

        // Example check if the user canceled. Might differ for each payment provider
        if ($request->query->getBoolean('cancel')) {
            throw PaymentException::customerCanceled($transactionId, 'Customer canceled the payment');
        }

        // Example check for the actual status of the payment. Might differ for each payment provider
        $paymentState = $request->query->getAlpha('status');

        $context = $salesChannelContext->getContext();
        if ($paymentState === 'completed') {
            // Payment completed, set transaction status to "paid"
            $this->transactionStateHandler->paid($transaction->getOrderTransaction()->getId(), $context);
        } else {
            // Payment not completed, set transaction status to "open"
            $this->transactionStateHandler->reopen($transaction->getOrderTransaction()->getId(), $context);
        }
    }

    public function refund(string $refundId, Context $context): void
    {
        $refund = $this->orderService->getOrder($refundId, $context);
        if ($refund->getAmountTotal() > 100.00) {
            // this will stop the refund process and set the refunds state to `failed`
            throw PaymentException::refundInvalidTransition($refund->getId(), 'Refunds over 100 € are not allowed');
        }
        try {
            $twintOrder = $this->paymentService->reverseOrder($refund);
            if ($twintOrder instanceof Order) {
                $this->logger->info(sprintf('TWINT order "%s" is refund successfully!', $refund->getOrderNumber()));
                $this->transactionStateHandler->refundPartially($refundId, $context);
            } else {
                throw PaymentException::refundInvalidTransition(
                    $refund->getId(),
                    'An error occurred during the communication with external payment gateway' . PHP_EOL
                );
            }
        } catch (ApiException $e) {
            $this->logger->warning($e->getMessage());
        }
    }
}

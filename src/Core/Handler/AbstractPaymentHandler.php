<?php

declare(strict_types=1);

namespace Twint\Core\Handler;

use Exception;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\RefundPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Twint\Core\Service\PairingService;
use Twint\Core\Service\PaymentService;
use Twint\Core\Util\CryptoHandler;

abstract class AbstractPaymentHandler implements AsynchronousPaymentHandlerInterface, RefundPaymentHandlerInterface
{
    public function __construct(
        private readonly OrderTransactionStateHandler $transactionStateHandler,
        private readonly PaymentService $paymentService,
        private readonly CryptoHandler $cryptoService,
        private readonly RouterInterface $router,
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
    }
}

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
use Twint\Core\Service\PaymentService;
use Twint\Core\Util\CryptoHandler;
use Twint\Sdk\Value\Order;
use Twint\Util\OrderCustomFieldInstaller;

abstract class AbstractPaymentHandler implements AsynchronousPaymentHandlerInterface, RefundPaymentHandlerInterface
{
    private OrderTransactionStateHandler $transactionStateHandler;

    private PaymentService $paymentService;

    private OrderService $orderService;

    private CryptoHandler $cryptoService;

    private RouterInterface $router;

    private LoggerInterface $logger;

    public function __construct(OrderTransactionStateHandler $transactionStateHandler, PaymentService $paymentService, CryptoHandler $cryptoService, RouterInterface $router, LoggerInterface $logger, OrderService $orderService)
    {
        $this->transactionStateHandler = $transactionStateHandler;
        $this->paymentService = $paymentService;
        $this->cryptoService = $cryptoService;
        $this->router = $router;
        $this->logger = $logger;
        $this->orderService = $orderService;
    }

    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        // Method that sends the return URL to the external gateway and gets a redirect URL back
        try {
            $twintOrder = $this->paymentService->createOrder($transaction);
            $this->transactionStateHandler->process(
                $transaction->getOrderTransaction()
                    ->getId(),
                $salesChannelContext->getContext()
            );
            //update API response for order
            $orderCustomFields = $transaction->getOrder()
                ->getCustomFields();
            $twintOrderJson = json_encode($twintOrder);
            if ($twintOrderJson) {
                $twintApiArray = json_decode($twintOrderJson, true);
                $orderCustomFields[OrderCustomFieldInstaller::TWINT_API_RESPONSE_CUSTOM_FIELD] = json_encode(
                    $twintApiArray
                );
                $this->paymentService->updateOrderCustomField($transaction->getOrder()->getId(), $orderCustomFields);
            }
        } catch (Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $transaction->getOrderTransaction()
                    ->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }
        // Redirect to external gateway
        if ($transaction->getOrder()->getOrderNumber() !== null && $transaction->getOrder()->getOrderNumber() !== '' && $transaction->getOrder()->getOrderNumber() !== '0') {
            $hashOrderNumber = $this->cryptoService->hash($transaction->getOrder()->getOrderNumber());
            return new RedirectResponse($this->router->generate('frontend.twint.waiting', [
                'orderNumber' => $hashOrderNumber,
            ]));
        }
        return new RedirectResponse('/');
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

<?php

declare(strict_types=1);

namespace Twint\Core\Handler;

use Exception;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Twint\Core\Service\PaymentService;
use Twint\Core\Util\CryptoHandler;
use Twint\Util\OrderCustomFieldInstaller;

abstract class AbstractPaymentHandler implements AsynchronousPaymentHandlerInterface
{
    private OrderTransactionStateHandler $transactionStateHandler;

    private PaymentService $paymentService;

    private CryptoHandler $cryptoService;

    private RouterInterface $router;

    public function __construct(OrderTransactionStateHandler $transactionStateHandler, PaymentService $paymentService, CryptoHandler $cryptoService, RouterInterface $router)
    {
        $this->transactionStateHandler = $transactionStateHandler;
        $this->paymentService = $paymentService;
        $this->cryptoService = $cryptoService;
        $this->router = $router;
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
            $twintApiArray = $this->paymentService->parseTwintOrderToArray($twintOrder);
            $orderCustomFields[OrderCustomFieldInstaller::TWINT_API_RESPONSE_CUSTOM_FIELD] = json_encode(
                $twintApiArray
            );
            $this->paymentService->updateOrderCustomField($transaction->getOrder()->getId(), $orderCustomFields);
        } catch (Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $transaction->getOrderTransaction()
                    ->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }
        // Redirect to external gateway
        if (!empty($transaction->getOrder()->getOrderNumber())) {
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
            throw PaymentException::customerCanceled(
                $transactionId,
                'Customer canceled the payment on the PayPal page'
            );
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
}

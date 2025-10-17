<?php

declare(strict_types=1);

namespace Twint\Core\Handler\V67;

use Exception;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\RefundPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Twint\Core\Service\PairingService;
use Twint\Core\Service\PaymentService;
use Twint\Core\Util\CryptoHandler;
use function assert;

abstract class AbstractPaymentMethodHandler extends AbstractPaymentHandler
{
    public function __construct(
        private readonly OrderTransactionStateHandler $transactionStateHandler,
        private readonly PaymentService $paymentService,
        private readonly CryptoHandler $cryptoService,
        private readonly RouterInterface $router,
        private readonly PairingService $pairingService,
        private readonly EntityRepository $orderTransactionRepository,
        private readonly AbstractSalesChannelContextFactory $salesChannelContextFactory,
    ) {
    }

    public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
    {
        return $type === PaymentHandlerType::REFUND;
    }

    public function pay(
        Request $request,
        PaymentTransactionStruct $transaction,
        Context $context,
        ?Struct $validateStruct,
    ): RedirectResponse {
        // Method that sends the return URL to the external gateway and gets a redirect URL back
        try {
            [$orderTransaction, $order] = $this->fetchOrderTransaction($transaction->getOrderTransactionId(), $context);
            $res = $this->paymentService->createOrder($orderTransaction);
            $this->transactionStateHandler->process($transaction->getOrderTransactionId(), $context);
            $salesChannelContext = $this->getSalesChannelContext($order->getSalesChannel());
            $pairing = $this->pairingService->create($res, $order, $salesChannelContext);
        } catch (Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $transaction->getOrderTransactionId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }

        return new RedirectResponse($this->router->generate('frontend.twint.waiting', [
            'pairingId' => $this->cryptoService->hash($pairing->getId()),
        ]));
    }

    public function finalize(Request $request, PaymentTransactionStruct $transaction, Context $context): void
    {
        $transactionId = $transaction->getOrderTransactionId();

        // Example check if the user canceled. Might differ for each payment provider
        if ($request->query->getBoolean('cancel')) {
            throw PaymentException::customerCanceled($transactionId, 'Customer canceled the payment');
        }

        // Example check for the actual status of the payment. Might differ for each payment provider
        $paymentState = $request->query->getAlpha('status');

        if ($paymentState === 'completed') {
            // Payment completed, set transaction status to "paid"
            $this->transactionStateHandler->paid($transaction->getOrderTransactionId(), $context);
        } else {
            // Payment not completed, set transaction status to "open"
            $this->transactionStateHandler->reopen($transaction->getOrderTransactionId(), $context);
        }
    }

    public function refund(RefundPaymentTransactionStruct $transaction, Context $context): void
    {
    }

    private function fetchOrderTransaction(string $transactionId, Context $context): array
    {
        $criteria = new Criteria([$transactionId]);
        $criteria->addAssociation('order.billingAddress.country');
        $criteria->addAssociation('order.currency');
        $criteria->addAssociation('order.deliveries.shippingOrderAddress.country');
        $criteria->addAssociation('order.lineItems');
        $criteria->addAssociation('order.orderCustomer.customer');
        $criteria->addAssociation('order.salesChannel');

        $transaction = $this->orderTransactionRepository->search($criteria, $context)
            ->first();
        assert($transaction instanceof OrderTransactionEntity);

        $order = $transaction->getOrder();
        assert($order instanceof OrderEntity);

        return [$transaction, $order];
    }

    public function getSalesChannelContext(SalesChannelEntity $salesChannel): SalesChannelContext
    {
        return $this->salesChannelContextFactory->create(Uuid::randomHex(), $salesChannel->getId());
    }
}

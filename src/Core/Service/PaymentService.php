<?php

declare(strict_types=1);

namespace Twint\Core\Service;

use Exception;
use Shopware\Core\Checkout\Cart\Price\CashRounding;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\SumResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Twint\Core\DataAbstractionLayer\Entity\Pairing\PairingEntity;
use Twint\Core\Factory\ClientBuilder;
use Twint\Core\Handler\TransactionLog\TransactionLogWriterInterface;
use Twint\Core\Model\ApiResponse;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\OrderId;
use Twint\Sdk\Value\OrderStatus;
use Twint\Sdk\Value\UnfiledMerchantTransactionReference;
use Twint\Sdk\Value\Uuid;
use function Psl\Type\string;

class PaymentService
{
    private Context $context;

    public function __construct(
        private readonly EntityRepository $reversalHistoryRepository,
        private readonly OrderTransactionStateHandler $transactionStateHandler,
        private readonly ClientBuilder $clientBuilder,
        private readonly TransactionLogWriterInterface $transactionLogWriter,
        private readonly CashRounding $rounding,
        private readonly OrderService $orderService,
        private readonly ApiService $apiService,
    ) {
        $this->context = new Context(new SystemSource());
    }

    public function createOrder(AsyncPaymentTransactionStruct $transaction): ApiResponse
    {
        $order = $transaction->getOrder();
        if (!$order->getCurrency() instanceof CurrencyEntity) {
            throw PaymentException::asyncProcessInterrupted(
                $transaction->getOrderTransaction()
                    ->getId(),
                'Missing order or currency' . PHP_EOL
            );
        }
        $currency = $order->getCurrency()
            ->getIsoCode();

        $client = $this->clientBuilder->build($order->getSalesChannelId());
        try {
            /** @var non-empty-string $orderId */
            $orderId = $order->getOrderNumber() ?? $order->getId();

            return $this->apiService->call($client, 'startOrder', [
                new UnfiledMerchantTransactionReference($orderId),
                new Money($currency, $order->getAmountTotal()),
            ], true, static function (array $log, mixed $return) use ($order, $transaction) {
                if ($return instanceof Order) {
                    $log['pairingId'] = $return->id()->__toString();
                    $log['orderId'] = $order->getId();
                    $log['orderVersionId'] = $order->getVersionId();
                    $log['paymentStateId'] = $transaction->getOrderTransaction()->getStateId();
                    $log['orderStateId'] = $transaction->getOrder()->getStateId();
                    $log['transactionId'] = $transaction->getOrderTransaction()->getId();
                }

                return $log;
            });
        } catch (Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $transaction->getOrderTransaction()
                    ->getId(),
                'An error occurred during the communication with API gateway' . PHP_EOL . $e->getMessage()
            );
        }
    }

    public function reverseOrder(OrderEntity $order, float $amount = 0): ?Order
    {
        try {
            if (($pairing = $this->orderService->getPairing($order->getId())) instanceof PairingEntity) {
                if ($amount <= 0 || $amount > $pairing->getAmount()) {
                    $amount = $pairing->getAmount();
                }
                $orderTransactionId = $order->getTransactions()?->first()?->getId();
                $currency = $order->getCurrency()?->getIsoCode();
                if ($orderTransactionId && ($currency !== null && $currency !== '' && $currency !== '0') && $pairing->getAmount() > 0) {
                    $client = $this->clientBuilder->build($order->getSalesChannelId());
                    $innovations = $client->flushInvocations();
                    $this->transactionLogWriter->writeReserveOrderLog(
                        $order->getId(),
                        $order->getVersionId(),
                        $order->getTransactions()
                            ?->first()
                            ?->getStateId() ?? '',
                        $order
                            ->getStateId(),
                        $order->getTransactions()?->first()?->getId() ?? '',
                        $innovations,
                        $this->context
                    );
                    $reversalIndex = $this->getReversalIndex($order->getId());
                    $reversalId = 'R-' . $pairing->getId() . '-' . $reversalIndex;
                    $twintOrder = $client->reverseOrder(
                        new UnfiledMerchantTransactionReference($reversalId),
                        new OrderId(new Uuid($pairing->getId())),
                        new Money($currency, $amount)
                    );
                    if ($twintOrder->status()->equals(OrderStatus::SUCCESS())) {
                        return $twintOrder;
                    }
                }
            }
            return null;
        } catch (Exception $e) {
            throw PaymentException::asyncProcessInterrupted($orderTransactionId ?? '', $e->getMessage());
        } finally {
            $order = $this->orderService->getOrder($order->getId(), $this->context);
            $innovations = empty($client) ? [] : $client->flushInvocations();
            $this->transactionLogWriter->writeReserveOrderLog(
                $order->getId(),
                $order->getVersionId(),
                $order->getTransactions()
                    ?->first()
                    ?->getStateId() ?? '',
                $order
                    ->getStateId(),
                $order->getTransactions()?->first()?->getId() ?? '',
                $innovations,
                $this->context
            );
        }
    }

    /**
     * @throws Exception
     */
    public function monitorOrder(PairingEntity $pairing): ?Order
    {
        $order = $this->orderService->getOrder($pairing->getOrderId() ?? '');

        try {
            $client = $this->clientBuilder->build($pairing->getSalesChannelId());

            return $client->monitorOrder(new OrderId(new Uuid($pairing->getId())));
        } catch (Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $order->getId(),
                'An error occurred during the communication with API gateway' . PHP_EOL . $e->getMessage()
            );
        } finally {
            $order = $this->orderService->getOrder($order->getId(), $this->context);
            $innovations = empty($client) ? [] : $client->flushInvocations();
            $this->transactionLogWriter->writeObjectLog(
                $order->getId(),
                $order->getVersionId(),
                $order->getTransactions()
                    ?->first()
                    ?->getStateId() ?? '',
                $order->getStateId(),
                $order->getTransactions()?->first()?->getId() ?? '',
                $innovations,
                $this->context
            );
        }
    }

    public function getPayLinks(string $token, string $salesChannelId): array
    {
        $payLinks = [];
        try {
            $client = $this->clientBuilder->build($salesChannelId);
            $device = $client->detectDevice(string()->assert($_SERVER['HTTP_USER_AGENT'] ?? ''));
            if ($device->isAndroid()) {
                $payLinks['android'] = 'intent://payment#Intent;action=ch.twint.action.TWINT_PAYMENT;scheme=twint;S.code=' . $token . ';S.startingOrigin=EXTERNAL_WEB_BROWSER;S.browser_fallback_url=;end';
            } elseif ($device->isIos()) {
                $appList = [];
                $apps = $client->getIosAppSchemes();
                foreach ($apps as $app) {
                    $appList[] = [
                        'name' => $app->displayName(),
                        'link' => $app->scheme() . 'applinks/?al_applink_data={"app_action_type":"TWINT_PAYMENT","extras": {"code": "' . $token . '",},"referer_app_link": {"target_url": "", "url": "", "app_name": "EXTERNAL_WEB_BROWSER"}, "version": "6.0"}',
                    ];
                }
                $payLinks['ios'] = $appList;
            }
        } catch (Exception $e) {
            return $payLinks;
        }
        return $payLinks;
    }

    public function getReversals(string $orderId): EntityCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));
        $criteria->addAssociation('order');
        return $this->reversalHistoryRepository->search($criteria, $this->context)
            ->getEntities();
    }

    public function getReversalIndex(string $orderId): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));
        $index = $this->reversalHistoryRepository->search($criteria, $this->context)
            ->count();
        return $index + 1;
    }

    public function getTotalReversal(string $orderId): float
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));
        $criteria->addAggregation(new SumAggregation('totalReversal', 'amount'));

        /** @var SumResult $totalReversal */
        $totalReversal = $this->reversalHistoryRepository->aggregate($criteria, $this->context)
            ->get('totalReversal');
        return $totalReversal->getSum() ?? -1;
    }

    public function changePaymentStatus(OrderEntity $order, float $amount = 0): void
    {
        $orderTransactionId = $order->getTransactions()?->first()?->getId();
        $lastTransactionStateName = $order->getTransactions()?->first()
            ?->getStateMachineState()
            ?->getTechnicalName();
        if (empty($orderTransactionId)) {
            return;
        }
        if (($pairing = $this->orderService->getPairing($order->getId())) instanceof PairingEntity) {
            $amountMoney = new Money($order->getCurrency()?->getIsoCode() ?? Money::CHF, $pairing->getAmount());
            $totalReversal = $this->rounding->mathRound(
                $amount + $this->getTotalReversal($order->getId()),
                $order->getItemRounding() ?? new CashRoundingConfig(2, 0.01, true)
            );
            $totalReversalMoney = new Money($order->getCurrency()?->getIsoCode() ?? Money::CHF, $totalReversal);
            if ($amountMoney->compare(
                $totalReversalMoney
            ) === 0 && $lastTransactionStateName !== OrderTransactionStates::STATE_REFUNDED) {
                $this->transactionStateHandler->refund($orderTransactionId, $this->context);
            } elseif ($lastTransactionStateName !== OrderTransactionStates::STATE_PARTIALLY_REFUNDED) {
                $this->transactionStateHandler->refundPartially($orderTransactionId, $this->context);
            }
        }
    }

    public function getNextAction(OrderEntity $order): string
    {
        $orderTransactionId = $order->getTransactions()?->first()?->getId();
        $lastTransactionStateName = $order->getTransactions()?->first()
            ?->getStateMachineState()
            ?->getTechnicalName();
        if (empty($orderTransactionId)) {
            return '';
        }

        if (($pairing = $this->orderService->getPairing($order->getId())) instanceof PairingEntity) {
            $amountMoney = new Money($order->getCurrency()?->getIsoCode() ?? Money::CHF, $pairing->getAmount());
            $totalReversalMoney = new Money($order->getCurrency()?->getIsoCode() ?? Money::CHF, $this->getTotalReversal(
                $order->getId()
            ));
            if ($amountMoney->compare(
                $totalReversalMoney
            ) === 0 && $lastTransactionStateName !== OrderTransactionStates::STATE_REFUNDED) {
                return StateMachineTransitionActions::ACTION_REFUND;
            } elseif ($lastTransactionStateName !== OrderTransactionStates::STATE_PARTIALLY_REFUNDED) {
                return StateMachineTransitionActions::ACTION_REFUND_PARTIALLY;
            }
        }

        return '';
    }
}

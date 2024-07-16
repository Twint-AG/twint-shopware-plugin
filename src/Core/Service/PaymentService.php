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
use Shopware\Core\Framework\Uuid\Uuid as ShopwareUuid;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Twint\Core\Factory\ClientBuilder;
use Twint\Core\Handler\TransactionLog\TransactionLogWriterInterface;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\OrderId;
use Twint\Sdk\Value\OrderStatus;
use Twint\Sdk\Value\UnfiledMerchantTransactionReference;
use Twint\Sdk\Value\Uuid;
use Twint\Util\OrderCustomFieldInstaller;
use function Psl\Type\string;

class PaymentService
{
    private Context $context;

    public function __construct(
        private readonly EntityRepository $reversalHistoryRepository,
        private readonly OrderTransactionStateHandler $transactionStateHandler,
        private readonly ClientBuilder $clientBuilder,
        private readonly TransactionLogWriterInterface $transactionLogWriter,
        private readonly StockManagerInterface $stockManager,
        private readonly CashRounding $rounding,
        private readonly OrderService $orderService
    ) {
        $this->context = new Context(new SystemSource());
    }

    public function createOrder(AsyncPaymentTransactionStruct $transaction): Order
    {
        $order = $transaction->getOrder();
        if (!$order instanceof OrderEntity || $order->getCurrency() === null) {
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
            /** @var non-empty-string $orderId * */
            $orderId = empty($order->getId()) ? ShopwareUuid::randomHex() : $order->getId();
            /** @var Order * */
            return $client->startOrder(
                new UnfiledMerchantTransactionReference($orderId),
                new Money($currency, $order->getAmountTotal())
            );
        } catch (Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $transaction->getOrderTransaction()
                    ->getId(),
                'An error occurred during the communication with API gateway' . PHP_EOL . $e->getMessage()
            );
        } finally {
            $this->transactionLogWriter->writeObjectLog(
                $order->getId(),
                $order->getVersionId(),
                $transaction->getOrderTransaction()
                    ->getStateId(),
                $transaction->getOrder()
                    ->getStateId(),
                $transaction->getOrderTransaction()
                    ->getId(),
                $client->flushInvocations()
            );
        }
    }

    public function checkOrderStatus(OrderEntity $order): ?Order
    {
        try {
            if ($this->orderService->isTwintOrder($order)) {
                $twintApiResponse = json_decode(
                    $order->getCustomFields()[OrderCustomFieldInstaller::TWINT_API_RESPONSE_CUSTOM_FIELD] ?? '{}',
                    true
                );
                $client = $this->clientBuilder->build($order->getSalesChannelId());
                /** @var Order * */
                $twintOrder = $client->monitorOrder(new OrderId(new Uuid($twintApiResponse['id'])));
                $transactionId = $order->getTransactions()?->first()?->getId() ?? null;
                if ($transactionId === null) {
                    throw new Exception('Missing transaction ID for this order:' . $order->getId() . PHP_EOL);
                }
                if ($twintOrder->status()->equals(OrderStatus::SUCCESS())) {
                    $this->transactionStateHandler->paid($transactionId, $this->context);
                } elseif ($twintOrder->status()->equals(OrderStatus::FAILURE())) {
                    $this->transactionStateHandler->cancel($transactionId, $this->context);
                }
                return $twintOrder;
            }
            return null;
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
                $transactionId ?? '',
                $innovations
            );
        }
    }

    public function reverseOrder(OrderEntity $order, float $amount = 0): ?Order
    {
        try {
            if (($twintOrder = $this->orderService->getTwintOrder($order)) instanceof Order) {
                if ($amount <= 0 || $amount > $twintOrder->amount()->amount()) {
                    $amount = $twintOrder->amount()
                        ->amount();
                }
                $orderTransactionId = $order->getTransactions()?->first()?->getId();
                $currency = $order->getCurrency()?->getIsoCode();
                if ($orderTransactionId && !empty($currency) && $twintOrder->amount()->amount() > 0) {
                    $client = $this->clientBuilder->build($order->getSalesChannelId());
                    /** @var Order * */
                    $twintOrder = $client->monitorOrder($twintOrder->id());
                    if ($twintOrder->status()->equals(OrderStatus::SUCCESS())) {
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
                            $innovations
                        );
                        $reversalIndex = $this->getReversalIndex($order->getId());
                        $reversalId = 'R-' . $twintOrder->id()->__toString() . '-' . $reversalIndex;
                        $twintOrder = $client->reverseOrder(
                            new UnfiledMerchantTransactionReference($reversalId),
                            $twintOrder->id(),
                            new Money($currency, $amount)
                        );
                        if ($twintOrder->status()->equals(OrderStatus::SUCCESS())) {
                            return $twintOrder;
                        }
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
                $innovations
            );
        }
    }

    public function monitorOrder(OrderEntity $order): ?Order
    {
        try {
            if ($this->orderService->isTwintOrder($order)) {
                $twintApiResponse = json_decode(
                    $order->getCustomFields()[OrderCustomFieldInstaller::TWINT_API_RESPONSE_CUSTOM_FIELD] ?? '{}',
                    true
                );
                $client = $this->clientBuilder->build($order->getSalesChannelId());
                /** @var Order * */
                $twintOrder = $client->monitorOrder(new OrderId(new Uuid($twintApiResponse['id'])));
                if ($twintOrder instanceof Order) {
                    return $twintOrder;
                }
            }
            return null;
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
                $innovations
            );
        }
    }

    public function updateOrderCustomField(string $orderId, array $customFields): void
    {
        $this->orderService->updateOrderCustomField($orderId, $customFields);
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

    public function changePaymentStatus(OrderEntity $order, float $amount = 0, bool $stockRecovery = false): void
    {
        $orderTransactionId = $order->getTransactions()?->first()?->getId();
        $lastTransactionStateName = $order->getTransactions()?->first()
            ?->getStateMachineState()
            ?->getTechnicalName();
        if (empty($orderTransactionId)) {
            return;
        }
        if (($twintOrder = $this->orderService->getTwintOrder($order)) instanceof Order) {
            $amountMoney = new Money(
                $order->getCurrency()?->getIsoCode() ?? Money::CHF,
                $twintOrder->amount()
                    ->amount()
            );
            $totalReversal = $this->rounding->mathRound(
                $amount + $this->getTotalReversal($order->getId()),
                $order->getItemRounding() ?? new CashRoundingConfig(2, 0.01, true)
            );
            $totalReversalMoney = new Money($order->getCurrency()?->getIsoCode() ?? Money::CHF, $totalReversal);
            if ($amountMoney->compare(
                $totalReversalMoney
            ) === 0 && $lastTransactionStateName !== OrderTransactionStates::STATE_REFUNDED) {
                $this->transactionStateHandler->refund($orderTransactionId, $this->context);
                if ($stockRecovery) {
                    $lineItems = $order->getLineItems();
                    if ($lineItems) {
                        foreach ($lineItems as $lineItem) {
                            $this->stockManager->increaseStock($lineItem, $lineItem->getQuantity());
                        }
                    }
                }
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
        if (($twintOrder = $this->orderService->getTwintOrder($order)) instanceof Order) {
            $amountMoney = new Money(
                $order->getCurrency()?->getIsoCode() ?? Money::CHF,
                $twintOrder->amount()
                    ->amount()
            );
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

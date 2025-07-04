<?php

declare(strict_types=1);

namespace Twint\Reporting\Service;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Twint\Core\Handler\TwintExpressPaymentHandler;
use Twint\Core\Handler\TwintRegularPaymentHandler;
use Twint\Core\Service\SettingServiceInterface;
use Twint\Core\Setting\Settings;
use Twint\Sdk\Value\InstallSource;

class TransactionReportService
{
    public function __construct(
        private readonly EntityRepository $transactionReportRepository,
        private readonly SettingServiceInterface $settingService,
        private readonly Connection $connection,
    ) {
    }

    public function processTransactionReport(OrderEntity $order, Context $context, ?float $refundPrice = 0): void
    {
        $transaction = $order->getTransactions()?->first();
        if (!$transaction) {
            return;
        }
        $handlerId = $transaction->getPaymentMethod()?->getHandlerIdentifier();
        $setting = $this->settingService->getSetting($order->getSalesChannelId());
        $isTestMode = $setting->isTestMode();

        if (!isset($handlerId) || $isTestMode || Settings::INSTALL_SOURCE !== InstallSource::STORE || $context->getVersionId() !== Defaults::LIVE_VERSION || !in_array(
                $handlerId,
                [TwintExpressPaymentHandler::class, TwintRegularPaymentHandler::class],
                true
            )) {
            return;
        }
        $totalPrice = $refundPrice > 0 ? -1 * $refundPrice : $transaction->getAmount()
            ->getTotalPrice();
        $id = $totalPrice > 0
            ? $this->getPaidTransactionReportId($order, $context) ?? Uuid::randomHex()
            : Uuid::randomHex();
        $this->transactionReportRepository->upsert([[
            'id' => $id,
            'orderTransactionId' => $transaction->getId(),
            'currencyIso' => $order->getCurrency()?->getIsoCode(),
            'totalPrice' => round($totalPrice, 2),
        ]], $context);
    }

    public function getPaidTransactionReportId(OrderEntity $order, Context $context): ?string
    {
        $transaction = $order->getTransactions()?->first();
        if (!$transaction) {
            return null;
        }
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderTransactionId', $transaction->getId()));
        $criteria->addFilter(new RangeFilter('totalPrice', [
            'gt' => 0,
        ]));

        return $this->transactionReportRepository->search($criteria, $context)
            ->first()?->getUniqueIdentifier();
    }

    public function getPaidTransactionReportIds(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new RangeFilter('totalPrice', [
            'gt' => 0,
        ]));

        $paidTransactionReportIds = $this->transactionReportRepository
            ->searchIds($criteria, $context)
            ->getIds();
        return array_column($paidTransactionReportIds, 'id');
    }

    public function getRefundTransactionReportIds(Context $context): array
    {
        $refundCriteria = new Criteria();
        $refundCriteria->addFilter(new RangeFilter('totalPrice', [
            'lt' => 0,
        ]));

        $refundTransactionReportIds = $this->transactionReportRepository
            ->searchIds($refundCriteria, $context)
            ->getIds();
        return array_column($refundTransactionReportIds, 'id');
    }

    /**
     * @param array<string> $transactionReportIds
     * @return array<int|string, mixed>
     */
    public function getAggregatedPaidTurnover(array $transactionReportIds): array
    {
        if (empty($transactionReportIds)) {
            return [];
        }

        return $this->connection->executeQuery(
            '
                SELECT tr.currency_iso, SUM(tr.total_price) as turnover
                    FROM twint_transaction_report as tr
                LEFT JOIN order_transaction as ot
                    ON tr.order_transaction_id = ot.id AND tr.order_transaction_version_id = ot.version_id
                LEFT JOIN state_machine_state as sms
                    ON ot.state_id = sms.id
                WHERE LOWER(HEX(tr.id)) in (:ids)
                GROUP BY tr.currency_iso
            ',
            [
                'ids' => $transactionReportIds,
            ],
            [
                'ids' => ArrayParameterType::STRING,
            ]
        )->fetchAllKeyValue();
    }

    /**
     * @param array<string> $refundTransactionReportIds
     * @return array<int|string, mixed>
     */
    public function getAggregatedRefundTurnover(array $refundTransactionReportIds): array
    {
        if (empty($refundTransactionReportIds)) {
            return [];
        }

        return $this->connection->executeQuery(
            '
                SELECT tr.currency_iso, SUM(tr.total_price) as turnover
                    FROM twint_transaction_report as tr
                LEFT JOIN order_transaction as ot
                    ON tr.order_transaction_id = ot.id AND tr.order_transaction_version_id = ot.version_id
                LEFT JOIN state_machine_state as sms
                    ON ot.state_id = sms.id
                WHERE LOWER(HEX(tr.id)) in (:ids)
                GROUP BY tr.currency_iso
            ',
            [
                'ids' => $refundTransactionReportIds,
            ],
            [
                'ids' => ArrayParameterType::STRING,
            ]
        )->fetchAllKeyValue();
    }

    /**
     * @param array<string> $transactionReportIds
     * @param array<string> $rejectedCurrencies
     */
    public function deleteReports(array $transactionReportIds, array $rejectedCurrencies): void
    {
        if (empty($transactionReportIds)) {
            return;
        }

        $sql = 'DELETE FROM `twint_transaction_report` WHERE LOWER(HEX(`id`)) IN (:ids)';
        $params = [
            'ids' => $transactionReportIds,
        ];
        $types = [
            'ids' => ArrayParameterType::STRING,
        ];

        if (!empty($rejectedCurrencies)) {
            $sql .= ' AND `currency_iso` NOT IN (:rejectedCurrencies)';
            $params['rejectedCurrencies'] = $rejectedCurrencies;
            $types['rejectedCurrencies'] = ArrayParameterType::STRING;
        }

        $this->connection->executeStatement($sql, $params, $types);
    }
}

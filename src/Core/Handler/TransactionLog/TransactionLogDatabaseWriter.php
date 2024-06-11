<?php

declare(strict_types=1);

namespace Twint\Core\Handler\TransactionLog;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Twint\Core\Setting\Settings;

class TransactionLogDatabaseWriter implements TransactionLogWriterInterface
{
    public function __construct(
        private readonly EntityRepository $repository
    ) {
    }

    public function write(
        string $orderId,
        string $paymentStateId,
        string $orderStateId,
        string $transactionId,
        string $request,
        string $response,
        array $soapRequest,
        array $soapResponse,
        string $exception
    ): void {
        $this->repository->create([
            [
                'orderId' => $orderId,
                'paymentStateId' => $paymentStateId,
                'orderStateId' => $orderStateId,
                'transactionId' => $transactionId,
                'request' => $request,
                'response' => $response,
                'soapRequest' => $soapRequest,
                'soapResponse' => $soapResponse,
                'exception' => $exception,
            ],
        ], Context::createDefaultContext());
    }

    public function writeObjectLog(
        string $orderId,
        string $paymentStateId,
        string $orderStateId,
        string $transactionId,
        array $invocations
    ): void {
        if ($invocations === []) {
            return;
        }
        $request = json_encode($invocations[0]->arguments());
        $exception = $invocations[0]->exception() ?? '';
        $response = json_encode($invocations[0]->returnValue());
        $soapMessages = $invocations[0]->messages();
        $soapRequests = [];
        $soapResponses = [];
        foreach ($soapMessages as $soapMessage) {
            $soapRequests[] = $soapMessage->request()->body();
            $soapResponses[] = $soapMessage->response()->body();
        }
        $record = [
            'orderId' => $orderId,
            'paymentStateId' => $paymentStateId,
            'orderStateId' => $orderStateId,
            'transactionId' => $transactionId,
            'request' => $request,
            'response' => $response,
            'soapRequest' => $soapRequests,
            'soapResponse' => $soapResponses,
            'exception' => $exception,
        ];
        if (!$this->checkDuplicatedTransactionLogInLastMinutes($record)) {
            $this->repository->create([$record], Context::createDefaultContext());
        }
    }

    public function checkDuplicatedTransactionLogInLastMinutes(array $record): bool
    {
        $lastTime = Settings::CHECK_DUPLICATED_TRANSACTION_LOG_FROM_MINUTES;
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('orderId', $record['orderId'] ?? ''),
            new EqualsFilter('paymentStateId', $record['paymentStateId'] ?? ''),
            new EqualsFilter('orderStateId', $record['orderStateId'] ?? ''),
            new EqualsFilter('transactionId', $record['transactionId'] ?? ''),
            new EqualsFilter('request', $record['request'] ?? ''),
            new EqualsFilter('response', $record['response'] ?? '')
        );
        /** @var int $time */
        $time = strtotime("-{$lastTime} minutes");
        $time = date('Y-m-d H:i:s', $time);
        $criteria->addFilter(
            new MultiFilter(
                MultiFilter::CONNECTION_OR,
                [
                    new RangeFilter('createdAt', [
                        RangeFilter::GT => $time,
                    ]),
                    new RangeFilter('updatedAt', [
                        RangeFilter::GT => $time,
                    ]),
                ]
            )
        );
        return (bool) $this->repository->search($criteria, Context::createDefaultContext())->first();
    }
}

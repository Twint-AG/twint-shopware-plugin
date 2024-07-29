<?php

declare(strict_types=1);

namespace Twint\Core\Handler\TransactionLog;

use Shopware\Core\Framework\Context;

interface TransactionLogWriterInterface
{
    public function write(
        ?string $orderId,
        ?string $orderVersionId,
        ?string $paymentStateId,
        ?string $orderStateId,
        string $transactionId,
        string $apiMethod,
        array $soapAction,
        string $request,
        string $response,
        array $soapRequest,
        array $soapResponse,
        string $exception,
        Context $context = null
    ): void;

    public function writeObjectLog(
        ?string $orderId,
        ?string $versionId,
        ?string $paymentStateId,
        ?string $orderStateId,
        string $transactionId,
        array $invocations,
        Context $context = null
    ): void;

    public function writeReserveOrderLog(
        ?string $orderId,
        ?string $versionId,
        ?string $paymentStateId,
        ?string $orderStateId,
        string $transactionId,
        array $invocations,
        Context $context = null
    ): void;
}

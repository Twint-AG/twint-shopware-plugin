<?php

declare(strict_types=1);

namespace Twint\Core\Handler\TransactionLog;

interface TransactionLogWriterInterface
{
    public function write(
        string $orderId,
        string $paymentStateId,
        string $orderStateId,
        string $transactionId,
        string $apiMethod,
        string $request,
        string $response,
        array $soapRequest,
        array $soapResponse,
        string $exception
    ): void;

    public function writeObjectLog(
        string $orderId,
        string $paymentStateId,
        string $orderStateId,
        string $transactionId,
        string $apiMethod,
        array $invocations
    ): void;

    public function writeReserveOrderLog(
        string $orderId,
        string $paymentStateId,
        string $orderStateId,
        string $transactionId,
        string $apiMethod,
        array $invocations
    ): void;
}

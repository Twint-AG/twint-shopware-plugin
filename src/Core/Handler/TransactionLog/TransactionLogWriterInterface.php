<?php
namespace Twint\Core\Handler\TransactionLog;

interface TransactionLogWriterInterface
{
    public function write(string $orderId, string $transactionId, string $request, string $response, string $soapRequest,
    string $soapResponse, string $exception): void;
}

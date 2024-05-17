<?php
namespace Twint\Core\Handler\TransactionLog;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class TransactionLogDatabaseWriter implements TransactionLogWriterInterface
{
    public function __construct(private readonly EntityRepository $repository)
    {
    }

    public function write(string $orderId, string $transactionId, string $request, string $response, string $soapRequest,
    string $soapResponse, string $exception): void
    {
        $this->repository->create([
            [
                'orderId' => $orderId,
                'transactionId' => $transactionId,
                'request' => $request,
                'response' => $response,
                'soapRequest' => $soapRequest,
                'soapResponse' => $soapResponse,
                'exception' => $exception,
            ],
        ], Context::createDefaultContext());
    }
}

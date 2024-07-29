<?php

declare(strict_types=1);

namespace Twint\Core\Handler\ReversalHistory;

use Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class ReversalHistoryDatabaseWriter implements ReversalHistoryWriterInterface
{
    public function __construct(
        private readonly EntityRepository $repository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function write(
        string $orderId,
        string $reversalId,
        float $amount,
        string $currency,
        string $reason,
        Context $context = null
    ): void {
        if (!$context instanceof Context) {
            $context = Context::createDefaultContext();
        }
        try {
            $this->repository->create([
                [
                    'orderId' => $orderId,
                    'reversalId' => $reversalId,
                    'amount' => $amount,
                    'currency' => $currency,
                    'reason' => $reason,
                ],
            ], $context);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}

<?php

declare(strict_types=1);

namespace Twint\Core\Handler\ReversalHistory;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class ReversalHistoryDatabaseWriter implements ReversalHistoryWriterInterface
{
    public function __construct(
        private readonly EntityRepository $repository
    ) {
    }

    public function write(string $orderId, string $reversalId, float $amount, string $currency, string $reason): void
    {
        $this->repository->create([
            [
                'orderId' => $orderId,
                'reversalId' => $reversalId,
                'amount' => $amount,
                'currency' => $currency,
                'reason' => $reason,
            ],
        ], Context::createDefaultContext());
    }
}

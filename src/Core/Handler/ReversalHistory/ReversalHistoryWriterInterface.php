<?php

declare(strict_types=1);

namespace Twint\Core\Handler\ReversalHistory;

use Shopware\Core\Framework\Context;

interface ReversalHistoryWriterInterface
{
    public function write(
        string $orderId,
        string $reversalId,
        float $amount,
        string $currency,
        string $reason,
        Context $context = null
    ): void;
}

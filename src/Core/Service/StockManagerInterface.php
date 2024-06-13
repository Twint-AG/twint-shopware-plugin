<?php

declare(strict_types=1);

namespace Twint\Core\Service;

use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;

interface StockManagerInterface
{
    public function increaseStock(OrderLineItemEntity $lineItem, int $quantity): void;
}

<?php

declare(strict_types=1);

namespace Twint\Core\DataAbstractionLayer\Entity\ReversalHistory;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class TwintReversalHistoryEntity extends Entity
{
    use EntityIdTrait;

    protected ?OrderEntity $order = null;

    protected string $orderId;

    protected string $reversalId;

    protected float $amount;

    protected string $currency;

    protected string $reason;

    public function getOrder(): ?OrderEntity
    {
        return $this->order;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getReversalId(): string
    {
        return $this->reversalId;
    }

    public function setReversalId(string $reversalId): void
    {
        $this->reversalId = $reversalId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }
}

<?php

declare(strict_types=1);

namespace Twint\Core\DataAbstractionLayer\Entity\Pairing;

use DateTimeInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Twint\Core\Setting\Settings;
use Twint\Sdk\Value\OrderStatus;

class PairingEntity extends Entity
{
    use EntityIdTrait;

    public const STATUS_DONE = 'DONE';

    public const STATUS_CANCELED = 'CANCELLED';

    protected ?Cart $cart = null;

    protected string $cartToken;

    protected string $status;

    protected ?string $pairingStatus = '';

    protected string $transactionStatus;

    protected bool $isExpress;

    protected string $token;

    protected ?string $shippingMethodId = null;

    protected ?string $customerId = null;

    protected ?ShippingMethodEntity $shippingMethod = null;

    protected ?SalesChannelEntity $salesChannel;

    protected ?CustomerEntity $customer;

    protected string $salesChannelId;

    protected ?object $customerData = null;

    protected ?string $orderId = null;

    protected float $amount;

    protected ?OrderEntity $order = null;

    protected int $version = 1;

    protected int $isOrdering;

    protected ?int $checkedAgo;

    protected ?int $createdAgo;

    protected ?DateTimeInterface $checkedAt = null;

    public function getCustomer(): ?CustomerEntity
    {
        return $this->customer;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function getOrder(): ?OrderEntity
    {
        return $this->order;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function getSalesChannel(): ?SalesChannelEntity
    {
        return $this->salesChannel;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(?Cart $value): void
    {
        $this->cart = $value;
    }

    public function getCartToken(bool $original = false): string
    {
        $parts = explode(':', $this->cartToken);

        return $original ? $parts[0] : $parts[1];
    }

    public function setCartToken(string $value): void
    {
        $this->cartToken = $value;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPairingStatus(): ?string
    {
        return $this->pairingStatus;
    }

    public function getTransactionStatus(): string
    {
        return $this->transactionStatus;
    }

    public function setStatus(string $value): void
    {
        $this->status = $value;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $value): void
    {
        $this->token = $value;
    }

    public function getShippingMethodId(): ?string
    {
        return $this->shippingMethodId;
    }

    public function setShippingMethodId(string $value): void
    {
        $this->shippingMethodId = $value;
    }

    public function getShippingMethod(): ?ShippingMethodEntity
    {
        return $this->shippingMethod;
    }

    public function setShippingMethod(?ShippingMethodEntity $value): void
    {
        $this->shippingMethod = $value;
    }

    public function getCustomerData(): ?object
    {
        return $this->customerData;
    }

    public function setCustomerData(object $value = null): void
    {
        $this->customerData = $value;
    }

    public function setPairingStatus(string $status): void
    {
        $this->status = $status;
    }

    public function setOrder(OrderEntity $order = null): void
    {
        $this->order = $order;
    }

    public function setOrderId(?string $id = null): void
    {
        $this->orderId = $id;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function isFinished(): bool
    {
        if ($this->isExpress) {
            return in_array($this->status, [self::STATUS_DONE, self::STATUS_CANCELED], true);
        }

        return in_array($this->status, [OrderStatus::SUCCESS, OrderStatus::FAILURE], true);
    }

    public function isSuccess(): bool
    {
        return $this->status === OrderStatus::SUCCESS;
    }

    public function isFailed(): bool
    {
        return $this->status === OrderStatus::FAILURE;
    }

    public function getIsExpress(): bool
    {
        return $this->isExpress;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getCheckedAgo(): ?int
    {
        return $this->checkedAgo;
    }

    public function getCreatedAgo(): ?int
    {
        return $this->createdAgo;
    }

    public function getCheckedAt(): ?DateTimeInterface
    {
        return $this->checkedAt;
    }

    public function isOrderProcessing(): bool
    {
        return (bool) $this->isOrdering;
    }

    public function isTimedOut(): bool
    {
        return $this->getCreatedAgo() > ($this->getIsExpress() ? Settings::PAIRING_TIMEOUT_EXPRESS : Settings::PAIRING_TIMEOUT_REGULAR);
    }
}

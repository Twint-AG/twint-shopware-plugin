<?php

declare(strict_types=1);

namespace Twint\Core\DataAbstractionLayer\Entity\Pairing;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class PairingEntity extends Entity
{
    use EntityIdTrait;

    protected ?Cart $cart = null;

    protected string $cartToken;

    protected string $status;

    protected string $token;

    protected ?string $shippingMethodId = null;

    protected ?ShippingMethodEntity $shippingMethod = null;

    protected ?SalesChannelEntity $salesChannel;

    protected string $salesChannelId;

    protected ?object $customerData = null;

    protected ?string $orderId = null;

    protected ?OrderEntity $order = null;

    public function getOrderId(): ?string
    {
        return $this->orderId;
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

    public function getCartToken(): string
    {
        return $this->cartToken;
    }

    public function setCartToken(string $value): void
    {
        $this->cartToken = $value;
    }

    public function getStatus(): string
    {
        return $this->status;
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
}

<?php

declare(strict_types=1);

namespace Twint\Core\DataAbstractionLayer\Entity\Pairing;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class TwintPairingEntity extends Entity
{
    use EntityIdTrait;

    protected ?Cart $cart = null;

    protected string $cartToken;

    protected string $status;

    protected string $token;

    protected string $shippingMethodId;

    protected ?ShippingMethodEntity $shippingMethod = null;

    protected ?object $customerData = null;

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

    public function getShippingMethodId(): string
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

    public function setCustomerData(object $value): void
    {
        $this->customerData = $value;
    }
}

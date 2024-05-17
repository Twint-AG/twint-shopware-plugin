<?php

declare(strict_types=1);

namespace Twint\Core\DataAbstractionLayer\Entity\TransactionLog;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class TwintTransactionLogEntity extends Entity
{
    use EntityIdTrait;

    protected ?OrderEntity $order = null;

    protected string $orderId;

    protected string $transactionId;

    protected string $request;

    protected string $response;

    protected string $soapRequest;

    protected string $soapResponse;

    public function getOrder(): ?OrderEntity
    {
        return $this->order;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getRequest(): string
    {
        return $this->request;
    }

    public function getResponse(): string
    {
        return $this->response;
    }

    public function getSoapRequest(): string
    {
        return $this->soapRequest;
    }

    public function getSoapResponse(): string
    {
        return $this->soapResponse;
    }
}

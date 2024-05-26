<?php

declare(strict_types=1);

namespace Twint\Core\DataAbstractionLayer\Entity\TransactionLog;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;

class TwintTransactionLogEntity extends Entity
{
    use EntityIdTrait;

    protected ?OrderEntity $order = null;

    protected ?StateMachineStateEntity $paymentState = null;

    protected ?StateMachineStateEntity $orderState = null;

    protected string $orderId;

    protected string $paymentStateId;

    protected string $orderStateId;

    protected string $transactionId;

    protected string $request;

    protected string $response;

    protected array $soapRequest;

    protected array $soapResponse;

    protected string $exception;

    public function getOrder(): ?OrderEntity
    {
        return $this->order;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getPaymentStateId(): string
    {
        return $this->paymentStateId;
    }

    public function getPaymentState(): ?StateMachineStateEntity
    {
        return $this->paymentState;
    }

    public function getOrderStateId(): string
    {
        return $this->orderStateId;
    }

    public function getOrderState(): ?StateMachineStateEntity
    {
        return $this->orderState;
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

    public function getSoapRequest(): array
    {
        return $this->soapRequest;
    }

    public function getSoapResponse(): array
    {
        return $this->soapResponse;
    }

    public function getException(): string
    {
        return $this->exception;
    }
}

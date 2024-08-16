<?php

declare(strict_types=1);

namespace Twint\Core\Service;

use Exception;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Throwable;
use Twint\Core\DataAbstractionLayer\Entity\Pairing\PairingEntity;
use Twint\Core\Factory\ClientBuilder;
use Twint\Core\Model\ApiResponse;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\OrderId;
use Twint\Sdk\Value\Uuid;

class PairingService
{
    public function __construct(
        private readonly EntityRepository $repository,
        private readonly ApiService $api,
        private readonly ClientBuilder $builder,
        private readonly OrderTransactionStateHandler $transactionStateHandler,
        private readonly OrderService $orderService,
    ) {
    }

    public function create(ApiResponse $response, OrderEntity $order, SalesChannelContext $context): PairingEntity
    {
        /** @var Order $tOrder */
        $tOrder = $response->getReturn();

        $this->repository->create([
            [
                'id' => $tOrder->id()
                    ->__toString(),
                'salesChannelId' => $context->getSalesChannel()
                    ->getId(),
                'status' => $tOrder->status()
                    ->__toString(),
                'pairingStatus' => $tOrder->pairingStatus()?->__toString(),
                'transactionStatus' => $tOrder->transactionStatus()
                    ->__toString(),
                'token' => $tOrder->pairingToken()?->__toString(),
                'amount' => $tOrder->amount()
                    ->amount(),
                'orderId' => $order->getId(),
            ],
        ], $context->getContext());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $tOrder->id()->__toString()));

        /** @phpstan-ignore-next-line */
        return $this->repository->search($criteria, $context->getContext())
            ->first();
    }

    /**
     * @throws Throwable
     */
    public function monitor(PairingEntity $pairing): bool
    {
        if ($pairing->isFinished()) {
            return true;
        }

        $org = clone $pairing;

        $client = $this->builder->build($pairing->getSalesChannelId());
        $res = $this->api->call($client, 'monitorOrder', [new OrderId(new Uuid($pairing->getId()))], false);

        /** @var Order $tOrder */
        $tOrder = $res->getReturn();
        if ($pairing->getStatus() !== $tOrder->status()->__toString() ||
            $pairing->getPairingStatus() !== $tOrder->pairingStatus()?->__toString() ||
            $pairing->getTransactionStatus() !== $tOrder->transactionStatus()
                ->__toString()
        ) {
            $pairing = $this->update($pairing, $res);
        }

        if ($tOrder->isPending()) {
            return false;
        }

        /** @var string $transactionId */
        $transactionId = $pairing->getOrder()
            ?->getTransactions()?->first()?->getId();

        if (!$org->isSuccess() && $tOrder->isSuccessful()) {
            $this->transactionStateHandler->paid($transactionId, Context::createDefaultContext());
        }

        if (!$org->isFailed() && $tOrder->isFailure()) {
            $this->transactionStateHandler->cancel($transactionId, Context::createDefaultContext());
        }

        $this->updateLog($res->getLog(), $pairing);

        return true;
    }

    /**
     * @throws Exception
     */
    protected function updateLog(array $log, PairingEntity $pairing): void
    {
        $statuses = $this->orderService->getCurrentStatuses($pairing->getOrderId() ?? '');
        $log['pairingId'] = $pairing->getId();
        $log = array_merge($log, $statuses);

        $this->api->saveLog($log);
    }

    protected function update(PairingEntity $pairing, ApiResponse $response): PairingEntity
    {
        /** @var Order $tOrder */
        $tOrder = $response->getReturn();

        $this->repository->update([
            [
                'id' => $pairing->getId(),
                'status' => $tOrder->status()
                    ->__toString(),
                'pairingStatus' => $tOrder->pairingStatus()?->__toString(),
                'transactionStatus' => $tOrder->transactionStatus()
                    ->__toString(),
            ],
        ], Context::createDefaultContext());

        return $pairing;
    }
}

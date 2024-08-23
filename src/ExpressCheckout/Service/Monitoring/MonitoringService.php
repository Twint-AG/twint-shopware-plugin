<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\Service\Monitoring;

use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Throwable;
use Twint\Core\DataAbstractionLayer\Entity\Pairing\PairingEntity;
use Twint\Core\Service\ApiService;
use Twint\Core\Service\PairingService as RegularPairingService;
use Twint\ExpressCheckout\Service\ExpressPaymentService;
use Twint\ExpressCheckout\Service\Monitoring\ContextFactory as TwintContext;
use Twint\ExpressCheckout\Service\Monitoring\StateHandler\OnPaidHandler;
use Twint\ExpressCheckout\Service\PairingService;
use Twint\Sdk\Exception\SdkError;
use Twint\Sdk\Value\FastCheckoutCheckIn;
use Twint\Sdk\Value\FastCheckoutState;
use Twint\Sdk\Value\PairingStatus;

class MonitoringService
{
    public function __construct(
        private ExpressPaymentService $paymentService,
        private TwintContext $context,
        private readonly OnPaidHandler $onPaidHandler,
        private readonly PairingService $pairingService,
        private readonly RegularPairingService $regular,
        private readonly ApiService $api,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @throws Exception
     * @throws SdkError
     * @throws Throwable
     */
    public function monitor(): void
    {
        $pairings = $this->pairingService->loadInProcessPairings();

        /** @var PairingEntity $pairing */
        foreach ($pairings as $pairing) {
            try {
                $this->monitorOne($pairing);
            }catch (Throwable $e){
                // Silent error to allow process handle next Pairings
                $this->logger->error("TWINT cli error: {$pairing->getId()} {$pairing->getToken()} {$e->getMessage()}");
            }
        }
    }

    /**
     * @throws Throwable
     */
    public function monitorOne(PairingEntity $pairing): mixed
    {
        return $pairing->getIsExpress() ? $this->monitorExpress($pairing) : $this->regular->monitor($pairing);
    }

    /**
     * @throws Exception\DriverException
     * @throws Exception
     */
    public function monitorExpress(PairingEntity $pairing): PairingEntity
    {
        $res = $this->paymentService->monitoring($pairing->getId(), $pairing->getSalesChannelId());
        $state = $res->getReturn();
        $this->pairingService->fetchCart($pairing, $this->context->getContext($pairing->getSalesChannelId()));
        if ($this->isChanged($pairing, $state)) {
            try {
                $this->pairingService->update($pairing, $state);
            } catch (Exception\DriverException $e) {
                if ($e->getSQLState() !== '45000') {
                    throw $e;
                }

                $this->logger->info("TWINT update pairing is locked {$pairing->getId()} {$pairing->getVersion()} {$pairing->getStatus()}");
                return $pairing;
            }

            $this->api->saveLog($res->getLog());
            $this->handle($pairing, $state);
        }

        return $pairing;
    }

    protected function isChanged(PairingEntity $entity, FastCheckoutCheckIn $state): bool
    {
        return $entity->getStatus() !== $state->pairingStatus()
            ->__toString()
            || $entity->getShippingMethodId() !== $state->shippingMethodId()?->__toString()
            || json_encode($entity->getCustomerData()) !== json_encode($state->customerData());
    }

    /**
     * @throws Exception
     */
    protected function handle(PairingEntity $entity, FastCheckoutState $state): PairingEntity
    {
        switch ($state->pairingStatus()->__toString()) {
            case PairingStatus::PAIRING_ACTIVE:
                if ($state instanceof FastCheckoutCheckIn && $state->hasCustomerData()) {
                    $this->onPaidHandler->handle($entity, $state);
                }
                break;

            case PairingStatus::NO_PAIRING:
                $this->pairingService->markAsCancelled($entity);
        }

        return $entity;
    }
}

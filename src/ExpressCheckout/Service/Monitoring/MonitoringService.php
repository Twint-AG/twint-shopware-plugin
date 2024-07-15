<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\Service\Monitoring;

use Doctrine\DBAL\Exception;
use Throwable;
use Twint\Core\DataAbstractionLayer\Entity\Pairing\PairingEntity;
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
        private readonly PairingService $pairingService
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
                $state = $this->paymentService->monitoring($pairing->getId(), $pairing->getSalesChannelId());
                $this->pairingService->fetchCart($pairing, $this->context->getContext($pairing->getSalesChannelId()));
                $this->pairingService->update($pairing, $state);
                $this->handle($pairing, $state);
            } catch (Throwable $e) {
                $this->pairingService->markAsError($pairing);
                echo $e->getMessage();
                throw $e;
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function handle(PairingEntity $entity, FastCheckoutState $state): void
    {
        switch ($state->pairingStatus()) {
            case PairingStatus::PAIRING_IN_PROGRESS:
            case PairingStatus::NO_PAIRING:
            case PairingStatus::PAIRING_ACTIVE:
                if ($state instanceof FastCheckoutCheckIn) {
                    $this->onPaidHandler->handle($entity, $state);
                    $this->pairingService->markAsDone($entity);
                }

                break;
        }
    }
}

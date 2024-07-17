<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\Service\Monitoring;

use Doctrine\DBAL\Exception;
use Throwable;
use Twint\Core\DataAbstractionLayer\Entity\Pairing\PairingEntity;
use Twint\ExpressCheckout\Service\ApiService;
use Twint\ExpressCheckout\Service\ExpressPaymentService;
use Twint\ExpressCheckout\Service\Monitoring\ContextFactory as TwintContext;
use Twint\ExpressCheckout\Service\Monitoring\StateHandler\OnPaidHandler;
use Twint\ExpressCheckout\Service\PairingService;
use Twint\Sdk\Exception\SdkError;
use Twint\Sdk\Value\FastCheckoutCheckIn;
use Twint\Sdk\Value\FastCheckoutState;

class MonitoringService
{
    public function __construct(
        private ExpressPaymentService   $paymentService,
        private TwintContext            $context,
        private readonly OnPaidHandler  $onPaidHandler,
        private readonly PairingService $pairingService,
        private readonly ApiService     $api
    )
    {
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
                $res = $this->paymentService->monitoring($pairing->getId(), $pairing->getSalesChannelId());
                $state = $res->getReturn();

                $this->pairingService->fetchCart($pairing, $this->context->getContext($pairing->getSalesChannelId()));

                if ($this->isChanged($pairing, $state)) {
                    $this->api->saveLog($res->getLog());
                    $this->pairingService->update($pairing, $state);
                }

                $this->handle($pairing, $state);
            } catch (Throwable $e) {
                echo $e->getMessage();
                throw $e;
            }
        }
    }

    protected function isChanged(PairingEntity $entity, FastCheckoutCheckIn $state): bool
    {
        return $entity->getStatus() !== $state->pairingStatus()->__toString()
            || $entity->getShippingMethodId() !== $state->shippingMethodId()?->__toString()
            || json_encode($entity->getCustomerData()) !== json_encode($state->customerData());
    }

    /**
     * @throws Exception
     */
    protected function handle(PairingEntity $entity, FastCheckoutState $state): void
    {
        if ($state instanceof FastCheckoutCheckIn && $state->hasCustomerData()) {
            $this->onPaidHandler->handle($entity, $state);
            $this->pairingService->markAsDone($entity);
        }
    }
}

<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\Service\Monitoring\StateHandler;

use Twint\Core\DataAbstractionLayer\Entity\Pairing\PairingEntity;
use Twint\Sdk\Value\FastCheckoutCheckIn;

interface StateHandlerInterface
{
    public function handle(PairingEntity $entity, FastCheckoutCheckIn $state): void;
}

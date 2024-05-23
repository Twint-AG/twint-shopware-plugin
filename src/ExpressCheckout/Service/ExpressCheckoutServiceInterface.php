<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\Service;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

interface ExpressCheckoutServiceInterface
{
    public function pairing(SalesChannelContext $context, Request $request): mixed;

    public function monitoring(string $pairingUUid, SalesChannelContext $context): mixed;
}

<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Twint\Core\Factory\ClientBuilder;
use Twint\Sdk\Exception\SdkError;
use Twint\Sdk\Value\CustomerDataScopes;
use Twint\Sdk\Value\FastCheckoutState;
use Twint\Sdk\Value\InteractiveFastCheckoutCheckIn;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\PairingUuid;
use Twint\Sdk\Value\ShippingMethods;

class ExpressPaymentService
{
    public function __construct(
        private readonly ClientBuilder $clientBuilder,
        private readonly EntityRepository $pairingRepository
    ) {
    }

    /**
     * @throws SdkError
     */
    public function requestFastCheckOutCheckIn(
        SalesChannelContext $context,
        Cart $cart,
        ShippingMethods $methods
    ): InteractiveFastCheckoutCheckIn {
        $client = $this->clientBuilder->build($context->getSalesChannel()->getId());

        $pairing = $client->requestFastCheckOutCheckIn(
            Money::CHF($cart->getPrice()->getTotalPrice()),
            new CustomerDataScopes(...CustomerDataScopes::all()),
            $methods
        );

        $this->pairingRepository->create([
            [
                'id' => $pairing->pairingUuid()
                    ->__toString(),
                'cart' => $cart,
                'cartToken' => $cart->getToken(),
                'status' => (string) $pairing->pairingStatus(),
                'token' => (string) $pairing->pairingToken(),
                'shippingMethodId' => null,
                'customerData' => null,
            ],
        ], $context->getContext());

        return $pairing;
    }

    /**
     * @throws SdkError
     */
    public function monitoring(string $pairingUUid, SalesChannelContext $context): FastCheckoutState
    {
        $client = $this->clientBuilder->build($context->getSalesChannel()->getId());

        return $client->monitorFastCheckOutCheckIn(PairingUuid::fromString($pairingUUid));
    }
}

<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\Service;

use Closure;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Twint\Core\DataAbstractionLayer\Entity\Pairing\PairingEntity;
use Twint\Core\Factory\ClientBuilder;
use Twint\Core\Model\ApiResponse;
use Twint\Core\Service\ApiService;
use Twint\Core\Setting\Settings;
use Twint\Sdk\Exception\SdkError;
use Twint\Sdk\Value\CustomerDataScopes;
use Twint\Sdk\Value\FastCheckoutCheckIn;
use Twint\Sdk\Value\InteractiveFastCheckoutCheckIn;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\OrderId;
use Twint\Sdk\Value\PairingUuid;
use Twint\Sdk\Value\ShippingMethods;
use Twint\Sdk\Value\UnfiledMerchantTransactionReference;
use Twint\Sdk\Value\Uuid;
use Twint\Sdk\Value\Version;

class ExpressPaymentService
{
    public function __construct(
        private readonly ClientBuilder $clientBuilder,
        private readonly EntityRepository $pairingRepository,
        public readonly ApiService $api,
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
        $client = $this->clientBuilder->build($context->getSalesChannel()->getId(), Version::NEXT);

        $amount = $cart->getPrice()
            ->getPositionPrice();
        $res = $this->api->call(
            $client,
            'requestFastCheckOutCheckIn',
            [Money::CHF($amount), new CustomerDataScopes(...CustomerDataScopes::all()), $methods],
            true,
            $this->getLogCallback()
        );

        $pairing = $res->getReturn();

        $this->pairingRepository->create([
            [
                'id' => (string) $pairing->pairingUuid(),
                'isExpress' => true,
                'amount' => $amount,
                'salesChannelId' => $context->getSalesChannel()
                    ->getId(),
                'cart' => $cart,
                'cartToken' => $context->getToken() . ':' . $cart->getToken(),
                'status' => (string) $pairing->pairingStatus(),
                'token' => (string) $pairing->pairingToken(),
                'customerId' => $context->getCustomer()?->getId(),
                'shippingMethodId' => null,
                'customerData' => null,
            ],
        ], $context->getContext());

        return $pairing;
    }

    public function monitoring(string $pairingUUid, string $channelId): ApiResponse
    {
        $client = $this->clientBuilder->build($channelId, Version::NEXT);

        return $this->api->call(
            $client,
            'monitorFastCheckOutCheckIn',
            [PairingUuid::fromString($pairingUUid)],
            false,
            $this->getLogCallback()
        );
    }

    public function startFastCheckoutOrder(OrderEntity $order, PairingEntity $pairing): ApiResponse
    {
        $client = $this->clientBuilder->build($order->getSalesChannelId(), Version::NEXT);

        /** @var non-empty-string $orderId */
        $orderId = $order->getOrderNumber();

        return $this->api->call($client, 'startFastCheckoutOrder', [
            PairingUuid::fromString($pairing->getId()),
            new UnfiledMerchantTransactionReference($orderId),
            new Money($order->getCurrency()?->getIsoCode() ?? Settings::ALLOWED_CURRENCY, $order->getAmountTotal()),
        ]);
    }

    public function monitoringOrder(string $pairingUuid, string $channelId): ApiResponse
    {
        $client = $this->clientBuilder->build($channelId);

        return $this->api->call($client, 'monitorOrder', [new OrderId(new Uuid($pairingUuid))], false);
    }

    protected function getLogCallback(): Closure
    {
        return static function (array $log, mixed $return) {
            if ($return instanceof InteractiveFastCheckoutCheckIn || $return instanceof FastCheckoutCheckIn) {
                $log['pairingId'] = $return->pairingUuid()->__toString();
                return $log;
            }

            return $log;
        };
    }
}

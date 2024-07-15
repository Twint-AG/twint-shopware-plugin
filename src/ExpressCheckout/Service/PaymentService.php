<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\OrderEntity;
use Throwable;
use Twint\Core\DataAbstractionLayer\Entity\Pairing\PairingEntity;
use Twint\Core\Factory\ClientBuilder;
use Twint\Core\Setting\Settings;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\PairingUuid;
use Twint\Sdk\Value\UnfiledMerchantTransactionReference;

class PaymentService
{
    public function __construct(
        private readonly ClientBuilder $clientBuilder,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function startFastCheckoutOrder(OrderEntity $order, PairingEntity $pairing): ?Order
    {
        $client = $this->clientBuilder->build($order->getSalesChannelId());

        try {
            /** @var non-empty-string $orderId */
            $orderId = $order->getId();
            return $client->startFastCheckoutOrder(
                PairingUuid::fromString($pairing->getId()),
                new UnfiledMerchantTransactionReference($orderId),
                new Money($order->getCurrency()?->getIsoCode() ?? Settings::ALLOWED_CURRENCY, $order->getAmountTotal())
            );
        } catch (Throwable $e) {
            $this->logger->error("Cannot start FastCheckoutOrder {$order->getId()}");
        }
        return null;
    }
}

<?php

declare(strict_types=1);

namespace Twint\Subscriber;

use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twint\Core\Setting\Settings;

class CheckoutConfirmPageSubscriber implements EventSubscriberInterface
{
    private const EXPRESS_HANDLER_ID = 'Twint\\Core\\Handler\\TwintExpressPaymentHandler';

    private const REGULAR_HANDLER_ID = 'Twint\\Core\\Handler\\TwintRegularPaymentHandler';

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'onConfirmPageLoaded',
            AccountPaymentMethodPageLoadedEvent::class => 'onConfirmPageLoaded',
            AccountEditOrderPageLoadedEvent::class => 'onConfirmPageLoaded',
        ];
    }

    public function onConfirmPageLoaded(
        CheckoutConfirmPageLoadedEvent | AccountPaymentMethodPageLoadedEvent | AccountEditOrderPageLoadedEvent $event
    ): void {
        $salesChannelContext = $event->getSalesChannelContext();

        foreach ($event->getPage()->getPaymentMethods() as $method) {
            $identifier = $method->getHandlerIdentifier();
            switch ($identifier) {
                case self::EXPRESS_HANDLER_ID:
                    $event->getPage()
                        ->getPaymentMethods()
                        ->remove($method->getId());
                    break;

                case self::REGULAR_HANDLER_ID:
                    $currencyCode = $salesChannelContext->getCurrency()
                        ->getIsoCode();

                    if ($currencyCode !== Settings::ALLOWED_CURRENCY) {
                        $event->getPage()
                            ->getPaymentMethods()
                            ->remove($method->getId());
                    }
            }
        }
    }
}

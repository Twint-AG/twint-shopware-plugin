<?php

declare(strict_types=1);

namespace Twint\Subscriber;

use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twint\Core\Handler\TwintExpressPaymentHandler;
use Twint\Core\Handler\TwintRegularPaymentHandler;
use Twint\Core\Setting\Settings;

class CheckoutConfirmPageSubscriber implements EventSubscriberInterface
{
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
                case TwintExpressPaymentHandler::class:
                    $event->getPage()
                        ->getPaymentMethods()
                        ->remove($method->getId());
                    break;

                case TwintRegularPaymentHandler::class:
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

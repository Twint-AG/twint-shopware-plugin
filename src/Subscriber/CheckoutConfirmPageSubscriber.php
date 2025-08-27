<?php

declare(strict_types=1);

namespace Twint\Subscriber;

use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twint\Core\Setting\Settings;
use Twint\Util\Method\ExpressPaymentMethod;
use Twint\Util\Method\RegularPaymentMethod;

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
                case ExpressPaymentMethod::HANDLER:
                    $event->getPage()
                        ->getPaymentMethods()
                        ->remove($method->getId());
                    break;

                case RegularPaymentMethod::HANDLER:
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

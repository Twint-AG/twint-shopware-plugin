<?php

namespace Twint\Subscriber;

use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twint\Sdk\Value\Money;

class CheckoutConfirmPageSubscriber implements EventSubscriberInterface
{
    private TranslatorInterface $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    /**
     * @return array<mixed>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'onConfirmPageLoaded'
        ];
    }


    /**
     * @param AccountEditOrderPageLoadedEvent|CheckoutConfirmPageLoadedEvent $args
     */
    public function onConfirmPageLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        $currencyCode = $salesChannelContext->getCurrency()->getIsoCode();
        if ($currencyCode != Money::CHF) {
            foreach ($event->getPage()->getPaymentMethods() as $paymentMethod) {
                if ($paymentMethod->getHandlerIdentifier() === 'Twint\Core\Handler\PaymentHandler') {
                    $event->getPage()->getPaymentMethods()->remove($paymentMethod->getId());
                    $event->getRequest()->getSession()->getFlashBag()->add('danger', $this->translator->trans('twintPayment.error.invalidPaymentError', ['%name%' => $paymentMethod->getName(), '%currency%' => Money::CHF]));
                    break;
                }
            }
        }
    }
}

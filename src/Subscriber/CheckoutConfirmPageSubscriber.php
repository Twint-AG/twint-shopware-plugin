<?php

declare(strict_types=1);

namespace Twint\Subscriber;

use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twint\Core\Handler\TwintExpressPaymentHandler;
use Twint\Core\Handler\TwintRegularPaymentHandler;
use Twint\Core\Setting\Settings;
use Twint\Sdk\Value\Money;

class CheckoutConfirmPageSubscriber implements EventSubscriberInterface
{
    private TranslatorInterface $translator;

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
            CheckoutConfirmPageLoadedEvent::class => 'onConfirmPageLoaded',
        ];
    }

    public function onConfirmPageLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
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

                    if (!in_array($currencyCode, Settings::ALLOWED_CURRENCIES, true)) {
                        $event->getPage()
                            ->getPaymentMethods()
                            ->remove($method->getId());

                        $session = $event->getRequest()
                            ->getSession();

                        if ($session instanceof FlashBagAwareSessionInterface) {
                            $session->getFlashBag()
                                ->add('danger', $this->translator->trans('twintPayment.error.invalidPaymentError', [
                                    '%name%' => $method->getName(),
                                    '%currency%' => Money::CHF,
                                ]));
                        }
                    }
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace Twint\FastCheckout\Subscriber;

use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twint\Core\Setting\Settings;
use Twint\FastCheckout\Model\FastCheckoutButton;
use Twint\FastCheckout\Service\FastCheckoutButtonService;

final class ProductPageLoadedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly FastCheckoutButtonService $service
    ) {
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'onProductLoaded',
            NavigationPageLoadedEvent::class => 'onNavigationPageLoaded',
            OffcanvasCartPageLoadedEvent::class => 'onOffcanvasCartPageLoaded',
            CheckoutCartPageLoadedEvent::class => 'onCheckoutCartPageLoaded',
        ];
    }

    public function onProductLoaded(ProductPageLoadedEvent $event): void
    {
        $this->addExtension($event, Settings::SCREENS_OPTIONS_PDP);
    }

    public function onNavigationPageLoaded(NavigationPageLoadedEvent $event): void
    {
        $this->addExtension($event, Settings::SCREENS_OPTIONS_PLP);
    }

    public function onOffcanvasCartPageLoaded(OffcanvasCartPageLoadedEvent $event): void
    {
        $this->addExtension($event, Settings::SCREENS_OPTIONS_CART_FLYOUT);
    }

    public function onCheckoutCartPageLoaded(CheckoutCartPageLoadedEvent $event): void
    {
        $this->addExtension($event, Settings::SCREENS_OPTIONS_CART);
    }

    private function addExtension(PageLoadedEvent $event, string $screen): void
    {
        $context = $event->getSalesChannelContext();
        $page = $event->getPage();

        $button = $this->service->getButton($context, $screen);
        if ($button instanceof FastCheckoutButton) {
            $page->addExtension('TwintFastCheckoutButton', $button);
        }
    }
}

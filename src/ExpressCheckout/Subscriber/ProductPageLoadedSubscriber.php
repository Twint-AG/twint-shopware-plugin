<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\Subscriber;

use Shopware\Core\Framework\Routing\Event\SalesChannelContextResolvedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twint\ExpressCheckout\Service\ExpressCheckoutButtonService;

class ProductPageLoadedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ExpressCheckoutButtonService $service
    ) {
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SalesChannelContextResolvedEvent::class => 'onSalesChannelContextResolvedEvent',
        ];
    }

    public function onSalesChannelContextResolvedEvent(SalesChannelContextResolvedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        $this->service->getButtons($salesChannelContext);
    }
}

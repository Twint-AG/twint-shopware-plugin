<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Delivery\DeliveryBuilder;
use Shopware\Core\Checkout\Cart\LineItemFactoryRegistry;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Shipping\SalesChannel\AbstractShippingMethodRoute;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Event\RouteRequest\ShippingMethodRouteRequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Twint\Sdk\Exception\SdkError;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\ShippingMethod;
use Twint\Sdk\Value\ShippingMethodId;
use Twint\Sdk\Value\ShippingMethods;

class ExpressCheckoutService implements ExpressCheckoutServiceInterface
{
    public function __construct(
        private readonly CartService                 $cartService,
        private readonly LineItemFactoryRegistry     $itemFactoryRegistry,
        private readonly AbstractShippingMethodRoute $shippingMethodRoute,
        private readonly EventDispatcherInterface    $eventDispatcher,
        private readonly DeliveryBuilder             $deliveryBuilder,
        private readonly ExpressPaymentService       $paymentService
    )
    {
    }

    /**
     * @throws SdkError
     */
    public function pairing(SalesChannelContext $context, Request $request): mixed
    {
        $cart = $this->createCart($context, $request->getPayload()->all());
        $methods = $this->getShippingMethods($context, $request);

        $options = $this->buildShippingOptions($cart, $methods, $context);

        return $this->paymentService->requestFastCheckOutCheckIn($context, $cart, $options);
    }

    public function monitoring(string $pairingUUid, SalesChannelContext $context): mixed
    {
        return $this->paymentService->monitoring($pairingUUid, $context);
    }

    private function buildShippingOptions(Cart $cart, EntityCollection $methods, SalesChannelContext $context): mixed
    {
        $options = [];
        foreach ($methods as $method) {
            /** @var ShippingMethodEntity $method */
            $cart->setDeliveries($this->deliveryBuilder->buildByUsingShippingMethod($cart, $method, $context));
            $cart = $this->cartService->recalculate($cart, $context);
            $amount = $cart->getDeliveries()
                ->getShippingCosts()
                ->first()
                ?->getTotalPrice();

            $options[] = new ShippingMethod(
                new ShippingMethodId(
                    $method->getId()
                ), $method->getName() ?? 'Default Shipping Method'
                , Money::CHF($amount ?? 0)
            );
        }

        return new ShippingMethods(...$options);
    }

    private function getShippingMethods(SalesChannelContext $context, Request $request): ShippingMethodCollection
    {
        $criteria = new Criteria();
        $criteria->setTitle('generic-page::shipping-methods');

        $event = new ShippingMethodRouteRequestEvent($request, $request->duplicate(), $context, $criteria);
        $this->eventDispatcher->dispatch($event);

        return $this->shippingMethodRoute
            ->load($event->getStoreApiRequest(), $context, $event->getCriteria())
            ->getShippingMethods();
    }

    private function getLineItems(array $items, SalesChannelContext $context): array
    {
        $lineItems = [];
        foreach ($items as $item) {
            $lineItems[] = $this->itemFactoryRegistry->create($item, $context);
        }

        return $lineItems;
    }

    private function createCart(SalesChannelContext $context, array $items): Cart
    {
        $token = $context->getToken();
        $cart = $this->cartService->createNew($token);

        $lineItems = $this->getLineItems($items, $context);

        return $this->cartService->add($cart, $lineItems, $context);
    }
}

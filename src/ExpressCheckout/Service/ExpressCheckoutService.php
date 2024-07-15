<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartPersister;
use Shopware\Core\Checkout\Cart\Delivery\DeliveryBuilder;
use Shopware\Core\Checkout\Cart\LineItemFactoryRegistry;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Shipping\SalesChannel\AbstractShippingMethodRoute;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Event\RouteRequest\ShippingMethodRouteRequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Twint\Core\Service\CurrencyService;
use Twint\Core\Service\SettingServiceInterface;
use Twint\ExpressCheckout\Repository\PairingRepository;
use Twint\Sdk\Exception\SdkError;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\ShippingMethod;
use Twint\Sdk\Value\ShippingMethodId;
use Twint\Sdk\Value\ShippingMethods;

class ExpressCheckoutService implements ExpressCheckoutServiceInterface
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly LineItemFactoryRegistry $itemFactoryRegistry,
        private readonly AbstractShippingMethodRoute $shippingMethodRoute,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly DeliveryBuilder $deliveryBuilder,
        private readonly ExpressPaymentService $paymentService,
        private readonly PairingRepository $loader,
        private readonly AbstractSalesChannelContextFactory $contextFactory,
        private readonly CartPersister $cartPersister,
        private readonly SettingServiceInterface $settingService,
        private readonly CurrencyService $currencyService,
    ) {
    }

    /**
     * @throws SdkError
     */
    public function pairing(SalesChannelContext $context, Request $request): mixed
    {
        $payload = $request->getPayload()
            ->all();

        $useCart = $payload['useCart'] ?? false;
        if ($useCart) {
            $cart = $this->cloneCart($context);
        } else {
            $setting = $this->settingService->getSetting($context->getSalesChannel()->getId());
            if ($setting->getCheckoutSingle()) {
                $cart = $this->createCart($context, $payload['lineItems']);
            } else {
                $cart = $this->cloneCart($context);
                $cart = $this->cartService->add($cart, $this->getLineItems($payload['lineItems'], $context), $context);
            }
        }

        $methods = $this->getShippingMethods($context, $request);

        $options = $this->buildShippingOptions($cart, $methods, $context);

        return $this->paymentService->requestFastCheckOutCheckIn($context, $cart, $options);
    }

    protected function cloneCart(SalesChannelContext $context): Cart
    {
        $cart = $this->cartService->getCart($context->getToken(), $context);
        $token = Uuid::randomHex();
        $this->cartPersister->replace($context->getToken(), $token, $context);
        $cart->setToken($token);

        return $cart;
    }

    public function monitoring(string $pairingUUid, SalesChannelContext $context): mixed
    {
        return $this->paymentService->monitoring($pairingUUid, $context->getSalesChannel()->getId());
    }

    /**
     * Hard to cart calculate shipping costs for each shipping method
     * Shopware forces to set shipping method to SalesChannelContext
     */
    private function buildShippingOptions(Cart $cart, EntityCollection $methods, SalesChannelContext $context): mixed
    {
        $options = [];
        /** @var ShippingMethodEntity $method */
        foreach ($methods as $key => $method) {
            $cart->setDeliveries($this->deliveryBuilder->buildByUsingShippingMethod($cart, $method, $context));
            if ($context->getShippingMethod()->getId() !== $method->getId()) {
                $session = [
                    'shippingMethodId' => $method->getId(),
                    'currencyId' => $this->currencyService->getCurrencyId(),
                ];

                $context = $this->contextFactory->create(
                    $context->getToken(),
                    $context->getSalesChannel()
                        ->getId(),
                    $session
                );
            }

            $cart = $this->cartService->recalculate($cart, $context);
            $amount = $cart->getDeliveries()
                ->getShippingCosts()
                ->first()
                ?->getTotalPrice();

            $options[] = new ShippingMethod(
                new ShippingMethodId($method->getId()),
                $method->getName() ?? $method->getTranslated()['name'] ?? "Shipping Method #{$key}",
                Money::CHF($amount ?? 0)
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
        $token = Uuid::randomHex();
        $cart = $this->cartService->createNew($token);

        $lineItems = $this->getLineItems($items, $context);

        return $this->cartService->add($cart, $lineItems, $context);
    }

    public function getOpenedPairings(): mixed
    {
        return $this->loader->loadInProcessPairings();
    }
}

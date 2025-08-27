<?php

declare(strict_types=1);

namespace Twint\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class UpdateShippingRouteCompilerPass implements CompilerPassInterface
{
    private const TARGET_SERVICE_ID = 'twint.service.express.checkout';

    private const SORTED_ROUTE = 'Shopware\Core\Checkout\Shipping\SalesChannel\SortedShippingMethodRoute';

    private const DEFAULT_ROUTE = 'Shopware\Core\Checkout\Shipping\SalesChannel\ShippingMethodRoute';

    private const ARGUMENT_INDEX_TO_CHANGE = 2;

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::TARGET_SERVICE_ID)) {
            return;
        }

        // Prefer SortedShippingMethodRoute if it exists, otherwise fallback
        $serviceId = class_exists(self::SORTED_ROUTE) ? self::SORTED_ROUTE : self::DEFAULT_ROUTE;

        $definition = $container->getDefinition(self::TARGET_SERVICE_ID);
        $definition->setArgument(self::ARGUMENT_INDEX_TO_CHANGE, new Reference($serviceId));
    }
}

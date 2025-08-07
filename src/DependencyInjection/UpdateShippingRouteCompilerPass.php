<?php

declare(strict_types=1);

namespace Twint\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class UpdateShippingRouteCompilerPass implements CompilerPassInterface
{
    private const TARGET_SERVICE_ID = 'twint.service.express.checkout';

    private const ARGUMENT_INDEX_TO_CHANGE = 2;

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::TARGET_SERVICE_ID)) {
            return;
        }
        $shopwareVersion = $container->getParameter('kernel.shopware_version');
        $shippingRouteServiceId = version_compare($shopwareVersion, '6.7.0.0', '<')
            ? 'Shopware\Core\Checkout\Shipping\SalesChannel\SortedShippingMethodRoute'
            : 'Shopware\Core\Checkout\Shipping\SalesChannel\ShippingMethodRoute';
        $definition = $container->getDefinition(self::TARGET_SERVICE_ID);
        $definition->setArgument(self::ARGUMENT_INDEX_TO_CHANGE, new Reference($shippingRouteServiceId));
    }
}

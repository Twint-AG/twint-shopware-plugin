<?php

declare(strict_types=1);

namespace Twint\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RemoveSetTwigCompilerPass implements CompilerPassInterface
{
    private const CONTROLLERS_TO_MODIFY = [
        'Twint\Storefront\Controller\PaymentController',
        'Twint\Storefront\Controller\CheckoutController',
    ];

    public function process(ContainerBuilder $container)
    {
        $shopwareVersion = $container->getParameter('kernel.shopware_version');
        if (version_compare($shopwareVersion, '6.7.0.0', '<')) {
            return;
        }
        foreach (self::CONTROLLERS_TO_MODIFY as $controller) {
            if ($container->hasDefinition($controller)) {
                $definition = $container->getDefinition($controller);
                $methodCalls = $definition->getMethodCalls();
                $filteredCalls = array_filter($methodCalls, static fn (array $call): bool => $call[0] !== 'setTwig');
                $definition->setMethodCalls(array_values($filteredCalls));
            }
        }
    }
}

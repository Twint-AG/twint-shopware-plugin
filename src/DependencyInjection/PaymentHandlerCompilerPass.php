<?php

declare(strict_types=1);

namespace Twint\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PaymentHandlerCompilerPass implements CompilerPassInterface
{
    private const HANDLER_SERVICE_IDS = [
        'Twint\Core\Handler\TwintRegularPaymentHandler',
        'Twint\Core\Handler\TwintExpressPaymentHandler',
    ];

    public function process(ContainerBuilder $container): void
    {
        $shopwareVersion = $container->getParameter('kernel.shopware_version');
        $versionNamespace = version_compare($shopwareVersion, '6.7.0.0', '<') ? 'V66' : 'V67';

        foreach (self::HANDLER_SERVICE_IDS as $serviceId) {
            if (!$container->hasDefinition($serviceId)) {
                continue;
            }

            $definition = $container->getDefinition($serviceId);
            $newClassName = str_replace(
                '\\Handler\\',
                '\\Handler\\' . $versionNamespace . '\\',
                $definition->getClass()
            );
            $definition->setClass($newClassName);
        }
    }
}

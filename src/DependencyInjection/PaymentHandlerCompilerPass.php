<?php

declare(strict_types=1);

namespace Twint\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PaymentHandlerCompilerPass implements CompilerPassInterface
{
    private const HANDLER_SERVICE_IDS = ['twint.regular.handler', 'twint.express.handler'];

    public function process(ContainerBuilder $container): void
    {
        $version = $container->getParameter('kernel.shopware_version');
        $namespace = version_compare($version, '6.7.0.0', '<') ? 'V66' : 'V67';

        foreach (self::HANDLER_SERVICE_IDS as $serviceId) {
            if (!$container->hasDefinition($serviceId)) {
                continue;
            }

            $definition = $container->getDefinition($serviceId);
            $newClassName = str_replace('\\Handler\\', '\\Handler\\' . $namespace . '\\', $definition->getClass());

            $definition->setClass($newClassName);
        }
    }
}

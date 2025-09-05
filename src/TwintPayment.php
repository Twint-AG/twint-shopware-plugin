<?php

declare(strict_types=1);

namespace Twint;

use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderDefinition;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Rule\RuleDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\System\SystemConfig\SystemConfigDefinition;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Twint\DependencyInjection\PaymentHandlerCompilerPass;
use Twint\DependencyInjection\RemoveSetTwigCompilerPass;
use Twint\Util\ConfigInstaller;
use Twint\Util\Installer;
use Twint\Util\MediaInstaller;
use Twint\Util\PaymentMethodInstaller;
use Twint\Util\PaymentMethodRegistry;
use function rtrim;
use function sprintf;

#[Package('checkout')]
class TwintPayment extends Plugin
{
    public const EXIT_CODE_SUCCESS = 0;

    public function install(InstallContext $installContext): void
    {
        $this->getInstaller()
            ->install($installContext->getContext());

        parent::install($installContext);
        $installContext->getMigrationCollection()
            ->migrateInPlace();
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        if (!$uninstallContext->keepUserData()) {
            $this->getInstaller()
                ->uninstall($uninstallContext->getContext());
        }

        parent::uninstall($uninstallContext);
    }

    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);
        $this->getInstaller()
            ->activate($activateContext->getContext());
        $activateContext->getMigrationCollection()
            ->migrateInPlace();
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        parent::deactivate($deactivateContext);
        $this->getInstaller()
            ->deactivate($deactivateContext->getContext());
    }

    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext);
        $updateContext->getMigrationCollection()
            ->migrateInPlace();
    }

    private function getInstaller(): Installer
    {
        return new Installer(
            new PaymentMethodInstaller(
                // @phpstan-ignore-next-line
                $this->getRepository($this->container, PaymentMethodDefinition::ENTITY_NAME),
                // @phpstan-ignore-next-line
                $this->getRepository($this->container, RuleDefinition::ENTITY_NAME),
                // @phpstan-ignore-next-line
                $this->container->get(PluginIdProvider::class),
                new PaymentMethodRegistry(
                    // @phpstan-ignore-next-line
                    $this->container,
                    // @phpstan-ignore-next-line
                    $this->getRepository($this->container, PaymentMethodDefinition::ENTITY_NAME),
                    []
                ),
                new MediaInstaller(
                    // @phpstan-ignore-next-line
                    $this->getRepository($this->container, MediaDefinition::ENTITY_NAME),
                    // @phpstan-ignore-next-line
                    $this->getRepository($this->container, MediaFolderDefinition::ENTITY_NAME),
                    // @phpstan-ignore-next-line
                    $this->getRepository($this->container, PaymentMethodDefinition::ENTITY_NAME),
                    // @phpstan-ignore-next-line
                    $this->container->get(FileSaver::class)
                ),
            ),
            new ConfigInstaller(
                // @phpstan-ignore-next-line
                $this->getRepository($this->container, SystemConfigDefinition::ENTITY_NAME),
                // @phpstan-ignore-next-line
                $this->container->get(SystemConfigService::class)
            )
        );
    }

    private function getRepository(ContainerInterface $container, string $entityName): EntityRepository
    {
        $repository = $container->get(
            sprintf('%s.repository', $entityName),
            ContainerInterface::NULL_ON_INVALID_REFERENCE
        );

        if (!$repository instanceof EntityRepository) {
            throw new ServiceNotFoundException(sprintf('%s.repository', $entityName));
        }

        return $repository;
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build(container: $container);
        $container->addCompilerPass(new RemoveSetTwigCompilerPass());
        $container->addCompilerPass(new PaymentHandlerCompilerPass());
        $locator = new FileLocator(__DIR__ . '/Resources/config');
        $xmlLoader = new XmlFileLoader($container, $locator);
        $xmlLoader->load('services.xml');

        if (version_compare($container->getParameter('kernel.shopware_version'), '6.7.0.0', '<')) {
            // For versions older than 6.7.0.0
            $xmlLoader->load('parts/handler_pre_6_7.xml');
        } else {
            // For versions 6.7.0.0 and newer
            $xmlLoader->load('parts/handler.xml');
        }

        // 3. Load YAML configuration files.
        $locator = new FileLocator(__DIR__ . '/Resources/config');
        $resolver = new LoaderResolver([
            new YamlFileLoader($container, $locator),
            new GlobFileLoader($container, $locator),
        ]);
        $configLoader = new DelegatingLoader($resolver);
        $confDir = rtrim($this->getPath(), '/') . '/Resources/config';
        $configLoader->load($confDir . '/{packages}/*.yaml', 'glob');
    }

    public function executeComposerCommands(): bool
    {
        return true;
    }
}

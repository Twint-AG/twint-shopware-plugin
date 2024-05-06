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
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetDefinition;
use Shopware\Core\System\CustomField\CustomFieldDefinition;
use Shopware\Core\System\Snippet\SnippetDefinition;
use Shopware\Core\System\SystemConfig\SystemConfigDefinition;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Twint\Util\ConfigInstaller;
use Twint\Util\Installer;
use Twint\Util\MediaInstaller;
use Twint\Util\OrderCustomFieldInstaller;
use Twint\Util\PaymentMethodInstaller;
use Twint\Util\PaymentMethodRegistry;
use function rtrim;
use function sprintf;

#[Package('checkout')]
final class TwintPayment extends Plugin
{
    public const EXIT_CODE_SUCCESS = 0;

    public function install(InstallContext $installContext): void
    {
        $this->getInstaller()
            ->install($installContext->getContext());

        parent::install($installContext);
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
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        parent::deactivate($deactivateContext);
        $this->getInstaller()
            ->deactivate($deactivateContext->getContext());
    }

    public function update(UpdateContext $updateContext): void
    {
        // Update necessary stuff, mostly non-database related
    }

    public function postInstall(InstallContext $installContext): void
    {
    }

    public function postUpdate(UpdateContext $updateContext): void
    {
    }

    private function getInstaller()
    {
        return new Installer(
            new PaymentMethodInstaller(
                $this->getRepository($this->container, PaymentMethodDefinition::ENTITY_NAME),
                $this->getRepository($this->container, RuleDefinition::ENTITY_NAME),
                $this->container->get(PluginIdProvider::class),
                new PaymentMethodRegistry(
                    $this->container,
                    $this->getRepository($this->container, PaymentMethodDefinition::ENTITY_NAME),
                    []
                ),
                new MediaInstaller(
                    $this->getRepository($this->container, MediaDefinition::ENTITY_NAME),
                    $this->getRepository($this->container, MediaFolderDefinition::ENTITY_NAME),
                    $this->getRepository($this->container, PaymentMethodDefinition::ENTITY_NAME),
                    $this->container->get(FileSaver::class)
                ),
            ),
            new ConfigInstaller(
                $this->getRepository($this->container, SystemConfigDefinition::ENTITY_NAME),
                $this->container->get(SystemConfigService::class)
            ),
            new OrderCustomFieldInstaller(
                $this->getRepository($this->container, CustomFieldSetDefinition::ENTITY_NAME),
                $this->getRepository($this->container, CustomFieldDefinition::ENTITY_NAME),
                $this->getRepository($this->container, SnippetDefinition::ENTITY_NAME),
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
        parent::build($container);

        $locator = new FileLocator('Resources/config');

        $resolver = new LoaderResolver([
            new YamlFileLoader($container, $locator),
            new GlobFileLoader($container, $locator),
            new DirectoryLoader($container, $locator),
        ]);

        $configLoader = new DelegatingLoader($resolver);

        $confDir = rtrim($this->getPath(), '/') . '/Resources/config';

        $configLoader->load($confDir . '/{packages}/*.yaml', 'glob');
    }
}

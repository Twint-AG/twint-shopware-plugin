<?php

declare(strict_types=1);

namespace Twint\Util;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
class Installer
{
    public function __construct(
        private readonly PaymentMethodInstaller $paymentMethodInstaller,
        private readonly ConfigInstaller $configInstaller,
        private readonly OrderCustomFieldInstaller $orderCustomFieldInstaller
    ) {
    }

    public function install(Context $context): void
    {
        $this->paymentMethodInstaller->installAll($context);
        $this->configInstaller->addDefaultConfiguration();
        $this->orderCustomFieldInstaller->install($context);
    }

    public function uninstall(Context $context): void
    {
        $this->paymentMethodInstaller->setAllPaymentStatus(false, $context);
        $this->configInstaller->removeConfiguration($context);
        $this->orderCustomFieldInstaller->uninstall($context);
    }

    public function activate(Context $context): void
    {
        $this->paymentMethodInstaller->setAllPaymentStatus(true, $context);
    }

    public function deactivate(Context $context): void
    {
        $this->paymentMethodInstaller->setAllPaymentStatus(false, $context);
    }
}

<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\Service;

use Doctrine\DBAL\Exception;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Twint\Core\Service\SettingServiceInterface;
use Twint\Core\Setting\Settings;
use Twint\ExpressCheckout\Model\ExpressCheckoutButton;
use Twint\ExpressCheckout\Util\PaymentMethodUtil;

class ExpressCheckoutButtonService
{
    public function __construct(
        private readonly PaymentMethodUtil $paymentMethodUtil,
        private readonly SettingServiceInterface $settingService,
    ) {
    }

    /**
     * @throws Exception
     */
    public function getButton(SalesChannelContext $context, string $screen): ?ExpressCheckoutButton
    {
        $settings = $this->settingService->getSetting($context->getSalesChannel()->getId());
        // Check if the credentials are validated
        if (!$settings->getValidated()) {
            return null;
        }

        $expressCheckoutMethodId = $this->paymentMethodUtil->getExpressCheckoutMethodId();
        if (!in_array($expressCheckoutMethodId, $context->getSalesChannel()->getPaymentMethodIds() ?? [], true)) {
            return null;
        }

        // Check if the express checkout is enabled
        $enabled = $this->paymentMethodUtil->isExpressCheckoutEnabled($context);
        if (!$enabled) {
            return null;
        }

        // Check if the currency is allowed
        $currency = $context->getCurrency()
            ->getIsoCode();
        if (!in_array($currency, Settings::ALLOWED_CURRENCIES, true)) {
            return null;
        }

        // Check if the screens are allowed
        if (!in_array($screen, $settings->getScreens(), true)) {
            return null;
        }

        return new ExpressCheckoutButton(
            'TWINT Fast Checkout',
            'Fast Checkout Description',
            'https://example.com/image.png',
            'https://example.com/link'
        );
    }
}

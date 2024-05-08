<?php

declare(strict_types=1);

namespace Twint\FastCheckout\Service;

use Doctrine\DBAL\Exception;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Twint\Core\Service\SettingService;
use Twint\Core\Setting\Settings;
use Twint\FastCheckout\Model\FastCheckoutButton;
use Twint\FastCheckout\Util\PaymentMethodUtil;

class FastCheckoutButtonService
{
    public function __construct(
        private readonly PaymentMethodUtil $paymentMethodUtil,
        private readonly SettingService $settingService
    ) {
    }

    /**
     * @throws Exception
     */
    public function getButton(SalesChannelContext $context, string $screen): ?FastCheckoutButton
    {
        // Check if the fast checkout is enabled
        $enabled = $this->paymentMethodUtil->isFastCheckoutEnabled($context);
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
        $settings = $this->settingService->getSetting($context->getSalesChannel()->getId());
        if (!in_array($screen, $settings->getScreens(), true)) {
            return null;
        }

        return new FastCheckoutButton(
            'TWINT Fast Checkout',
            'Fast Checkout Description',
            'https://example.com/image.png',
            'https://example.com/link'
        );
    }
}

<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\Service;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\SalesChannel\AbstractPaymentMethodRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Country\SalesChannel\AbstractCountryRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Twint\Core\Service\SettingServiceInterface;
use Twint\Core\Setting\Settings;
use Twint\ExpressCheckout\Util\PaymentMethodUtil;

class ExpressCheckoutButtonService
{
    private static ?array $buttons = null;

    public function __construct(
        private readonly PaymentMethodUtil $paymentMethodUtil,
        private readonly SettingServiceInterface $settingService,
        private readonly AbstractPaymentMethodRoute $paymentMethodRoute,
        private readonly AbstractCountryRoute $countryRoute
    ) {
    }

    public static function isEnabled(string $screen): bool
    {
        return is_array(self::$buttons) && in_array($screen, self::$buttons, true);
    }

    private function getPaymentMethods(SalesChannelContext $context): PaymentMethodCollection
    {
        $request = new Request();
        $request->query->set('onlyAvailable', '1');

        return $this->paymentMethodRoute->load($request, $context, new Criteria())
            ->getPaymentMethods();
    }

    private function getCountries(SalesChannelContext $salesChannelContext): CountryCollection
    {
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('active', true))
            ->addAssociation('states');

        $countries = $this->countryRoute->load(new Request(), $criteria, $salesChannelContext)
            ->getCountries();

        $countries->sortCountryAndStates();

        return $countries;
    }

    public function getButtons(SalesChannelContext $context): array
    {
        $channelId = $context->getSalesChannel()
            ->getId();
        if (is_array(self::$buttons)) {
            return self::$buttons;
        }

        /**
         * Get the payment methods for the sales channel. Already filtered by active rules.
         */
        $paymentMethods = $this->getPaymentMethods($context);
        $context->getSalesChannel()
            ->setPaymentMethods($paymentMethods);

        $settings = $this->settingService->getSetting($channelId);
        // Check if the credentials are validated
        if (!$settings->getValidated()) {
            self::$buttons = [];
            return self::$buttons;
        }

        $expressCheckoutMethodId = $this->paymentMethodUtil->getExpressCheckoutMethodId();
        if (!in_array($expressCheckoutMethodId, $paymentMethods->getIds(), true)) {
            self::$buttons = [];
            return self::$buttons;
        }

        // Check if the express checkout is enabled
        $enabled = $this->paymentMethodUtil->isExpressCheckoutEnabled($context);
        if (!$enabled) {
            self::$buttons = [];
            return self::$buttons;
        }

        // Check if the currency is allowed
        $currency = $context->getCurrency()
            ->getIsoCode();
        if ($currency !== Settings::ALLOWED_CURRENCY) {
            self::$buttons = [];
            return self::$buttons;
        }

        //Check Sale channel countries
        if (!$this->matchAllowedCountry($context)) {
            self::$buttons = [];
            return self::$buttons;
        }

        self::$buttons = $context->getCurrentCustomerGroup()->getDisplayGross() ? $settings->getScreens() : [];

        return self::$buttons;
    }

    private function matchAllowedCountry(SalesChannelContext $context): bool
    {
        $countries = $this->getCountries($context);
        /** @var CountryEntity $country */
        foreach ($countries as $country) {
            if ($country->getIso() === Settings::ALLOWED_COUNTRY) {
                return true;
            }
        }

        return false;
    }
}

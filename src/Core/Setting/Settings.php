<?php

declare(strict_types=1);

namespace Twint\Core\Setting;

class Settings
{
    public const ENVIRONMENT_PROD = false;

    public const ENVIRONMENT_TEST = true;

    public const PREFIX = 'TwintPayment.';

    public const PREFIX_REGULAR = self::PREFIX . 'settings.';

    public const PREFIX_EXPRESS = self::PREFIX . 'express.';

    public const TEST_MODE = self::PREFIX_REGULAR . 'testMode';

    public const MERCHANT_ID = self::PREFIX_REGULAR . 'merchantId';

    public const CERTIFICATE = self::PREFIX_REGULAR . 'certificate';

    public const SCREENS = self::PREFIX_EXPRESS . 'screens';

    public const ALLOWED_CURRENCIES = ['CHF', 'EUR'];

    // Screen options
    public const SCREENS_OPTIONS_PLP = 'PLP';

    public const SCREENS_OPTIONS_PDP = 'PDP';

    public const SCREENS_OPTIONS_CART = 'CART';

    public const SCREENS_OPTIONS_CART_FLYOUT = 'CART_FLYOUT';

    public const ONLY_PICK_ORDERS_FROM_MINUTES = 30;

    public const DEFAULT_VALUES = [
        self::TEST_MODE => self::ENVIRONMENT_PROD,
        self::MERCHANT_ID => '',
        self::CERTIFICATE => '',
        self::SCREENS => [
            self::SCREENS_OPTIONS_PDP,
            self::SCREENS_OPTIONS_PLP,
            self::SCREENS_OPTIONS_CART,
            self::SCREENS_OPTIONS_CART_FLYOUT,
        ],
    ];
}

<?php

declare(strict_types=1);

namespace Twint\Util\Method;

use Twint\Core\Handler\TwintExpressPaymentHandler;

class ExpressPaymentMethod extends AbstractMethod
{
    public const TECHNICAL_NAME = 'twint_express_checkout';

    public function getTranslations(): array
    {
        return [
            'de-CH' => [
                'description' => '',
                'name' => 'TWINT Express Checkout',
            ],
            'en-GB' => [
                'description' => '',
                'name' => 'TWINT Express Checkout',
            ],
        ];
    }

    public function getPosition(): int
    {
        return -999;
    }

    public function getHandler(): string
    {
        return TwintExpressPaymentHandler::class;
    }

    public function getTechnicalName(): string
    {
        return static::TECHNICAL_NAME;
    }

    public function getInitialState(): bool
    {
        return true;
    }

    public function getMediaFileName(): ?string
    {
        return 'twint';
    }
}

<?php

declare(strict_types=1);

namespace Twint\Util\Method;

use Twint\Core\Handler\TwintRegularPaymentHandler;

class RegularPaymentMethod extends AbstractMethod
{
    public const TECHNICAL_NAME = 'twint_checkout';

    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'description' => 'TWINT DE - Regular checkout payment - supported by TWINT',
                'name' => 'TWIN - DE Regular Checkout',
            ],
            'en-GB' => [
                'description' => 'TWINT - Regular checkout payment - supported by TWINT',
                'name' => 'TWINT - Regular Checkout',
            ],
        ];
    }

    public function getPosition(): int
    {
        return 0;
    }

    public function getHandler(): string
    {
        return TwintRegularPaymentHandler::class;
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

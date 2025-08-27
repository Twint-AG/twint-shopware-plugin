<?php

declare(strict_types=1);

namespace Twint\Util\Method;

class RegularPaymentMethod extends AbstractMethod
{
    public const TECHNICAL_NAME = 'twint_checkout';

    public const HANDLER = 'twint.regular.handler';

    public function getTranslations(): array
    {
        return [
            'de-CH' => [
                'description' => '',
                'name' => 'TWINT',
            ],
            'en-GB' => [
                'description' => '',
                'name' => 'TWINT',
            ],
        ];
    }

    public function getPosition(): int
    {
        return -998;
    }

    public function getHandler(): string
    {
        return self::HANDLER;
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

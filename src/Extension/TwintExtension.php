<?php

declare(strict_types=1);

namespace Twint\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twint\ExpressCheckout\Service\ExpressCheckoutButtonService;

class TwintExtension extends AbstractExtension
{
    private bool $plpRendered = false;

    public function getFunctions(): array
    {
        return [
            new TwigFunction('isTwintExpress', function (string $screen): bool {
                return $this->button($screen);
            }),
            new TwigFunction('isPlpRendered', function (): bool {
                return $this->plpRendered;
            }),
            new TwigFunction('resetPlpRendered', function (): void {
                $this->plpRendered = false;
            }),
            new TwigFunction('markPlpRendered', function (): void {
                $this->plpRendered = true;
            }),
        ];
    }

    public function button(string $screen): bool
    {
        return ExpressCheckoutButtonService::isEnabled($screen);
    }
}

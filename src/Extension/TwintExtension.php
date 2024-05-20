<?php

declare(strict_types=1);

namespace Twint\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twint\ExpressCheckout\Service\ExpressCheckoutButtonService;

class TwintExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [new TwigFunction('isTwintExpress', function (string $screen): bool {
            return $this->button($screen);
        })];
    }

    public function button(string $screen): bool
    {
        return ExpressCheckoutButtonService::isEnabled($screen);
    }
}

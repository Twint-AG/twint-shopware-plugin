<?php

declare(strict_types=1);

namespace Twint\Core\Service;

use Twint\Core\Model\TwintSettingStruct;

interface SettingServiceInterface
{
    public function getSetting(?string $salesChannel = null): TwintSettingStruct;

    public function validateCredentials(?string $saleChannel = null): void;
}

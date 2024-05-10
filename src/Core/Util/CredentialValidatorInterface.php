<?php

declare(strict_types=1);

namespace Twint\Core\Util;

interface CredentialValidatorInterface
{
    public function validate(array $certificate, string $merchantId, bool $testMode): bool;
}

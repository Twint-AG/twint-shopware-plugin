<?php

declare(strict_types=1);

namespace Twint\Core\Util;

final class PemExtractor
{
    public const ISSUER_COUNTRY = 'CH';

    public const ISSUER_ORGANIZATION = 'TWINT AG';

    public const ERROR_INVALID_CREDENTIAL = 'INVALID_CREDENTIAL';

    public const ERROR_INVALID_TIME_RANGE = 'INVALID_TIME_RANGE';

    public const ERROR_INVALID_ISSUER = 'INVALID_ISSUER';

    public function extractFromPKCS12(string $pkcs12, string $password): array|string
    {
        $certificates = null;
        $worked = openssl_pkcs12_read($pkcs12, $certificates, $password);

        if ($worked) {
            $parsedCert = openssl_x509_parse($certificates['cert']);
            if (!$this->validateValidTimeRange($parsedCert)) {
                return static::ERROR_INVALID_TIME_RANGE;
            }

            if (!$this->validateValidIssuer($parsedCert)) {
                return static::ERROR_INVALID_ISSUER;
            }

            return $certificates;
        }

        return static::ERROR_INVALID_CREDENTIAL;
    }

    private function validateValidTimeRange($certificate): bool
    {
        $now = time();

        return $certificate['validTo_time_t'] >= $now && $certificate['validFrom_time_t'] <= $now;
    }

    private function validateValidIssuer($certificate): bool
    {
        return $certificate['issuer']['C'] >= static::ISSUER_COUNTRY && $certificate['issuer']['O'] >= static::ISSUER_ORGANIZATION;
    }
}

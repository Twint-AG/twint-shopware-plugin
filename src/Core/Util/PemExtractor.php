<?php

declare(strict_types=1);

namespace Twint\Core\Util;

final class PemExtractor
{
    public const ISSUER_COUNTRIES = ['CH'];

    public const ISSUER_ORGANIZATIONS = ['TWINT AG'];

    public const ERROR_INVALID_CREDENTIAL = 'INVALID_CREDENTIAL';

    public const ERROR_INVALID_TIME_RANGE = 'INVALID_TIME_RANGE';

    public const ERROR_INVALID_ISSUER = 'INVALID_ISSUER';

    public const ERROR_PARSING_CERTIFICATE = 'ERROR_PARSING_CERTIFICATE';

    /**
     * Extracts certificates from a PKCS12 file.
     *
     * @param string $pkcs12 The PKCS12 file data.
     * @param string $password The password to decrypt the PKCS12 file.
     * @return array|string An array containing the extracted certificates, or an error string if the extraction fails.
     */
    public function extractFromPKCS12(string $pkcs12, string $password): array|string
    {
        $certificates = null;
        $worked = openssl_pkcs12_read($pkcs12, $certificates, $password);

        if (!$worked) {
            return static::ERROR_INVALID_CREDENTIAL;
        }

        $parsedCert = openssl_x509_parse($certificates['cert']);
        if ($parsedCert === false) {
            return static::ERROR_PARSING_CERTIFICATE;
        }

        if (!$this->validateValidTimeRange($parsedCert)) {
            return static::ERROR_INVALID_TIME_RANGE;
        }

        if (!$this->validateValidIssuer($parsedCert)) {
            return static::ERROR_INVALID_ISSUER;
        }

        return $certificates;
    }

    /**
     * Validates if the given certificate is valid within its validity period.
     *
     * @param array $certificate An array containing the certificate data, including 'validFrom_time_t' and 'validTo_time_t' keys.
     * @return bool true if the certificate is valid within its validity period, false otherwise.
     */
    private function validateValidTimeRange(array $certificate): bool
    {
        $currentTime = time();
        $validFrom = $certificate['validFrom_time_t'] ?? null;
        $validTo = $certificate['validTo_time_t'] ?? null;

        if ($validFrom === null || $validTo === null) {
            // Handle missing validity period data
            return false;
        }

        return $currentTime >= $validFrom && $currentTime <= $validTo;
    }

    /**
     * Validates the issuer of the given certificate.
     *
     * @param array $certificate An array containing the parsed certificate data, including the 'issuer' key.
     * @return bool true if the issuer is valid, false otherwise.
     */
    private function validateValidIssuer(array $certificate): bool
    {
        $issuer = $certificate['issuer'] ?? null;
        if ($issuer === null || !is_array($issuer)) {
            // Handle missing or invalid issuer data
            return false;
        }

        $countryCode = $issuer['C'] ?? null;
        $organization = $issuer['O'] ?? null;

        if ($countryCode === null || $organization === null) {
            // Handle missing required issuer fields
            return false;
        }

        return in_array($countryCode, static::ISSUER_COUNTRIES, true) && in_array(
            $organization,
            static::ISSUER_ORGANIZATIONS,
            true
        );
    }
}

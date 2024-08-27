<?php

declare(strict_types=1);

namespace Twint\Core\Util;

use Exception;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Validation;
use Twint\Sdk\Certificate\CertificateContainer;
use Twint\Sdk\Certificate\Pkcs12Certificate;
use Twint\Sdk\Client;
use Twint\Sdk\Io\InMemoryStream;
use Twint\Sdk\Value\Environment;
use Twint\Sdk\Value\StoreUuid;
use Twint\Sdk\Value\Version;

class CredentialValidator implements CredentialValidatorInterface
{
    public function __construct(readonly CryptoHandler $crypto)
    {
    }

    public function validate(array $certificate, string $storeUuid, bool $testMode): bool
    {
        try {
            $validator = Validation::createValidator();
            $storeUuidViolations = $validator->validate(
                $storeUuid,
                [
                    new NotBlank(),
                    new Length([
                        'max' => 36,
                    ]),
                ]
            );
            $certificateViolations = $validator->validate(
                $certificate['certificate'] ?? '',
                [
                    new NotNull(),
                    new Length([
                        'max' => 64 * 1024,
                    ]),
                ]
            );
            $passphraseViolations = $validator->validate(
                $certificate['passphrase'] ?? '',
                [
                    new NotNull(),
                    new Length([
                        'max' => 1024,
                    ]),
                ]
            );
            if (count($storeUuidViolations) > 0 || count($certificateViolations) > 0 || count(
                $passphraseViolations
            ) > 0) {
                return false;
            }
            $cert = $this->crypto->decrypt($certificate['certificate']);
            $passphrase = $this->crypto->decrypt($certificate['passphrase']);

            if ($passphrase === '' || $cert === '') {
                return false;
            }

            $client = new Client(
                CertificateContainer::fromPkcs12(new Pkcs12Certificate(new InMemoryStream($cert), $passphrase)),
                StoreUuid::fromString($storeUuid),
                Version::latest(),
                $testMode ? Environment::TESTING() : Environment::PRODUCTION(),
            );
            $status = $client->checkSystemStatus();
        } catch (Exception $e) {
            return false;
        }

        return $status->isOk();
    }
}

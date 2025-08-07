<?php

declare(strict_types=1);

namespace Twint\Core\Util;

use DateTime;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\MultiInsertQueryQueue;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Validation;
use Throwable;
use Twint\Core\Setting\Settings;
use Twint\Sdk\Certificate\CertificateContainer;
use Twint\Sdk\Certificate\Pkcs12Certificate;
use Twint\Sdk\Client;
use Twint\Sdk\Io\InMemoryStream;
use Twint\Sdk\Value\Environment;
use Twint\Sdk\Value\InstallSource;
use Twint\Sdk\Value\PlatformVersion;
use Twint\Sdk\Value\PluginVersion;
use Twint\Sdk\Value\ShopPlatform;
use Twint\Sdk\Value\ShopPluginInformation;
use Twint\Sdk\Value\StoreUuid;
use Twint\Sdk\Value\Version;

class CredentialValidator implements CredentialValidatorInterface
{
    public function __construct(
        public readonly CryptoHandler $crypto,
        public readonly string $shopwareVersion,
        private readonly LoggerInterface $logger,
        public readonly Connection $connection
    ) {
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
                new ShopPluginInformation(
                    StoreUuid::fromString($storeUuid),
                    ShopPlatform::SHOPWARE(),
                    // @phpstan-ignore-next-line
                    new PlatformVersion($this->shopwareVersion),
                    new PluginVersion(Settings::PLUGIN_VERSION),
                    new InstallSource(Settings::INSTALL_SOURCE)
                ),
                Version::latest(),
                $testMode ? Environment::TESTING() : Environment::PRODUCTION(),
            );
            $status = $client->checkSystemStatus();
        } catch (Throwable $e) {
            $this->writeEventLog($this->buildLogMessage($e));
            $this->logger->error($this->buildLogMessage($e));
            return false;
        }

        return $status->isOk();
    }

    private function buildLogMessage(Throwable $e, string $message = ''): string
    {
        // Set a default message if none is provided
        if ($message === '' || $message === '0') {
            $message = 'TWINT verify certificate error: ' . $e->getMessage();
        }

        // Append details about previous exceptions recursively
        $previous = $e->getPrevious();
        if ($previous instanceof Throwable) {
            $message .= sprintf(
                "\n %s:%d %s -> %s",
                $previous->getFile(),
                $previous->getLine(),
                get_class($previous),
                $this->buildLogMessage($previous)
            );
        }

        return $message;
    }

    private function writeEventLog(string $message = ''): void
    {
        $queue = new MultiInsertQueryQueue($this->connection);
        $record = [
            'id' => Uuid::randomBytes(),
            'message' => 'admin.twint.validate',
            'level' => 1,
            'channel' => 'business_events',
            'context' => json_encode([
                'message' => $message,
            ]),
            'extra' => json_encode([]),
            'updated_at' => null,
            'created_at' => (new DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ];
        $queue->addInsert('log_entry', $record);
        $queue->execute();
    }
}

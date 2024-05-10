<?php

declare(strict_types=1);

namespace Twint\Core\Factory;

use Throwable;
use Twint\Core\Exception\InvalidConfigException;
use Twint\Core\Service\SettingService;
use Twint\Core\Util\CryptoHandler;
use Twint\Sdk\Certificate\CertificateContainer;
use Twint\Sdk\Certificate\Pkcs12Certificate;
use Twint\Sdk\Client;
use Twint\Sdk\Io\InMemoryStream;
use Twint\Sdk\Value\Environment;
use Twint\Sdk\Value\MerchantId;
use Twint\Sdk\Value\Version;

class ClientBuilder
{
    private static array $instances = [];

    public function __construct(
        private readonly SettingService $settingService,
        private readonly CryptoHandler $cryptoService
    ) {
    }

    public function build(string $salesChannelId): Client
    {
        if (isset(self::$instances[$salesChannelId])) {
            return self::$instances[$salesChannelId];
        }

        $setting = $this->settingService->getSetting($salesChannelId);
        $merchantId = $setting->getMerchantId();
        $certificate = $setting->getCertificate();
        $environment = $setting->isTestMode() ? Environment::TESTING() : Environment::PRODUCTION();
        if ($merchantId === '') {
            throw new InvalidConfigException(InvalidConfigException::ERROR_INVALID_MERCHANT_ID);
        }

        if ($certificate === []) {
            throw new InvalidConfigException(InvalidConfigException::ERROR_INVALID_CERTIFICATE);
        }

        try {
            $cert = $this->cryptoService->decrypt($certificate['certificate']);
            $passphrase = $this->cryptoService->decrypt($certificate['passphrase']);

            if ($passphrase === '' || $cert === '') {
                throw new InvalidConfigException(InvalidConfigException::ERROR_INVALID_CERTIFICATE);
            }

            $client = new Client(
                CertificateContainer::fromPkcs12(new Pkcs12Certificate(new InMemoryStream($cert), $passphrase)),
                MerchantId::fromString($merchantId),
                Version::latest(),
                $environment,
            );
            $status = $client->checkSystemStatus();
            if ($status->isOk()) {
                self::$instances[$salesChannelId] = $client;

                return $client;
            }

            throw new InvalidConfigException(InvalidConfigException::ERROR_UNAVAILABLE, 0);
        } catch (Throwable $e) {
            throw new InvalidConfigException(InvalidConfigException::ERROR_UNDEFINED, 0, $e);
        }
    }
}

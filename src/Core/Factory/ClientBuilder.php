<?php

declare(strict_types=1);

namespace Twint\Core\Factory;

use Soap\Engine\Transport;
use Throwable;
use Twint\Core\Exception\InvalidConfigException;
use Twint\Core\Service\SettingServiceInterface;
use Twint\Core\Setting\Settings;
use Twint\Core\Util\CryptoHandler;
use Twint\Sdk\Certificate\CertificateContainer;
use Twint\Sdk\Certificate\Pkcs12Certificate;
use Twint\Sdk\Client;
use Twint\Sdk\Factory\DefaultSoapEngineFactory;
use Twint\Sdk\InvocationRecorder\InvocationRecordingClient;
use Twint\Sdk\InvocationRecorder\Soap\MessageRecorder;
use Twint\Sdk\InvocationRecorder\Soap\RecordingTransport;
use Twint\Sdk\Io\InMemoryStream;
use Twint\Sdk\Value\Environment;
use Twint\Sdk\Value\MerchantId;
use Twint\Sdk\Value\PrefixedCashRegisterId;
use Twint\Sdk\Value\Version;

class ClientBuilder
{
    private static array $instances = [];

    public function __construct(
        private readonly SettingServiceInterface $settingService,
        private readonly CryptoHandler $cryptoService
    ) {
    }

    /**
     * @phpstan-type FutureVersionId = int<Version::NEXT,max>
     * @phpstan-type version = FutureVersionId
     */
    public function build(string $salesChannelId, int $version = Version::LATEST): InvocationRecordingClient
    {
        if (isset(self::$instances[$salesChannelId])) {
            return self::$instances[$salesChannelId];
        }

        $setting = $this->settingService->getSetting($salesChannelId);
        if ($setting->getValidated() === false) {
            throw new InvalidConfigException(InvalidConfigException::ERROR_NOT_VALIDATED);
        }

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
            $recorder = new MessageRecorder();

            $client = new InvocationRecordingClient(
                new Client(
                    CertificateContainer::fromPkcs12(new Pkcs12Certificate(new InMemoryStream($cert), $passphrase)),
                    new PrefixedCashRegisterId(MerchantId::fromString($merchantId), Settings::PLATFORM),
                    // @phpstan-ignore-next-line
                    new Version($version),
                    $environment,
                    soapEngineFactory: new DefaultSoapEngineFactory(
                        wrapTransport: static fn (Transport $transport) => new RecordingTransport(
                            $transport,
                            $recorder
                        )
                    )
                ),
                $recorder
            );

            self::$instances[$salesChannelId] = $client;

            return $client;
        } catch (Throwable $e) {
            throw new InvalidConfigException(InvalidConfigException::ERROR_UNDEFINED, 0, $e);
        }
    }
}

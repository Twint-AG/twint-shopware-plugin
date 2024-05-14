<?php

declare(strict_types=1);

namespace Twint\Core\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twint\Core\Event\AfterUpdateValidatedEvent;
use Twint\Core\Event\BeforeUpdateValidatedEvent;
use Twint\Core\Model\TwintSettingStruct;
use Twint\Core\Setting\Settings;
use Twint\Core\Util\CredentialValidatorInterface;

class SettingService implements SettingServiceInterface
{
    public const SYSTEM_CONFIG_DOMAIN = Settings::PREFIX;

    public function __construct(
        private readonly SystemConfigService $configService,
        private readonly CredentialValidatorInterface $validator,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * Get Twint settings from configuration.
     */
    public function getSetting(?string $salesChannel = null): TwintSettingStruct
    {
        $structData = [];
        $config = $this->configService->getDomain(self::SYSTEM_CONFIG_DOMAIN, $salesChannel, true);

        foreach ($config as $key => $value) {
            $parts = explode('.', $key);
            $lastPart = end($parts);

            if (!empty($lastPart)) {
                $structData[$lastPart] = $value;
            }
        }

        return (new TwintSettingStruct())->assign($structData);
    }

    /**
     * Validate Twint credentials.
     * And store the validation status in the configuration.
     */
    public function validateCredential(?string $saleChannel = null): void
    {
        $config = $this->configService->getDomain(self::SYSTEM_CONFIG_DOMAIN, $saleChannel, true);
        $valid = $this->validator->validate(
            $config[Settings::CERTIFICATE],
            $config[Settings::MERCHANT_ID],
            $config[Settings::TEST_MODE]
        );

        $this->eventDispatcher->dispatch(new BeforeUpdateValidatedEvent());
        $this->configService->set(Settings::VALIDATED, $valid, $saleChannel);
        $this->eventDispatcher->dispatch(new AfterUpdateValidatedEvent());
    }
}

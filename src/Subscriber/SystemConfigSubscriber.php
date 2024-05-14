<?php

declare(strict_types=1);

namespace Twint\Subscriber;

use Shopware\Core\System\SystemConfig\Event\BeforeSystemConfigMultipleChangedEvent;
use Shopware\Core\System\SystemConfig\Event\SystemConfigMultipleChangedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twint\Core\Event\AfterUpdateValidatedEvent;
use Twint\Core\Event\BeforeUpdateValidatedEvent;
use Twint\Core\Service\SettingServiceInterface;
use Twint\Core\Setting\Settings;

class SystemConfigSubscriber implements EventSubscriberInterface
{
    private static bool $allowUpdateValidated = false;

    public function __construct(
        private readonly SettingServiceInterface $settingService
    ) {
    }

    /**
     * @return array<mixed>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SystemConfigMultipleChangedEvent::class => 'onSystemConfigChanged',
            BeforeSystemConfigMultipleChangedEvent::class => 'onBeforeSystemConfigChanged',
            BeforeUpdateValidatedEvent::class => 'onBeforeUpdateValidated',
            AfterUpdateValidatedEvent::class => 'onAfterUpdateValidated',
        ];
    }

    public function onSystemConfigChanged(SystemConfigMultipleChangedEvent $event): void
    {
        $channel = $event->getSalesChannelId();

        // Filter the config array to only include the keys that are defined TwintPayment plugin
        $keys = array_keys($event->getConfig());
        $credentialKeys = [Settings::CERTIFICATE, Settings::MERCHANT_ID, Settings::TEST_MODE];

        if (array_intersect($keys, $credentialKeys) !== []) {
            // Need to read the config values from the database and validate the credentials
            // While user is updating any of the Twint settings
            $this->settingService->validateCredential($channel);
        }
    }

    /**
     * Reset value for validated key in the config array to prevent update that config via API call
     * {
     *      "TwintPayment.settings.validated": true // This value should not be updated via API call
     * }
     */
    public function onBeforeSystemConfigChanged(BeforeSystemConfigMultipleChangedEvent $event): void
    {
        if (self::$allowUpdateValidated) {
            return;
        }

        $config = $event->getConfig();
        if (isset($config[Settings::VALIDATED])) {
            // The event doesn't allow to remove the key from the config array then need to reset the value via data from database
            $setting = $this->settingService->getSetting($event->getSalesChannelId());
            $event->setValue(Settings::VALIDATED, $setting->getValidated());
        }
    }

    public function onBeforeUpdateValidated(BeforeUpdateValidatedEvent $event): void
    {
        self::$allowUpdateValidated = true;
    }

    public function onAfterUpdateValidated(AfterUpdateValidatedEvent $event): void
    {
        self::$allowUpdateValidated = false;
    }
}

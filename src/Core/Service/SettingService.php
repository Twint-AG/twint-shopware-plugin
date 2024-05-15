<?php

declare(strict_types=1);

namespace Twint\Core\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Twint\Core\Model\TwintSettingStruct;
use Twint\Core\Setting\Settings;

class SettingService
{
    public const SYSTEM_CONFIG_DOMAIN = Settings::PREFIX;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var EntityRepository
     */
    private $repoSalesChannels;

    public function __construct(SystemConfigService $systemConfigService, EntityRepository $repoSalesChannels)
    {
        $this->systemConfigService = $systemConfigService;
        $this->repoSalesChannels = $repoSalesChannels;
    }

    /**
     * Get Twint settings from configuration.
     */
    public function getSetting(?string $salesChannelId = null): TwintSettingStruct
    {
        $structData = [];
        $systemConfigData = $this->systemConfigService->getDomain(self::SYSTEM_CONFIG_DOMAIN, $salesChannelId, true);

        foreach ($systemConfigData as $key => $value) {
            $parts = explode('.', $key);
            $lastPart = end($parts);

            if (!empty($lastPart)) {
                $structData[$lastPart] = $value;
            }
        }

        return (new TwintSettingStruct())->assign($structData);
    }

    /**
     * Gets all configurations of all sales channels.
     * Every sales channel will be a separate entry in the array.
     *
     * @return array<string, TwintSettingStruct>
     */
    public function getAllSalesChannelSetting(Context $context): array
    {
        $allConfigs = [];

        /** @var string[] $resultIDs */
        $resultIDs = $this->repoSalesChannels->searchIds(new Criteria(), $context)
            ->getIds();

        foreach ($resultIDs as $scID) {
            $allConfigs[(string) $scID] = $this->getSetting((string) $scID);
        }

        return $allConfigs;
    }

    /**
     * @param mixed $value
     */
    public function set(string $key, $value, ?string $salesChannelId = null): void
    {
        $this->systemConfigService->set(self::SYSTEM_CONFIG_DOMAIN . $key, $value, $salesChannelId);
    }

    public function delete(string $key, ?string $salesChannelId = null): void
    {
        $this->systemConfigService->delete(self::SYSTEM_CONFIG_DOMAIN . $key, $salesChannelId);
    }

    public function getIsoApps(?string $salesChannelId): array
    {
        if ($this->systemConfigService->get(Settings::TEST_MODE, $salesChannelId)) {
            $response = @file_get_contents(Settings::TESTING_APP_LIST_URL);
        } else {
            $response = @file_get_contents(Settings::PRODUCTION_APP_LIST_URL);
        }
        if (!($response === '' || $response === '0' || $response === false)) {
            $apps = json_decode($response, true);
            return $apps['appSwitchConfigList'] ?? $apps;
        }
        return [];
    }
}

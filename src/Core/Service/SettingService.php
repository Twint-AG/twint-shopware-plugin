<?php declare(strict_types=1);

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
    protected $systemConfigService;

    /**
     *
     * @var EntityRepository
     */
    private $repoSalesChannels;


    /**
     * @param SystemConfigService $systemConfigService
     * @param EntityRepository $repoSalesChannels
     */
    public function __construct(SystemConfigService $systemConfigService, EntityRepository $repoSalesChannels)
    {
        $this->systemConfigService = $systemConfigService;
        $this->repoSalesChannels = $repoSalesChannels;
    }

    /**
     * Get Twint settings from configuration.
     *
     * @param null|string $salesChannelId
     * @return TwintSettingStruct
     */
    public function getSetting(?string $salesChannelId = null): TwintSettingStruct
    {
        $structData = [];
        $systemConfigData = $this->systemConfigService->getDomain(self::SYSTEM_CONFIG_DOMAIN, $salesChannelId, true);

        foreach ($systemConfigData as $key => $value) {
            if (stripos($key, self::SYSTEM_CONFIG_DOMAIN) !== false) {
                $structData[substr($key, strlen(self::SYSTEM_CONFIG_DOMAIN))] = $value;
            } else {
                $structData[$key] = $value;
            }
        }

        return (new TwintSettingStruct())->assign($structData);
    }

    /**
     * Gets all configurations of all sales channels.
     * Every sales channel will be a separate entry in the array.
     *
     * @param Context $context
     * @return array<string, TwintSettingStruct>
     */
    public function getAllSalesChannelSetting(Context $context): array
    {
        $allConfigs = [];

        /** @var string[] $resultIDs */
        $resultIDs = $this->repoSalesChannels->searchIds(new Criteria(), $context)->getIds();

        foreach ($resultIDs as $scID) {
            $allConfigs[(string)$scID] = $this->getSettings((string)$scID);
        }

        return $allConfigs;
    }


    /**
     * @param string $key
     * @param mixed $value
     * @param null|string $salesChannelId
     */
    public function set(string $key, $value, ?string $salesChannelId = null): void
    {
        $this->systemConfigService->set(self::SYSTEM_CONFIG_DOMAIN . $key, $value, $salesChannelId);
    }

    /**
     * @param string $key
     * @param null|string $salesChannelId
     */
    public function delete(string $key, ?string $salesChannelId = null): void
    {
        $this->systemConfigService->delete(self::SYSTEM_CONFIG_DOMAIN . $key, $salesChannelId);
    }
}

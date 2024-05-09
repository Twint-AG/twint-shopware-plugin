<?php

declare(strict_types=1);

namespace Twint\Util;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Twint\Core\Setting\Settings;
use function array_map;

class ConfigInstaller
{
    public function __construct(
        private readonly EntityRepository $systemConfigRepository,
        private readonly SystemConfigService $systemConfig
    ) {
    }

    public function addDefaultConfiguration(): void
    {
        if ($this->validSettingsExists()) {
            return;
        }

        foreach (Settings::DEFAULT_VALUES as $key => $value) {
            $this->systemConfig->set($key, $value);
        }
    }

    private function validSettingsExists(): bool
    {
        return false; //TODO: Implement this method
    }

    public function removeConfiguration(Context $context): void
    {
        $criteria = (new Criteria())
            ->addFilter(new ContainsFilter('configurationKey', Settings::PREFIX));
        $idSearchResult = $this->systemConfigRepository->searchIds($criteria, $context);

        $ids = array_map(static function ($id) {
            return [
                'id' => $id,
            ];
        }, $idSearchResult->getIds());

        if ($ids === []) {
            return;
        }

        $this->systemConfigRepository->delete($ids, $context);
    }
}

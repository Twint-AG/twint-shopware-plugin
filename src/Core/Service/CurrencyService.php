<?php

declare(strict_types=1);

namespace Twint\Core\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Twint\Core\Setting\Settings;

class CurrencyService
{
    public function __construct(
        private readonly EntityRepository $repository,
    ) {
    }

    public function getCurrencyId(): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('isoCode', Settings::ALLOWED_CURRENCY));

        return $this->repository->searchIds($criteria, Context::createDefaultContext())
            ->firstId();
    }
}

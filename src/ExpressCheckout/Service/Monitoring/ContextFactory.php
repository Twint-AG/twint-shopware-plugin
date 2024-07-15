<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\Service\Monitoring;

use Shopware\Core\Framework\Util\Random;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ContextFactory
{
    protected static array $contexts = [];

    public function __construct(
        private AbstractSalesChannelContextFactory $factory,
    ) {
    }

    public function createContext(string $channelId, array $session = []): SalesChannelContext
    {
        $token = Random::getAlphanumericString(32);
        $context = $this->factory->create($token, $channelId, $session);
        self::$contexts[$channelId] = $context;

        return $context;
    }

    public function getContext(string $channelId): SalesChannelContext
    {
        return self::$contexts[$channelId] ?? $this->createContext($channelId);
    }
}

<?php

declare(strict_types=1);

namespace Twint\FastCheckout\Util;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Twint\Core\Handler\TwintRegularPaymentHandler;

final class PaymentMethodUtil
{
    private ?array $salesChannels = null;

    private ?array $paymentMethodIds = null;

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * Detect if the fast checkout is enabled for the current sales channel.
     *
     * @throws Exception
     */
    public function isFastCheckoutEnabled(
        SalesChannelContext $salesChannelContext,
        ?PaymentMethodCollection $paymentMethods = null
    ): bool {
        $methodId = $this->getFastCheckoutMethodId();
        if (!$methodId) {
            return false;
        }

        if ($paymentMethods instanceof PaymentMethodCollection) {
            return $paymentMethods->has($methodId);
        }

        $paymentMethods = $salesChannelContext->getSalesChannel()
            ->getPaymentMethods();
        if ($paymentMethods !== null) {
            return $paymentMethods->filterByProperty('active', true)
                ->has($methodId);
        }

        if ($this->salesChannels === null) {
            // skip repository for performance reasons
            $salesChannels = $this->connection->fetchFirstColumn(
                'SELECT LOWER(HEX(assoc.`sales_channel_id`))
                FROM `sales_channel_payment_method` AS assoc
                    LEFT JOIN `payment_method` AS pm
                        ON pm.`id` = assoc.`payment_method_id`
                WHERE
                    assoc.`payment_method_id` = ? AND
                    pm.`active` = 1',
                [Uuid::fromHexToBytes($methodId)]
            );

            $this->salesChannels = $salesChannels;
        }

        return in_array($salesChannelContext->getSalesChannelId(), $this->salesChannels, true);
    }

    /**
     * @throws Exception
     */
    public function getFastCheckoutMethodId(): ?string
    {
        return $this->getPaymentMethodIdByHandler(TwintRegularPaymentHandler::class);
    }

    /**
     * @throws Exception
     */
    private function getPaymentMethodIdByHandler(string $handlerIdentifier): ?string
    {
        if ($this->paymentMethodIds === null) {
            /** @var array<class-string, string> $ids */
            $ids = $this->connection->fetchAllKeyValue(
                'SELECT `handler_identifier`, LOWER(HEX(`id`)) FROM `payment_method`'
            );

            $this->paymentMethodIds = $ids;
        }

        return $this->paymentMethodIds[$handlerIdentifier] ?? null;
    }
}

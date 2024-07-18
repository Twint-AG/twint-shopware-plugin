<?php

declare(strict_types=1);

namespace Twint\Core\Service;

use DateTime;
use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

class StockManager implements StockManagerInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function increaseStock(OrderLineItemEntity $lineItem, int $quantity): void
    {
        if ($lineItem->getPayload() === null) {
            return;
        }

        # check if we have a product
        if (!isset($lineItem->getPayload()['productNumber'])) {
            return;
        }

        # extract our PRODUCT ID from the reference ID
        $productID = (string) $lineItem->getReferencedId();

        $update = $this->connection->prepare(
            'UPDATE product SET available_stock = available_stock + :refundQuantity, sales = sales - :refundQuantity, updated_at = :now WHERE id = :id'
        );

        $update->execute(
            [
                'id' => Uuid::fromHexToBytes($productID),
                'refundQuantity' => $quantity,
                'now' => (new DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]
        );
    }
}

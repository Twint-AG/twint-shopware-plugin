<?php

declare(strict_types=1);

namespace Twint\Core\DataAbstractionLayer\Entity\Pairing;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Shipping\ShippingMethodDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class PairingDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'twint_pairing';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return PairingCollection::class;
    }

    public function getEntityClass(): string
    {
        return PairingEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            (new StringField('cart_token', 'cartToken'))->setFlags(new Required()),
            (new StringField('status', 'status'))->setFlags(new Required()),
            (new StringField('token', 'token'))->setFlags(new Required()),

            (new StringField('sales_channel_id', 'salesChannelId'))->setFlags(new Required()),
            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class)),
            new ManyToOneAssociationField(
                'salesChannel',
                'sales_channel_id',
                SalesChannelDefinition::class,
                'id',
                false
            ),
            (new FkField('shipping_method_id', 'shippingMethodId', ShippingMethodDefinition::class)),
            new ManyToOneAssociationField(
                'shippingMethod',
                'shipping_method_id',
                ShippingMethodDefinition::class,
                'id',
                false
            ),
            (new FkField('order_id', 'orderId', OrderDefinition::class)),
            new ManyToOneAssociationField('order', 'order_id', OrderDefinition::class, 'id', false),
            (new JsonField('customer_data', 'customerData')),
            new CreatedAtField(),
            new UpdatedAtField(),
        ]);
    }
}

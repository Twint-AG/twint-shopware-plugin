<?php

declare(strict_types=1);

namespace Twint\Core\DataAbstractionLayer\Entity\Pairing;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Shipping\ShippingMethodDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class PairingDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'twint_pairing';

    public function getDefaults(): array
    {
        $defaults = parent::getDefaults();

        $defaults['isExpress'] = false;

        return $defaults;
    }

    public function getEntityName(): string
    {
        return static::ENTITY_NAME;
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
            (new BoolField('is_express', 'isExpress'))->setFlags(new Required()),
            (new FloatField('amount', 'amount'))->setFlags(new Required()),
            (new StringField('cart_token', 'cartToken'))->setFlags(),
            (new StringField('status', 'status'))->setFlags(new Required()),
            (new StringField('pairing_status', 'pairingStatus'))->setFlags(),
            (new StringField('transaction_status', 'transactionStatus'))->setFlags(),
            (new StringField('token', 'token'))->setFlags(),
            (new FkField('customer_id', 'customerId', CustomerDefinition::class)),
            new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class, 'id', false),
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
            new IntField('version', 'version'),
            new CreatedAtField(),
            new UpdatedAtField()
        ]);
    }
}

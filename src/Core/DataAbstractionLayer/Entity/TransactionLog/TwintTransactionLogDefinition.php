<?php

declare(strict_types=1);

namespace Twint\Core\DataAbstractionLayer\Entity\TransactionLog;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class TwintTransactionLogDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'twint_transaction_log';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return TwintTransactionLogCollection::class;
    }

    public function getEntityClass(): string
    {
        return TwintTransactionLogEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),

            (new FkField('order_id', 'orderId', OrderDefinition::class))->addFlags(new Required()),
            (new ReferenceVersionField(OrderDefinition::class, 'order_version_id'))->addFlags(new Required()),
            new ManyToOneAssociationField('order', 'order_id', OrderDefinition::class, 'id', false),

            (new StringField('transaction_id', 'transactionId'))->setFlags(new Required()),

            (new StringField('request', 'request'))->setFlags(new Required()),
            (new StringField('response', 'response'))->setFlags(new Required()),
            (new StringField('soap_request', 'soapRequest'))->setFlags(new Required()),
            (new StringField('soap_response', 'soapResponse'))->setFlags(new Required()),
            (new StringField('exception', 'exception')),
            new CreatedAtField()
        ]);
    }
}

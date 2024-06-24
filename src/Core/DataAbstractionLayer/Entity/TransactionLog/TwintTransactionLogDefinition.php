<?php

declare(strict_types=1);

namespace Twint\Core\DataAbstractionLayer\Entity\TransactionLog;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ListField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateDefinition;

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

            (new FkField('payment_state_id', 'paymentStateId', StateMachineStateDefinition::class))->addFlags(
                new Required()
            ),
            (new ManyToOneAssociationField(
                'paymentStateMachineState',
                'payment_state_id',
                StateMachineStateDefinition::class,
                'id',
                false
            ))->addFlags(new ApiAware()),

            (new FkField('order_state_id', 'orderStateId', StateMachineStateDefinition::class))->addFlags(
                new Required()
            ),
            (new ManyToOneAssociationField(
                'orderStateMachineState',
                'order_state_id',
                StateMachineStateDefinition::class,
                'id',
                false
            ))->addFlags(new ApiAware()),

            (new StringField('transaction_id', 'transactionId'))->setFlags(new Required()),
            (new ListField('api_method', 'apiMethod'))->setFlags(new Required()),
            (new LongTextField('request', 'request'))->setFlags(new Required()),
            (new LongTextField('response', 'response'))->setFlags(new Required()),
            (new ListField('soap_request', 'soapRequest'))->setFlags(new Required()),
            (new ListField('soap_response', 'soapResponse'))->setFlags(new Required()),
            (new LongTextField('exception', 'exception')),
            new CreatedAtField(),
        ]);
    }
}

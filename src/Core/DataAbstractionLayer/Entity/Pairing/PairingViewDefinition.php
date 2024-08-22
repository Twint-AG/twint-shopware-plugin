<?php

declare(strict_types=1);

namespace Twint\Core\DataAbstractionLayer\Entity\Pairing;

use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class PairingViewDefinition extends PairingDefinition
{
    final public const ENTITY_NAME = 'twint_pairing_view';

    protected function defineFields(): FieldCollection
    {
        $collection = parent::defineFields();
        $collection->add(new IntField('checked_ago', 'checkedAgo'));

        return $collection;
    }

    public function getCollectionClass(): string
    {
        return PairingCollection::class;
    }

    public function getEntityClass(): string
    {
        return PairingEntity::class;
    }

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }
}

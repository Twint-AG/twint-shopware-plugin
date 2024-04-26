<?php declare(strict_types=1);

namespace Twint\Core\Content\No;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void add(NoEntity $entity)
 * @method void set(string $key, NoEntity $entity)
 * @method NoEntity[] getIterator()
 * @method NoEntity[] getElements()
 * @method NoEntity|null get(string $key)
 * @method NoEntity|null first()
 * @method NoEntity|null last()
 */
class NoCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return NoEntity::class;
    }
}

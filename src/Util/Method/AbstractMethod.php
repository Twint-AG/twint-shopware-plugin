<?php declare(strict_types=1);

namespace Twint\Util\Method;

use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractMethod
{
    /**
     * @internal
     */
    final public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return array<string, array<string, string>>
     */
    abstract public function getTranslations(): array;

    abstract public function getPosition(): int;

    abstract public function getHandler(): string;

    abstract public function getTechnicalName(): string;

    abstract public function getInitialState(): bool;

    abstract public function getMediaFileName(): ?string;
}

<?php

declare(strict_types=1);

namespace Twint\Util;

use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twint\Util\Method\AbstractMethod;
use Twint\Util\Method\RegularPaymentMethod;

final class PaymentMethodRegistry
{
    private const PAYMENT_METHODS = [RegularPaymentMethod::class];

    /**
     * @var AbstractMethod[]
     */
    private array $methods;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly EntityRepository $paymentMethodRepository,
        ?array $methods
    ) {
        $this->methods = $methods ?? [];
    }

    public function getPaymentMethods(): array
    {
        if ($this->methods === []) {
            foreach (self::PAYMENT_METHODS as $method) {
                $this->methods[$method] = new $method($this->container);
            }
        }
        return $this->methods;
    }

    public function getEntityIdFromData(AbstractMethod $method, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', $method->getHandler()));

        return $this->paymentMethodRepository->searchIds($criteria, $context)
            ->firstId();
    }

    public function getEntityFromData(AbstractMethod $method, Context $context): ?PaymentMethodEntity
    {
        $criteria = new Criteria();
        $criteria->addAssociation('availabilityRule');
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', $method->getHandler()));

        /** @var PaymentMethodEntity|null $paymentMethod */
        $paymentMethod = $this->paymentMethodRepository->search($criteria, $context)
            ->first();

        return $paymentMethod;
    }
}

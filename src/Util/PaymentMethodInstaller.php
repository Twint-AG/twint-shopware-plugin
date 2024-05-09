<?php

declare(strict_types=1);

namespace Twint\Util;

use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\RestrictDeleteViolationException;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\Framework\Uuid\Uuid;
use Twint\TwintPayment;
use Twint\Util\Method\AbstractMethod;
use function array_map;
use function preg_match;

class PaymentMethodInstaller
{
    private EntityRepository $paymentMethodRepository;

    private EntityRepository $ruleRepository;

    private PluginIdProvider $pluginIdProvider;

    private PaymentMethodRegistry $methodRegistry;

    private MediaInstaller $mediaInstaller;

    /**
     * @internal
     */
    public function __construct(
        EntityRepository $paymentMethodRepository,
        EntityRepository $ruleRepository,
        PluginIdProvider $pluginIdProvider,
        PaymentMethodRegistry $methodRegistry,
        MediaInstaller $mediaInstaller
    ) {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->ruleRepository = $ruleRepository;
        $this->pluginIdProvider = $pluginIdProvider;
        $this->methodRegistry = $methodRegistry;
        $this->mediaInstaller = $mediaInstaller;
    }

    public function installAll(Context $context): void
    {
        $pluginId = $this->pluginIdProvider->getPluginIdByBaseClass(TwintPayment::class, $context);

        $upsertData = [];
        $translationData = [];
        $paymentMethods = [];
        foreach ($this->methodRegistry->getPaymentMethods() as $method) {
            $data = $this->getPaymentMethodData($method, $pluginId, $context);
            $upsertData[] = $data;

            $translationData[] = [
                'id' => $data['id'],
                'translations' => $method->getTranslations(),
            ];

            $paymentMethods[$data['id']] = $method;
        }

        $this->paymentMethodRepository->upsert($upsertData, $context);
        $this->paymentMethodRepository->upsert($translationData, $context);

        /** @var string $paymentMethodId */
        foreach ($paymentMethods as $paymentMethodId => $method) {
            $this->mediaInstaller->installPaymentMethodMedia($method, $paymentMethodId, $context);
        }
    }

    public function removeRules(Context $context): void
    {
        $ruleRemovals = [];
        $paymentMethodUpdates = [];

        foreach ($this->methodRegistry->getPaymentMethods() as $method) {
            $entity = $this->methodRegistry->getEntityFromData($method, $context);
            if (!$entity instanceof PaymentMethodEntity) {
                continue;
            }

            $rule = $entity->getAvailabilityRule();
            if ($rule === null) {
                continue;
            }

            if (!preg_match('/Twint.+AvailabilityRule/', $rule->getName())) {
                continue;
            }

            $ruleRemovals[] = [
                'id' => $rule->getId(),
            ];

            $paymentMethodUpdates[] = [
                'id' => $entity->getId(),
                'availabilityRuleId' => null,
            ];
        }

        if ($ruleRemovals === []) {
            return;
        }

        if ($paymentMethodUpdates !== []) {
            $this->paymentMethodRepository->update($paymentMethodUpdates, $context);
        }

        try {
            $this->ruleRepository->delete($ruleRemovals, $context);
        } catch (RestrictDeleteViolationException $e) {
        }
    }

    private function getPaymentMethodData(AbstractMethod $method, string $pluginId, Context $context): array
    {
        $translations = $method->getTranslations();
        $defaultTranslation = $translations['en-GB'];

        $paymentMethodData = [
            'id' => Uuid::randomHex(),
            'handlerIdentifier' => $method->getHandler(),
            'name' => $defaultTranslation['name'],
            'technicalName' => $method->getTechnicalName(),
            'position' => $method->getPosition(),
            'afterOrderEnabled' => true,
            'pluginId' => $pluginId,
            'description' => $defaultTranslation['description'],
        ];

        $existingMethodId = $this->methodRegistry->getEntityIdFromData($method, $context);
        if ($existingMethodId) {
            $paymentMethodData['id'] = $existingMethodId;
        }

        return $paymentMethodData;
    }

    public function setAllPaymentStatus(bool $active, Context $context): void
    {
        $handlers = [];
        foreach ($this->methodRegistry->getPaymentMethods() as $paymentMethod) {
            if (!$active || $paymentMethod->getInitialState()) {
                $handlers[] = $paymentMethod->getHandler();
            }
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('handlerIdentifier', $handlers));
        /** @var string[] $ids */
        $ids = $this->paymentMethodRepository->searchIds($criteria, $context)
            ->getIds();

        if (!$ids) {
            return;
        }

        $this->paymentMethodRepository->update(array_map(static function (string $id) use ($active) {
            return [
                'id' => $id,
                'active' => $active,
            ];
        }, $ids), $context);
    }

    public function removeAllPaymentMethods(Context $context): void
    {
        $ids = $this->getPaymentMethodIds($context);

        if ($ids === []) {
            return;
        }

        $this->paymentMethodRepository->delete(array_map(static function (string $id) {
            return [
                'id' => $id,
            ];
        }, $ids), $context);
    }

    private function getPaymentMethodIds(Context $context): array
    {
        $handlers = [];
        foreach ($this->methodRegistry->getPaymentMethods() as $paymentMethod) {
            $handlers[] = $paymentMethod->getHandler();
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('handlerIdentifier', $handlers));
        return $this->paymentMethodRepository->searchIds($criteria, $context)
            ->getIds();
    }
}

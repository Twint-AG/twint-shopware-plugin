<?php

declare(strict_types=1);

namespace Twint\Util;

use Exception;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetEntity;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class OrderCustomFieldInstaller
{
    public const TWINT_CUSTOM_FIELD_SET = 'twint_payment_custom_field_set';

    public const TWINT_API_RESPONSE_CUSTOM_FIELD = 'twint_api_response';

    private EntityRepository $customFieldSetRepository;

    private EntityRepository $customFieldRepository;

    private EntityRepository $snippetRepository;

    /**
     * @internal
     */
    public function __construct(
        EntityRepository $customFieldSetRepository,
        EntityRepository $customFieldRepository,
        EntityRepository $snippetRepository
    ) {
        $this->customFieldSetRepository = $customFieldSetRepository;
        $this->customFieldRepository = $customFieldRepository;
        $this->snippetRepository = $snippetRepository;
    }

    public function install(Context $context): void
    {
        $twintApiResponseCustomerFieldId = Uuid::randomHex();
        try {
            $this->customFieldSetRepository->upsert([[
                'id' => Uuid::randomHex(),
                'name' => self::TWINT_CUSTOM_FIELD_SET,
                'active' => true,
                'config' => [
                    'label' => [
                        'en-GB' => 'Twint',
                        'de-DE' => 'Twint',
                    ],
                ],
                'customFields' => [
                    [
                        'id' => $twintApiResponseCustomerFieldId,
                        'name' => self::TWINT_API_RESPONSE_CUSTOM_FIELD,
                        'type' => CustomFieldTypes::JSON,
                        'config' => [
                            'componentName' => 'sw-field',
                            'customFieldType' => CustomFieldTypes::JSON,
                            'customFieldPosition' => 1,
                            'label' => [
                                'en-GB' => 'Twint API Response',
                                'de-DE' => 'Twint-API-Antwort',
                            ],
                        ],
                    ],
                ],
                'relations' => [
                    [
                        'id' => $twintApiResponseCustomerFieldId,
                        'entityName' => OrderDefinition::ENTITY_NAME,
                    ],
                ],
            ]], $context);
        } catch (Exception $e) {
            // @todo Handle Exception
        }
    }

    public function uninstall(Context $context): void
    {
        //delete custom_field_set entry
        $customFieldSetId = $this->customFieldSetRepository->search(
            (new Criteria())
                ->addFilter(new EqualsFilter('name', self::TWINT_CUSTOM_FIELD_SET)),
            $context
        )->first();
        if ($customFieldSetId instanceof CustomFieldSetEntity) {
            $this->customFieldSetRepository->delete([
                [
                    'id' => $customFieldSetId->getId(),
                ],
            ], $context);
            //delete custom_field entries
            $customFieldIds = $this->customFieldRepository->search((new Criteria())
                ->addFilter(new EqualsFilter('customFieldSetId', $customFieldSetId->getId())), $context)->getIds();
            $ids = [];
            if ($customFieldIds) {
                foreach ($customFieldIds as $id) {
                    $ids[] = [
                        'id' => $id,
                    ];
                }
                $this->customFieldRepository->delete($ids, $context);
            }

            // delete snippet entries
            $snippetEntries = $this->snippetRepository->search((new Criteria())
                ->addFilter(
                    new MultiFilter(
                        MultiFilter::CONNECTION_OR,
                        [new ContainsFilter('translationKey', self::TWINT_API_RESPONSE_CUSTOM_FIELD)]
                    )
                ), $context)->getIds();
            $snippetIds = [];
            if ($snippetEntries) {
                foreach ($snippetEntries as $id) {
                    $snippetIds[] = [
                        'id' => $id,
                    ];
                }
                $this->snippetRepository->delete($snippetIds, $context);
            }
        }
    }
}

<?php
declare(strict_types=1);

namespace Twint\Tests\Helper;


use Shopware\Core\Checkout\Cart\CartRuleLoader;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Checkout\Order\SalesChannel\OrderService;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\PlatformRequest;
use Shopware\Core\SalesChannelRequest;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateDefinition;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\Loader\InitialStateIdLoader;
use Shopware\Core\System\StateMachine\StateMachineDefinition;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;
use Shopware\Core\Test\Integration\PaymentHandler\SyncTestPaymentHandler;
use Shopware\Core\Test\TestDefaults;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Shopware\Storefront\Framework\Routing\RequestTransformer;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Twint\Util\Method\RegularPaymentMethod;

trait ServicesTrait
{
    use IntegrationTestBehaviour;

    protected function createSalesChannel(string $id, array $additionalData = []): void
    {
        /** @var EntityRepository $salesChannelRepository */
        $salesChannelRepository = $this->getContainer()->get(\sprintf('%s.repository', SalesChannelDefinition::ENTITY_NAME));
        $data = [
            'id' => $id,
            'typeId' => Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
            'languageId' => Defaults::LANGUAGE_SYSTEM,
            'languages' => $additionalData['languages'] ?? [['id' => Defaults::LANGUAGE_SYSTEM]],
            'customerGroupId' => $this->getValidCustomerGroupId(),
            'currencyId' => Defaults::CURRENCY,
            'paymentMethodId' => $this->getValidPaymentMethodId(),
            'shippingMethodId' => $this->getValidShippingMethodId(),
            'countryId' => $this->getValidCountryId(),
            'navigationCategoryId' => $this->getValidCategoryId(),
            'accessKey' => 'testAccessKey',
            'name' => 'Test SalesChannel',
        ];

        $data = \array_merge($data, $additionalData);

        $salesChannelRepository->create([$data], Context::createDefaultContext());
    }

    protected function createProduct(string $productId, ?string $taxId = null, array $additionalData = []): void
    {
        /** @var EntityRepository $productRepository */
        $productRepository = $this->getContainer()->get(\sprintf('%s.repository', ProductDefinition::ENTITY_NAME));

        $productData = [
            'id' => $productId,
            'stock' => \random_int(1, 5),
            'taxId' => $taxId ?? $this->getValidTaxId(),
            'price' => [
                'net' => [
                    'currencyId' => Defaults::CURRENCY,
                    'net' => 74.49,
                    'gross' => 89.66,
                    'linked' => true,
                ],
            ],
            'productNumber' => 'test-234',
            'translations' => [
                Defaults::LANGUAGE_SYSTEM => [
                    'name' => 'example-product',
                ],
            ],
            'visibilities' => [
                [
                    'salesChannelId' => TestDefaults::SALES_CHANNEL,
                    'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                ],
            ],
        ];

        $productData = \array_merge($productData, $additionalData);

        $productRepository->create(
            [
                $productData,
            ],
            Context::createDefaultContext()
        );
    }

    /**
     * @param string $customerId
     * @param Context $context
     * @param array $customFields
     * @return OrderEntity
     * @throws \JsonException
     */
    private function createOrder(string $customerId, Context $context, array $customFields, ?string $paymentMethodId = null): OrderEntity
    {
        if(!$paymentMethodId){
            $paymentMethodId = $this->getValidPaymentMethodId();
        }
        /** @var EntityRepository $orderRepository */
        $orderRepository = $this->getContainer()->get(\sprintf('%s.repository', OrderDefinition::ENTITY_NAME));
        $orderId = Uuid::randomHex();
        /** @var InitialStateIdLoader $initialStateIdLoader */
        $initialStateIdLoader = $this->getContainer()->get(InitialStateIdLoader::class);
        $stateId = $initialStateIdLoader->get(OrderStates::STATE_MACHINE);
        $transactionStateId = $initialStateIdLoader->get(OrderTransactionStates::STATE_MACHINE);
        $transactionStateId = $this->getPaymentInProgressStateId();
        $billingAddressId = Uuid::randomHex();

        $order = [
            'id' => $orderId,
            'itemRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
            'totalRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
            'orderNumber' => Uuid::randomHex(),
            'orderDateTime' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            'price' => new CartPrice(10, 10, 10, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_NET),
            'shippingCosts' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
            'orderCustomer' => [
                'customerId' => $customerId,
                'email' => 'test@example.com',
                'salutationId' => $this->getValidSalutationId(),
                'firstName' => 'Max',
                'lastName' => 'Mustermann',
            ],
            'stateId' => $stateId,
            'paymentMethodId' => $paymentMethodId,
            'currencyId' => Defaults::CURRENCY,
            'currencyFactor' => 1.0,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'billingAddressId' => $billingAddressId,
            'addresses' => [
                [
                    'id' => $billingAddressId,
                    'salutationId' => $this->getValidSalutationId(),
                    'firstName' => 'Max',
                    'lastName' => 'Mustermann',
                    'street' => 'Ebbinghoff 10',
                    'zipcode' => '48624',
                    'city' => 'Schöppingen',
                    'countryId' => $this->getValidCountryId(),
                ],
            ],
            'lineItems' => [
                [
                    'id' => Uuid::randomHex(),
                    'identifier' => Uuid::randomHex(),
                    'quantity' => 1,
                    'label' => 'label',
                    'type' => LineItem::CREDIT_LINE_ITEM_TYPE,
                    'price' => new CalculatedPrice(200, 200, new CalculatedTaxCollection(), new TaxRuleCollection()),
                    'priceDefinition' => new QuantityPriceDefinition(200, new TaxRuleCollection(), 2),
                ],
            ],
            "transactions" => [
                [
                    "paymentMethodId" => $paymentMethodId,
                    "amount" =>  [
                        "unitPrice" => 13.98,
                        "totalPrice" => 13.98,
                        "quantity" => 1,
                        "calculatedTaxes" => [
                            [
                                "tax" => 0,
                                "taxRate" => 0,
                                "price" => 0
                            ]
                        ],
                        "taxRules" => [
                            [
                                "taxRate" => 0,
                                "percentage" => 100
                            ]
                        ]
                    ],
                    "stateId" => $transactionStateId
                ]
            ],
            'customFields' => $customFields,
            'deliveries' => [
            ],
            'context' => '{}',
            'payload' => '{}',
            'createdAt' => new \DateTime(),
            'updatedAt' => new \DateTime(),
        ];

        $orderRepository->upsert([$order], $context);
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions');
        /** @var OrderEntity|null $order */
        $order = $orderRepository->search($criteria, $context)->first();
        if($order instanceof OrderEntity){
            return $order;
        }
    }

    /**
     * @return string
     */
    protected function getValidCustomerGroupId(): string
    {
        /** @var EntityRepository $customerGroupRepository */
        $customerGroupRepository = $this->getContainer()->get(\sprintf('%s.repository', CustomerGroupDefinition::ENTITY_NAME));
        $customerGroupId = $customerGroupRepository->searchIds(new Criteria(), Context::createDefaultContext())->firstId();
        if ($customerGroupId === null) {
            throw new \RuntimeException('No customer group id could be found');
        }

        return $customerGroupId;
    }
    /**
     * @param array<string, mixed> $customerOverride
     */
    private function createCustomer(?string $email = null, ?bool $guest = false, array $customerOverride = []): string
    {
        $customerId = Uuid::randomHex();
        $addressId = Uuid::randomHex();

        if ($email === null) {
            $email = Uuid::randomHex() . '@example.com';
        }

        $customer = array_merge([
            'id' => $customerId,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'defaultShippingAddress' => [
                'id' => $addressId,
                'firstName' => 'Max',
                'lastName' => 'Mustermann',
                'street' => 'Musterstraße 1',
                'city' => 'Schöppingen',
                'zipcode' => '12345',
                'salutationId' => $this->getValidSalutationId(),
                'countryId' => $this->getValidCountryId(),
            ],
            'defaultBillingAddressId' => $addressId,
            'defaultPaymentMethod' => [
                'name' => 'Invoice',
                'active' => true,
                'description' => 'Default payment method',
                'handlerIdentifier' => SyncTestPaymentHandler::class,
                'technicalName' => Uuid::randomHex(),
                'availabilityRule' => [
                    'id' => Uuid::randomHex(),
                    'name' => 'true',
                    'priority' => 0,
                    'conditions' => [
                        [
                            'type' => 'cartCartAmount',
                            'value' => [
                                'operator' => '>=',
                                'amount' => 0,
                            ],
                        ],
                    ],
                ],
                'salesChannels' => [
                    [
                        'id' => TestDefaults::SALES_CHANNEL,
                    ],
                ],
            ],
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'email' => $email,
            'password' => TestDefaults::HASHED_PASSWORD,
            'firstName' => 'Max',
            'lastName' => 'Mustermann',
            'guest' => $guest,
            'salutationId' => $this->getValidSalutationId(),
            'customerNumber' => '12345',
        ], $customerOverride);

        $customerId = $customer['id'];

        /** @var EntityRepository $customerRepository */
        $customerRepository = $this->getContainer()->get('customer.repository');

        $customerRepository->create([$customer], Context::createDefaultContext());

        return $customerId;
    }

    /**
     * @param array<string, string> $salesChannel
     * @param array<string, mixed> $options
     */
    private function createContext(array $salesChannel, array $options): SalesChannelContext
    {
        /** @var SalesChannelContextFactory $factory */
        $factory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $context = $factory->create(Uuid::randomHex(), $salesChannel['id'], $options);

        /** @var CartRuleLoader $ruleLoader */
        $ruleLoader = $this->getContainer()->get(CartRuleLoader::class);
        $ruleLoader->loadByToken($context, $context->getToken());

        return $context;
    }
    /**
     * @param string $email
     * @return KernelBrowser
     */
    private function login(string $email): KernelBrowser
    {
        $browser = KernelLifecycleManager::createBrowser($this->getKernel());
        $browser->request(
            'POST',
            $_SERVER['APP_URL'] . '/account/login',
            $this->tokenize('frontend.account.login', [
                'username' => $email,
                'password' => 'shopware',
            ])
        );
        $response = $browser->getResponse();
        static::assertSame(200, $response->getStatusCode(), $response->getContent());

        return $browser;
    }
    /**
     * @param array<string, mixed> $params
     */
    private function createRequest(string $route, array $params = []): Request
    {
        $request = new Request();
        $request->query->add($params);
        $request->setSession($this->getSession());
        $request->headers->set('HOST', 'localhost');
        $request->attributes->add([
            '_route' => $route,
            SalesChannelRequest::ATTRIBUTE_IS_SALES_CHANNEL_REQUEST => true,
            PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT => $this->salesChannelContext,
            RequestTransformer::STOREFRONT_URL => 'http://localhost',
        ]);

        return $request;
    }
    private function getOrderById($id): OrderEntity
    {
        /** @var EntityRepository $orderRepository */
        $orderRepository = $this->getContainer()->get(\sprintf('%s.repository', OrderDefinition::ENTITY_NAME));
        $criteria = new Criteria([$id]);
        $criteria->addAssociation('orderCustomer.customer')
            ->addAssociation('transactions')
            ->addAssociation('transactions.stateMachineState')
            ->addAssociation('customFields');
        /** @var OrderEntity|null $order */
        return $orderRepository->search($criteria, $this->context)->first();
    }
    public function transitionOrder(OrderEntity $order, string $action, Context $context): OrderEntity
    {
        $transactionId = $order->getTransactions()?->first()?->getId() ?? null;
        $stateMachineRegistry = $this->getContainer()->get(StateMachineRegistry::class);
        $stateMachineRegistry->transition(
            new Transition(
                OrderTransactionDefinition::ENTITY_NAME,
                $transactionId,
                $action,
                'stateId'
            ),
            $context
        );
        return $this->getOrderById($order->getId());
    }
    public function getRegularPaymentMethodId(?string $salesChannelId = null): string
    {
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get(\sprintf('%s.repository', PaymentMethodDefinition::ENTITY_NAME));
        $criteria = (new Criteria());
        $criteria->addFilter(
            new EqualsAnyFilter('technicalName', [
                RegularPaymentMethod::TECHNICAL_NAME,
            ])
        );
        $criteria->setLimit(1)
            ->addFilter(new EqualsFilter('active', true));

        if ($salesChannelId) {
            $criteria->addFilter(new EqualsFilter('salesChannels.id', $salesChannelId));
        }

        /** @var string $id */
        $id = $repository->searchIds($criteria, Context::createDefaultContext())->firstId();

        return $id;
    }
    public function getPaymentInProgressStateId(): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_MACHINE));
        $stateMachineRepository = $this->getContainer()->get(\sprintf('%s.repository', StateMachineDefinition::ENTITY_NAME));
        $stateMachineStateRepository = $this->getContainer()->get(\sprintf('%s.repository', StateMachineStateDefinition::ENTITY_NAME));
        $transactionState = $stateMachineRepository->search($criteria, $this->context)
            ->first();
        if (!empty($transactionState)) {
            $transactionStateId = $transactionState->get('id');
            $criteriaM = new Criteria();

            $criteriaM->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
                new EqualsFilter('technicalName', OrderTransactionStates::STATE_IN_PROGRESS),
                new EqualsFilter('stateMachineId', $transactionStateId),
            ]));
            $paymentInProgressState = $stateMachineStateRepository->search($criteriaM, $this->context)
                ->first();
            if (!empty($paymentInProgressState)) {
                return $paymentInProgressState->get('id');
            }
        }
        return '';
    }
}

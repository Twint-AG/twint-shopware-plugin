<?php
declare(strict_types=1);

namespace Twint\Tests\Storefront\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;
use Shopware\Core\Test\TestDefaults;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Twint\Core\DataAbstractionLayer\Entity\TransactionLog\TwintTransactionLogEntity;
use Twint\Core\Service\OrderService;
use Twint\Core\Service\PaymentService;
use Twint\Tests\Helper\ServicesTrait;
use Twint\Storefront\Controller\PaymentController;
use Twint\Core\Util\CryptoHandler;
use Shopware\Storefront\Test\Controller\StorefrontControllerTestBehaviour;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

/**
 * @internal
 */
class PaymentControllerTest extends TestCase
{
    use ServicesTrait;
    use StorefrontControllerTestBehaviour;
    use IntegrationTestBehaviour;

    private ContainerInterface $container;
    /**
     * @var PaymentController
     */
    private $paymentController;
    /**
     * @var CryptoHandler
     */
    private $crytoService;

    private SalesChannelContext $salesChannelContext;

    private string $customerId;

    private Context $context;

    private array $twintCustomFields = [];

    private StateMachineRegistry $stateMachineRegistry;

    private PaymentController $controller;
    private MockObject $orderRepository;
    private MockObject $cryptoService;
    private MockObject $paymentService;
    private MockObject $orderService;
    private MockObject $request;

    /**
     * @return string
     */
    static function getName()
    {
        return "PaymentControllerTest";
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentController = $this->getContainer()->get(PaymentController::class);
        $this->crytoService = $this->getContainer()->get(CryptoHandler::class);
        /** @var SalesChannelContextFactory $contextFactory */
        $contextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $this->salesChannelContext = $contextFactory->create('', TestDefaults::SALES_CHANNEL);
        $this->customerId = $this->createCustomer('test@example.com');
        $this->context = Context::createDefaultContext();
        $this->twintCustomFields = [
            'twint_api_response' => '{"id":"40684cd7-66a0-4118-92e0-5b06b5459f59","status":"IN_PROGRESS","transactionStatus":"ORDER_RECEIVED","pairingToken":"74562","merchantTransactionReference":"10095"}'
        ];
        $this->stateMachineRegistry = $this->getContainer()->get(StateMachineRegistry::class);


    }

    public function testValidOrder(): void{
        $order = $this->createOrder($this->customerId, $this->context, $this->twintCustomFields);
        $request = $this->createRequest('frontend.twint.waiting', ['orderNumber' => $this->crytoService->hash($order->getOrderNumber())]);
        $this->getContainer()->get('request_stack')->push($request);
        $response = $this->paymentController->showWaiting($request, $this->salesChannelContext->getContext());
        //check QR code exist or not
        static::assertStringContainsStringIgnoringCase('QR-Code', (string)$response->getContent());
    }

    public function testInvalidOrder(): void
    {
        $request = $this->createRequest('frontend.twint.waiting', ['orderNumber' => $this->crytoService->hash(Uuid::randomHex())]);
        $this->getContainer()->get('request_stack')->push($request);
        $response = $this->paymentController->showWaiting($request, $this->salesChannelContext->getContext());
        static::assertSame(302, $response->getStatusCode());
    }

    public function testOrderWithoutTwintResponse(): void
    {
        $order = $this->createOrder($this->customerId, $this->context, []);
        $request = $this->createRequest('frontend.twint.waiting', ['orderNumber' => $this->crytoService->hash($order->getOrderNumber())]);
        $this->getContainer()->get('request_stack')->push($request);
        $response = $this->paymentController->showWaiting($request, $this->salesChannelContext->getContext());
        //check QR code exist or not
        static::assertStringNotContainsString('alt="QR-Code"', (string)$response->getContent());
    }

    public function testPaidOrder(): void
    {
        $order = $this->createOrder($this->customerId, $this->context, $this->twintCustomFields);
        $transactionId = $order->getTransactions()?->first()?->getId() ?? null;
        $this->stateMachineRegistry->transition(
            new Transition(
                OrderTransactionDefinition::ENTITY_NAME,
                $transactionId,
                StateMachineTransitionActions::ACTION_PAID,
                'stateId'
            ),
            $this->context
        );
        $request = $this->createRequest('frontend.twint.waiting', ['orderNumber' => $this->crytoService->hash($order->getOrderNumber())]);
        $this->getContainer()->get('request_stack')->push($request);
        $response = $this->paymentController->showWaiting($request, $this->salesChannelContext->getContext());
        static::assertSame(302, $response->getStatusCode());
        static::assertSame('/checkout/finish?orderId='.$order->getId(), (string)$response->getTargetUrl());
    }
    public function testCancelOrder(): void
    {
        $order = $this->createOrder($this->customerId, $this->context, $this->twintCustomFields);
        $transactionId = $order->getTransactions()?->first()?->getId() ?? null;
        $this->stateMachineRegistry->transition(
            new Transition(
                OrderTransactionDefinition::ENTITY_NAME,
                $transactionId,
                StateMachineTransitionActions::ACTION_CANCEL,
                'stateId'
            ),
            $this->context
        );
        $request = $this->createRequest('frontend.twint.waiting', ['orderNumber' => $this->crytoService->hash($order->getOrderNumber())]);
        $this->getContainer()->get('request_stack')->push($request);
        $response = $this->paymentController->showWaiting($request, $this->salesChannelContext->getContext());
        static::assertSame(302, $response->getStatusCode());
        static::assertStringContainsStringIgnoringCase('/account/order/edit/'.$order->getId(), (string)$response->getTargetUrl());
    }
}

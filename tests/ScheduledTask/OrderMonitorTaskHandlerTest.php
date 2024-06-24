<?php declare(strict_types=1);

namespace Twint\Tests\ScheduledTask;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\AdminFunctionalTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\QueueTestBehaviour;
use Symfony\Component\Messenger\MessageBusInterface;
use Twint\Core\Service\OrderService;
use Twint\Core\Service\PaymentService;
use Twint\ScheduledTask\OrderMonitorTaskHandler;
use Twint\Tests\Helper\ServicesTrait;

class OrderMonitorTaskHandlerTest extends TestCase
{
    use ServicesTrait;
    use IntegrationTestBehaviour;
    use AdminFunctionalTestBehaviour;
    use QueueTestBehaviour;

    private MessageBusInterface $messageBusMock;
    private PaymentService $paymentService;
    private OrderService $orderService;
    private OrderMonitorTaskHandler $orderMonitorHandler;
    private array $twintCustomFields;

    /**
     * @return string
     */
    static function getName()
    {
        return "OrderMonitorTaskHandlerTest";
    }

    protected function setUp(): void
    {
        $this->messageBusMock = $this->createMock(MessageBusInterface::class);
        $this->paymentService = $this->getContainer()->get(PaymentService::class);
        $this->orderService = $this->getContainer()->get(OrderService::class);
        $this->twintCustomFields = [
            'twint_api_response' => '{"id":"40684cd7-66a0-4118-92e0-5b06b5459f59","status":"IN_PROGRESS","transactionStatus":"ORDER_RECEIVED","pairingToken":"74562","merchantTransactionReference":"10095"}'
        ];
        $this->orderMonitorHandler = new OrderMonitorTaskHandler(
            $this->getContainer()->get('scheduled_task.repository'),
            $this->createMock(LoggerInterface::class),
            $this->paymentService,
            $this->orderService
        );

    }
    public function testScheduledTaskExecutionWithNoMessages()
    {
        static::assertCount(0, $this->orderService->getPendingOrders());
        $this->orderMonitorHandler->run();
        $url = '/api/_action/message-queue/consume';
        $client = $this->getBrowser();
        $client->request('POST', $url, ['receiver' => 'async']);

        static::assertSame(200, $client->getResponse()->getStatusCode());

        $response = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        static::assertArrayHasKey('handledMessages', $response);
        static::assertIsInt($response['handledMessages']);
        static::assertEquals(0, $response['handledMessages']);
    }
    public function testScheduledTaskExecutionWithOneMessage()
    {
        $customerId = $this->createCustomer('test@example.com');
        $this->context = Context::createDefaultContext();
        $this->order = $this->createOrder($customerId, $this->context, $this->twintCustomFields, $this->getRegularPaymentMethodId());
        static::assertCount(1, $this->orderService->getPendingOrders());
        $this->orderMonitorHandler->run();
        $url = '/api/_action/message-queue/consume';
        $client = $this->getBrowser();
        $client->request('POST', $url, ['receiver' => 'async']);

        static::assertSame(200, $client->getResponse()->getStatusCode());

        $response = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        static::assertArrayHasKey('handledMessages', $response);
        static::assertIsInt($response['handledMessages']);
        static::assertEquals(1, $response['handledMessages']);
    }
}
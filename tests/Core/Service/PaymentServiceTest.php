<?php

declare(strict_types=1);

namespace Twint\Tests\Core\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Price\CashRounding;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\CurrencyEntity;
use Soap\Engine\Transport;
use Twint\Core\Factory\ClientBuilder;
use Twint\Core\Handler\TransactionLog\TransactionLogWriterInterface;
use Twint\Core\Service\OrderService;
use Twint\Core\Service\PaymentService;
use Twint\Core\Service\StockManagerInterface;
use Twint\Sdk\Certificate\CertificateContainer;
use Twint\Sdk\Certificate\Pkcs12Certificate;
use Twint\Sdk\Client;
use Twint\Sdk\Factory\DefaultSoapEngineFactory;
use Twint\Sdk\InvocationRecorder\InvocationRecordingClient;
use Twint\Sdk\InvocationRecorder\Soap\MessageRecorder;
use Twint\Sdk\InvocationRecorder\Soap\RecordingTransport;
use Twint\Sdk\Io\InMemoryStream;
use Twint\Sdk\Value\Environment;
use Twint\Sdk\Value\FiledMerchantTransactionReference;
use Twint\Sdk\Value\MerchantId;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\NumericPairingToken;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\OrderId;
use Twint\Sdk\Value\OrderStatus;
use Twint\Sdk\Value\PairingStatus;
use Twint\Sdk\Value\TransactionStatus;
use Twint\Sdk\Value\Version;
use Twint\Util\OrderCustomFieldInstaller;
use function Psl\Type\uint;

/**
 * @internal
 */
class PaymentServiceTest extends TestCase
{
    private const ORDER_ID = '12345678-1234-1234-1234-123456789012';

    private const PAIRING_TOKEN = 1235;

    private PaymentService $paymentService;

    private MockObject $reversalHistoryRepository;

    private MockObject $transactionStateHandler;

    private MockObject $clientBuilder;

    private MockObject $transactionLogWriter;

    private MockObject $stockManager;

    private MockObject $rounding;

    private MockObject $orderService;

    private Order $mockOrder;

    protected function setUp(): void
    {
        $this->reversalHistoryRepository = $this->createMock(EntityRepository::class);
        $this->transactionStateHandler = $this->createMock(OrderTransactionStateHandler::class);
        $this->clientBuilder = $this->createMock(ClientBuilder::class);
        $this->transactionLogWriter = $this->createMock(TransactionLogWriterInterface::class);
        $this->rounding = $this->createMock(CashRounding::class);
        $this->orderService = $this->createMock(OrderService::class);

        $this->paymentService = new PaymentService(
            $this->reversalHistoryRepository,
            $this->transactionStateHandler,
            $this->clientBuilder,
            $this->transactionLogWriter,
            $this->rounding,
            $this->orderService
        );
        $this->mockOrder = new Order(
            OrderId::fromString(self::ORDER_ID),
            new FiledMerchantTransactionReference(Uuid::randomHex()),
            OrderStatus::SUCCESS(),
            TransactionStatus::ORDER_OK(),
            Money::CHF(10),
            PairingStatus::NO_PAIRING(),
            new NumericPairingToken(uint()->assert(self::PAIRING_TOKEN)),
            null
        );
    }

    public function testCreateOrder(): void
    {
        $transaction = $this->createMock(AsyncPaymentTransactionStruct::class);
        $orderEntity = $this->createMock(OrderEntity::class);
        $orderEntity->method('getId')
            ->willReturn(Uuid::randomHex());
        $orderEntity->method('getSalesChannelId')
            ->willReturn(Uuid::randomHex());
        $currencyEntity = new CurrencyEntity();
        $currencyEntity->setIsoCode(Money::CHF);
        $orderEntity->method('getCurrency')
            ->willReturn($currencyEntity);

        $transaction->method('getOrder')
            ->willReturn($orderEntity);
        $transaction->method('getOrderTransaction')
            ->willReturn($this->createMock(OrderTransactionEntity::class));
        $client = $this->createMock(InvocationRecordingClient::class);
        $this->clientBuilder->method('build')
            ->willReturn($client);

        $client->method('startOrder')
            ->willReturn($this->mockOrder);

        $result = $this->paymentService->createOrder($transaction);

        self::assertInstanceOf(Order::class, $result);
    }

    public function testCreateOrderMissingOrderOrCurrency(): void
    {
        $transaction = $this->createMock(AsyncPaymentTransactionStruct::class);
        $transaction->method('getOrder')
            ->willReturn(new OrderEntity());

        $this->expectException(PaymentException::class);
        $this->paymentService->createOrder($transaction);
    }

    public function testCheckOrderStatusSuccess(): void
    {
        $orderEntity = $this->createMock(OrderEntity::class);
        $twintApiResponse = [
            'id' => self::ORDER_ID,
        ];
        $orderEntity->method('getCustomFields')
            ->willReturn([
                OrderCustomFieldInstaller::TWINT_API_RESPONSE_CUSTOM_FIELD => json_encode($twintApiResponse),
            ]);
        $orderEntity->method('getSalesChannelId')
            ->willReturn(Uuid::randomHex());

        $orderTransactionEntity = new OrderTransactionEntity();
        $orderTransactionEntity->setId(Uuid::randomHex());
        $orderEntity->method('getTransactions')
            ->willReturn(new OrderTransactionCollection([$orderTransactionEntity]));


        $client = $this->createMock(InvocationRecordingClient::class);
        $this->clientBuilder->method('build')
            ->willReturn($client);


        $client->method('monitorOrder')
            ->willReturn($this->mockOrder);

        $this->orderService->method('isTwintOrder')
            ->willReturn(true);

        $result = $this->paymentService->checkOrderStatus($orderEntity);

        self::assertInstanceOf(Order::class, $result);
    }

    public function testReverseOrder(): void
    {
        $orderEntity = $this->createMock(OrderEntity::class);
        $twintOrder = new Order(
            OrderId::fromString(self::ORDER_ID),
            new FiledMerchantTransactionReference(Uuid::randomHex()),
            OrderStatus::SUCCESS(),
            TransactionStatus::ORDER_OK(),
            Money::CHF(10),
            PairingStatus::NO_PAIRING(),
            new NumericPairingToken(uint()->assert(self::PAIRING_TOKEN)),
            null
        );
        $orderEntity->method('getId')
            ->willReturn(Uuid::randomHex());
        $orderEntity->method('getSalesChannelId')
            ->willReturn(Uuid::randomHex());
        $currencyEntity = new CurrencyEntity();
        $currencyEntity->setIsoCode(Money::CHF);
        $orderEntity->method('getCurrency')
            ->willReturn($currencyEntity);
        $orderTransactionEntity = new OrderTransactionEntity();
        $orderTransactionEntity->setId(Uuid::randomHex());
        $orderTransactionEntity->setStateId(Uuid::randomHex());
        $orderEntity->method('getTransactions')
            ->willReturn(new OrderTransactionCollection([$orderTransactionEntity]));

        $this->orderService->method('getTwintOrder')
            ->willReturn($twintOrder);
        $client = $this->createMock(InvocationRecordingClient::class);
        $this->clientBuilder->method('build')
            ->willReturn($client);

        $client->method('monitorOrder')
            ->willReturn($twintOrder);
        $client->method('reverseOrder')
            ->willReturn($twintOrder);

        $result = $this->paymentService->reverseOrder($orderEntity);

        self::assertInstanceOf(Order::class, $result);
    }
}

<?php
declare(strict_types=1);

namespace Twint\Tests\Administration\Controller;

use Shopware\Core\Checkout\Cart\Price\CashRounding;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Test\TestDefaults;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twint\Administration\Controller\TwintController;
use Twint\Core\Handler\ReversalHistory\ReversalHistoryWriterInterface;
use Twint\Core\Service\OrderService;
use Twint\Core\Service\PaymentService;
use Twint\Core\Util\CertificateHandler;
use Twint\Core\Util\CredentialValidatorInterface;
use Twint\Core\Util\CryptoHandler;
use Twint\Sdk\Value\FiledMerchantTransactionReference;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\NumericPairingToken;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\OrderId;
use Twint\Sdk\Value\OrderStatus;
use Twint\Sdk\Value\PairingStatus;
use Twint\Sdk\Value\TransactionStatus;
use Twint\Tests\Helper\ServicesTrait;
use Shopware\Storefront\Test\Controller\StorefrontControllerTestBehaviour;
use function Psl\Type\non_empty_string;
use function Psl\Type\uint;

/**
 * @internal
 */
class TwintControllerTest extends TestCase
{
    use ServicesTrait;
    use StorefrontControllerTestBehaviour;
    use IntegrationTestBehaviour;

    private const ORDER_ID = '12345678-1234-1234-1234-123456789012';

    private const PAIRING_TOKEN = 1235;

    private Context $context;

    private array $twintCustomFields = [];

    private SalesChannelContext $salesChannelContext;

    private ContainerInterface $container;

    private TwintController $controller;

    private TranslatorInterface $translatorMock;

    private OrderService $orderServiceMock;

    private PaymentService $paymentServiceMock;

    private CredentialValidatorInterface $credentialValidatorMock;

    private CryptoHandler $cryptoHandlerMock;

    private CertificateHandler $certificateHandlerMock;

    private ReversalHistoryWriterInterface $reversalHistoryWriter;

    /**
     * @return string
     */
    static function getName()
    {
        return "TwintControllerTest";
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = $this->getContainer();

        $this->cryptoHandlerMock = $this->createMock(CryptoHandler::class);
        $this->cryptoHandlerMock->method('encrypt')->willReturn('encryptedContent');

        $this->reversalHistoryWriter = $this->createMock(ReversalHistoryWriterInterface::class);

        $this->controller = new TwintController();
        $this->controller->setContainer($this->container);
        $this->controller->setReversalHistoryWriter($this->reversalHistoryWriter);
        /** @var SalesChannelContextFactory $contextFactory */
        $contextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $this->salesChannelContext = $contextFactory->create('', TestDefaults::SALES_CHANNEL);
        $this->context = Context::createDefaultContext();
    }

    public function testExtractPemForEmptyFile(): void{
        $translatorMock = $this->createMock(TranslatorInterface::class);
        $translatorMock->method('trans')->willReturn('Please upload a valid certificate file');
        $this->controller->setTranslator($translatorMock);

        $request = $this->createRequest('api.action.twint.extract_pem', ['password' => '']);
        $response = $this->controller->extractPem($request, $this->context);
        static::assertSame(400, $response->getStatusCode());
        static::assertSame('{"success":false,"message":"Please upload a valid certificate file"}', (string)$response->getContent());
    }

    public function testExtractPemWithValidFile(): void{
        $translatorMock = $this->createMock(TranslatorInterface::class);
        $translatorMock->method('trans')->willReturn('Certificate validation successful');

        $this->controller->setTranslator($translatorMock);
        $this->controller->setEncryptor($this->cryptoHandlerMock);
        // Prepare a mock Request
        $file = new UploadedFile(dirname(__DIR__, 2).'/_fixture/test.p12', 'test.p12');
        $request = $this->createRequest('api.action.twint.extract_pem', ['password' => '']);
        $request->files->set('file', $file);
        // Call the method
        $response = $this->controller->extractPem($request, $this->context);
        // Assertions
        $this->assertInstanceOf(Response::class, $response);
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
    }

    public function testExtractPemForValidFileWrongPassword(): void{
        $translatorMock = $this->createMock(TranslatorInterface::class);
        $translatorMock->method('trans')->willReturn('Invalid certificate file');
        $this->controller->setTranslator($translatorMock);

        $file = new UploadedFile(dirname(__DIR__, 2).'/_fixture/certificate.p12', 'certificate.p12', null, null, true);

        // Prepare a mock Request
        $request = $this->createRequest('api.action.twint.extract_pem', ['password' => '1234']);
        $request->files->set('file', $file);
        // Call the method
        $response = $this->controller->extractPem($request, $this->context);
        static::assertSame(400, $response->getStatusCode());
        static::assertSame('{"success":false,"message":"Invalid certificate file","errorCode":"ERROR_INVALID_PASSPHRASE"}', (string)$response->getContent());
    }

    public function testRefundWithValidOrder(): void
    {
        $orderId = Uuid::randomHex();
        $order = new OrderEntity();
        $order->setId($orderId);
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
        $orderService = $this->createMock(OrderService::class);
        $orderService->method('getOrder')->willReturn($order);
        $orderService->method('getTwintOrder')->willReturn($twintOrder);

        $paymentService = $this->createMock(PaymentService::class);
        $paymentService->method('getTotalReversal')->willReturn(0.0);
        $paymentService->method('reverseOrder')->willReturn($twintOrder);


        $rounding = $this->createMock(CashRounding::class);
        $rounding->method('mathRound')->willReturn(20.00);

        $translatorMock = $this->createMock(TranslatorInterface::class);
        $translatorMock->method('trans')->willReturn('Certificate validation successful');

        $this->controller->setOrderService($orderService);
        $this->controller->setPaymentService($paymentService);
        $this->controller->setCashRounding($rounding);
        $this->controller->setTranslator($translatorMock);

        // Prepare a mock Request
        $request = $this->createRequest('api.action.twint.refund', ['orderId' => $orderId, 'reason' => 'test', 'amount' => 5]);
        // Call the method
        $response = $this->controller->refund($request, $this->context);
        // Assertions
        $this->assertInstanceOf(Response::class, $response);
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }
}
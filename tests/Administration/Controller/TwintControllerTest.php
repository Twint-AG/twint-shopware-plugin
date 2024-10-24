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

        $this->assertInstanceOf(Response::class, $response);
        $data = json_decode($response->getContent(), true);
        static::assertSame(400, $response->getStatusCode());
        $this->assertFalse($data['success']);
        static::assertSame('Please upload a valid certificate file', $data['message']);
    }

    public function testExtractPemWithValidFile(): void{

        $translatorMock = $this->createMock(TranslatorInterface::class);
        $translatorMock->method('trans')->willReturn('Certificate validation successful');

        $this->controller->setTranslator($translatorMock);
        $this->controller->setEncryptor($this->cryptoHandlerMock);
        // Prepare a mock Request
        $file = new UploadedFile(dirname(__DIR__, 2).'/_fixture/test.p12', 'test.p12');
        $request = $this->createRequest('api.action.twint.extract_pem', ['password' => '1234']);
        $request->files->set('file', $file);

        $response = $this->controller->extractPem($request, $this->context);

        $this->assertInstanceOf(Response::class, $response);
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('certificate', $data['data']);
        $this->assertArrayHasKey('passphrase', $data['data']);
    }

    public function testExtractPemForValidFileWrongPassword(): void{
        $translatorMock = $this->createMock(TranslatorInterface::class);
        $translatorMock->method('trans')->willReturn('Invalid certificate file');
        $this->controller->setTranslator($translatorMock);

        $file = new UploadedFile(dirname(__DIR__, 2).'/_fixture/test.p12', 'test.p12', null, null, true);
        $request = $this->createRequest('api.action.twint.extract_pem', ['password' => '12345']);
        $request->files->set('file', $file);


        $response = $this->controller->extractPem($request, $this->context);

        $this->assertInstanceOf(Response::class, $response);
        $data = json_decode($response->getContent(), true);
        static::assertSame(400, $response->getStatusCode());
        $this->assertFalse($data['success']);
        static::assertSame('Invalid certificate file', $data['message']);
        static::assertSame('ERROR_INVALID_PASSPHRASE', $data['errorCode']);
    }
}

<?php
declare(strict_types=1);

namespace Twint\Tests\Administration\Controller;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\Test\TestDefaults;
use Twint\Administration\Controller\TwintController;
use Twint\Tests\Helper\ServicesTrait;
use Shopware\Storefront\Test\Controller\StorefrontControllerTestBehaviour;

/**
 * @internal
 */
class TwintControllerTest extends TestCase
{
    use ServicesTrait;
    use StorefrontControllerTestBehaviour;
    use IntegrationTestBehaviour;

    private SalesChannelContext $salesChannelContext;

    private string $customerId;

    private Context $context;

    private array $twintCustomFields = [];

    private StateMachineRegistry $stateMachineRegistry;

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
        $this->twintController = $this->getContainer()->get(TwintController::class);
        /** @var SalesChannelContextFactory $contextFactory */
        $contextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $this->salesChannelContext = $contextFactory->create('', TestDefaults::SALES_CHANNEL);
        $this->customerId = $this->createCustomer('test@example.com');
        $this->context = Context::createDefaultContext();
        $this->stateMachineRegistry = $this->getContainer()->get(StateMachineRegistry::class);
    }
    public function testExtractPemForEmptyFile(): void{
        $request = $this->createRequest('api.action.twint.extract_pem', ['password' => '']);
        $response = $this->twintController->extractPem($request, $this->salesChannelContext->getContext());
        static::assertSame(400, $response->getStatusCode());
        static::assertSame('{"success":false,"message":"Please upload a valid certificate file "}', (string)$response->getContent());
    }

    public function testExtractPemForInvalidFile(): void{
        $file = new UploadedFile(dirname(__DIR__, 2).'/_fixture/test.p12', 'test.p12', null, null, true);
        $request = $this->createRequest('api.action.twint.extract_pem', ['password' => '']);
        $request->files->set('file', $file);
        $response = $this->twintController->extractPem($request, $this->salesChannelContext->getContext());
        static::assertSame(400, $response->getStatusCode());
        static::assertSame('{"success":false,"message":"Invalid certificate file ","errorCode":"ERROR_INVALID_INPUT"}', (string)$response->getContent());
    }
    public function testExtractPemForValidFileWrongPassword(): void{
        $file = new UploadedFile(dirname(__DIR__, 2).'/_fixture/certificate.p12', 'certificate.p12', null, null, true);
        $request = $this->createRequest('api.action.twint.extract_pem', ['password' => '1234']);
        $request->files->set('file', $file);
        $response = $this->twintController->extractPem($request, $this->salesChannelContext->getContext());
        static::assertSame(400, $response->getStatusCode());
        static::assertSame('{"success":false,"message":"Invalid certificate file ","errorCode":"ERROR_INVALID_PASSPHRASE"}', (string)$response->getContent());
    }
    public function testExtractPemForValidFileCorrectPassword(): void{
        $file = new UploadedFile(dirname(__DIR__, 2).'/_fixture/certificate.p12', 'certificate.p12', null, null, true);
        $request = $this->createRequest('api.action.twint.extract_pem', ['password' => '12345']);
        $request->files->set('file', $file);
        $response = $this->twintController->extractPem($request, $this->salesChannelContext->getContext());
        static::assertSame(400, $response->getStatusCode());
        static::assertSame('{"success":false,"message":"Invalid certificate file ","errorCode":"ERROR_INVALID_CERTIFICATE_FORMAT"}', (string)$response->getContent());
    }
}
<?php
declare(strict_types=1);

namespace Twint\Tests\Storefront\Controller;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\TestDefaults;
use Twint\Storefront\Controller\CheckoutController;
use Twint\Tests\Helper\ServicesTrait;
use Twint\Storefront\Controller\PaymentController;
use Twint\Core\Util\CryptoHandler;
use Shopware\Storefront\Test\Controller\StorefrontControllerTestBehaviour;

/**
 * @internal
 */
class CheckoutControllerTest extends TestCase
{
    use ServicesTrait;
    use StorefrontControllerTestBehaviour;
    use IntegrationTestBehaviour;

    /**
     * @var PaymentController
     */
    private $paymentController;
    /**
     * @var CryptoHandler
     */
    private $crytoService;

    private SalesChannelContext $salesChannelContext;

    private Context $context;

    /**
     * @return string
     */
    static function getName()
    {
        return "CheckoutControllerTest";
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->checkoutController = $this->getContainer()->get(CheckoutController::class);
        $this->crytoService = $this->getContainer()->get(CryptoHandler::class);
        /** @var SalesChannelContextFactory $contextFactory */
        $contextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $this->salesChannelContext = $contextFactory->create('', TestDefaults::SALES_CHANNEL);
        $this->context = Context::createDefaultContext();
    }
    public function testInvalidMonitor(): void{
        $request = $this->createRequest('frontend.twint.monitoring', ['paringHash' => $this->crytoService->hash(Uuid::randomHex())]);
        $this->getContainer()->get('request_stack')->push($request);
        $response = $this->checkoutController->monitor($request, $this->salesChannelContext);
        static::assertSame(302, $response->getStatusCode());
        static::assertStringContainsStringIgnoringCase('/account/order', (string)$response->getTargetUrl());
    }
    public function testInvalidExpress(): void{
        $request = $this->createRequest('frontend.twint.express', ['paringHash' => $this->crytoService->hash(Uuid::randomHex())]);
        $this->getContainer()->get('request_stack')->push($request);
        $response = $this->checkoutController->express($request, $this->salesChannelContext);
        static::assertSame(302, $response->getStatusCode());
        static::assertStringContainsStringIgnoringCase('/account/order', (string)$response->getTargetUrl());
    }
}
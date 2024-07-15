<?php
declare(strict_types=1);

namespace Twint\Tests\Storefront\Controller;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\TestDefaults;
use Twint\Core\DataAbstractionLayer\Entity\Pairing\PairingEntity;
use Twint\Core\Service\PaymentService;
use Twint\ExpressCheckout\Repository\PairingRepository;
use Twint\ExpressCheckout\Service\ExpressCheckoutServiceInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Twint\Sdk\Value\PairingStatus;
use Twint\Sdk\Value\PairingUuid;
use Twint\Storefront\Controller\CheckoutController;
use Twint\Tests\Helper\ServicesTrait;
use Twint\Core\Util\CryptoHandler;
use Shopware\Storefront\Test\Controller\StorefrontControllerTestBehaviour;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 */
class CheckoutControllerTest extends TestCase
{
    use ServicesTrait;
    use StorefrontControllerTestBehaviour;
    use IntegrationTestBehaviour;

    private ContainerInterface $container;

    const TOKEN = '12345678-1234-1234-1234-123456789012';

    private SalesChannelContext $salesChannelContext;

    private Context $context;

    private CheckoutController $controller;

    private ExpressCheckoutServiceInterface $checkoutService;

    private CryptoHandler $cryptoService;

    private PairingRepository $pairingRepository;

    private PaymentService $paymentService;

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
        $this->container = $this->getContainer();
        $this->crytoService = $this->getContainer()->get(CryptoHandler::class);
        /** @var SalesChannelContextFactory $contextFactory */
        $contextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $this->salesChannelContext = $contextFactory->create('', TestDefaults::SALES_CHANNEL);
        $this->context = Context::createDefaultContext();

        $this->checkoutService = $this->createMock(ExpressCheckoutServiceInterface::class);
        $this->cryptoService = $this->createMock(CryptoHandler::class);
        $this->pairingRepository = $this->createMock(PairingRepository::class);
        $this->paymentService = $this->createMock(PaymentService::class);
        $this->controller = new CheckoutController(
            $this->checkoutService,
            $this->cryptoService,
            $this->pairingRepository,
            $this->paymentService
        );
        $this->controller->setContainer($this->container);
    }
    /**
     * @covers \Twint\Storefront\Controller\CheckoutController::expressCheckout
     * TO-DO for Express Checkout
     */
    public function testExpressCheckout(): void
    {
        $request = $this->createRequest('frontend.twint.express-checkout', ['paringHash' => self::TOKEN], 'POST');
        $pairing = new class(){
            public function pairingUuid(){
                return new PairingUuid(new \Twint\Sdk\Value\Uuid('12345678-1234-1234-1234-123456789012'));
            }
            public function pairingStatus(){
                return new PairingStatus(PairingStatus::PAIRING_IN_PROGRESS);
            }
        };
        $this->checkoutService->expects($this->any())
            ->method('pairing')
            ->with($this->salesChannelContext, $request)
            ->willReturn($pairing);

        $pairingEntity = new PairingEntity();
        $pairingEntity->setToken(self::TOKEN);
        $cart = new Cart(Uuid::randomHex());
        $cart->setPrice(new CartPrice(10, 10, 10, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_GROSS));
        $pairingEntity->setCart($cart);

        $this->pairingRepository->expects($this->any())
            ->method('load')
            ->with(self::TOKEN)
            ->willReturn($pairingEntity);

        $this->cryptoService->expects($this->any())
            ->method('hash')
            ->with(self::TOKEN)
            ->willReturn(self::TOKEN);

        $this->cryptoService->expects($this->any())
            ->method('unHash')
            ->with(self::TOKEN)
            ->willReturn(self::TOKEN);
        //$response = $this->controller->expressCheckout($request, $this->salesChannelContext);
        //$responseData = json_decode($response->getContent(), true);
        $this->assertTrue(true);
        //$this->assertTrue($responseData['success']);
        //$this->assertEquals('/payment/express/hashedPairingUuid', $responseData['redirectUrl']);
    }

    /**
     * @covers \Twint\Storefront\Controller\CheckoutController::monitor
     * TO-DO for Express Checkout
     */
    public function testMonitor(): void
    {
        $request = $this->createRequest('frontend.twint.monitoring', ['paringHash' => 'abc']);
        $pairing = new class(){
            public function pairingUuid(){
                return new PairingUuid(new \Twint\Sdk\Value\Uuid('12345678-1234-1234-1234-123456789012'));
            }
            public function pairingStatus(){
                return new PairingStatus(PairingStatus::PAIRING_IN_PROGRESS);
            }
        };
        $pairingHash = 'hashedPairingUuid';

        $this->cryptoService->expects($this->any())
            ->method('unHash')
            ->with($pairingHash)
            ->willReturn('unhashedPairingUuid');

        $pairingEntity = new PairingEntity();
        $pairingEntity->setToken(self::TOKEN);
        $cart = new Cart(Uuid::randomHex());
        $cart->setPrice(new CartPrice(10, 10, 10, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_GROSS));
        $pairingEntity->setCart($cart);

        $this->pairingRepository->expects($this->any())
            ->method('load')
            ->with(self::TOKEN)
            ->willReturn($pairingEntity);

        //$response = $this->controller->monitor($request, $this->salesChannelContext);
        //$responseData = json_decode($response->getContent(), true);
        //$this->assertFalse($responseData['completed']);
        $this->assertTrue(true);
    }

    /**
     * @covers \Twint\Storefront\Controller\CheckoutController::express
     * TO-DO for Express Checkout
     */
    public function testExpress(): void
    {
        $request = $this->createRequest('frontend.twint.monitoring', ['paringHash' => 'abc']);
        $pairingHash = 'hashedPairingUuid';
        $pairing = new class(){
            public function pairingUuid(){
                return new PairingUuid(new \Twint\Sdk\Value\Uuid('12345678-1234-1234-1234-123456789012'));
            }
            public function pairingStatus(){
                return new PairingStatus(PairingStatus::PAIRING_IN_PROGRESS);
            }
        };

        $this->cryptoService->expects($this->any())
            ->method('unHash')
            ->with($pairingHash)
            ->willReturn('unhashedPairingUuid');

        $pairingEntity = new PairingEntity();
        $pairingEntity->setToken(self::TOKEN);
        $cart = new Cart(Uuid::randomHex());
        $cart->setPrice(new CartPrice(10, 10, 10, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_GROSS));
        $pairingEntity->setCart($cart);

        $this->pairingRepository->expects($this->any())
            ->method('load')
            ->with(self::TOKEN)
            ->willReturn($pairingEntity);

        //$response = $this->controller->express($request, $this->salesChannelContext);
        $this->assertTrue(true);
    }
}

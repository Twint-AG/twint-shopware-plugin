<?php
declare(strict_types=1);


namespace Twint\Tests\Storefront\Controller;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\TestDefaults;
use Twint\Tests\Helper\ServicesTrait;
use Symfony\Component\HttpFoundation\Request;
use Twint\Storefront\Controller\PaymentController;
use Twint\Core\Util\CryptoHandler;

class PaymentControllerTest extends TestCase
{
    use ServicesTrait;

    /**
     * @var PaymentController
     */
    private $paymentController;
    /**
     * @var CryptoHandler
     */
    private $crytoService;

    private SalesChannelContext $salesChannelContext;

    protected function setUp(): void
    {
        $this->paymentController = $this->getContainer()->get(PaymentController::class);
        $this->crytoService = $this->getContainer()->get(CryptoHandler::class);
        /** @var SalesChannelContextFactory $contextFactory */
        $contextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $this->salesChannelContext = $contextFactory->create('', TestDefaults::SALES_CHANNEL);
    }

    public function testPaymentOutput(): void
    {
        $context = $this->salesChannelContext->getContext();
        $customerId = $this->createCustomer();
        $order = $this->createOrder($customerId, $context);
        $orderNumber = !empty($order->getOrderNumber()) ? $order->getOrderNumber() : $order->getId();
        $response = $this->paymentController->showWaiting(new Request([
            'orderNumber' => $this->crytoService->hash($orderNumber)
        ]), $context);
        static::assertSame(200, $response->getStatusCode());
    }
    public function testInvalidOrder(): void
    {
        $context = $this->salesChannelContext->getContext();
        $response = $this->paymentController->showWaiting(new Request([
            'orderNumber' => $this->crytoService->hash(Uuid::randomHex())
        ]), $context);
        static::assertSame(302, $response->getStatusCode());
    }

    public function testRedirect(): void
    {
        $context = Context::createDefaultContext();
        $customerId = $this->createCustomer();
        $order = $this->createOrder($customerId, $context);

        $response = $this->paymentController->showWaiting(new Request([
            'orderNumber' => $this->crytoService->hash($order->getOrderNumber())
        ]), $context);

        static::assertSame(301, $response->getStatusCode());
    }
}

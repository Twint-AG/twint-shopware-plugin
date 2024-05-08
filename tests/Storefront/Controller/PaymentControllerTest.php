<?php
declare(strict_types=1);

namespace Twint\Tests\Storefront\Controller;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\TestDefaults;
use Twint\Tests\Helper\ServicesTrait;
use Twint\Storefront\Controller\PaymentController;
use Twint\Core\Util\CryptoHandler;
use Shopware\Storefront\Test\Controller\StorefrontControllerTestBehaviour;

/**
 * @internal
 */
class PaymentControllerTest extends TestCase
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

    }
    public function testValidOrder(): void{
        $email = 'test@example.com';
        $context = Context::createDefaultContext();
        $customerId = $this->createCustomer('test@example.com');
        $customFields = [
            'twint_api_response' => '{"id":"40684cd7-66a0-4118-92e0-5b06b5459f59","status":"IN_PROGRESS","transactionStatus":"ORDER_RECEIVED","pairingToken":"74562","merchantTransactionReference":"10095"}'
        ];
        $order = $this->createOrder($customerId, $context, $customFields);
        $order->setCustomFields([]);
        $browser = $this->login($email);

        $browser->request(
            'GET',
            '/payment/waiting/' . $this->crytoService->hash($order->getOrderNumber()),
            []
        );
        //check QR code exist or not
        static::assertStringContainsStringIgnoringCase('QR-Code', (string)$browser->getResponse()->getContent());
    }
    public function testInvalidOrder(): void
    {
        $email = 'test@example.com';
        $context = Context::createDefaultContext();
        $customerId = $this->createCustomer('test@example.com');

        $browser = $this->login($email);

        $browser->request(
            'GET',
            '/payment/waiting/' . $this->crytoService->hash(Uuid::randomHex()),
            []
        );
        $response = $browser->getResponse();
        static::assertSame(302, $response->getStatusCode());
    }

    /**
     * @throws \JsonException
     */
    public function testOrderWithoutTwintResponse(): void
    {
        $email = 'test@example.com';
        $context = Context::createDefaultContext();
        $customerId = $this->createCustomer('test@example.com');
        $customFields = [];
        $order = $this->createOrder($customerId, $context, $customFields);
        //remove twint response;
        $order->setCustomFields([]);
        $browser = $this->login($email);

        $browser->request(
            'GET',
            '/payment/waiting/' . $this->crytoService->hash($order->getOrderNumber()),
            []
        );
        //check QR code exist or not
        static::assertStringNotContainsStringIgnoringCase('QR-Code', (string)$browser->getResponse()->getContent());
    }
}

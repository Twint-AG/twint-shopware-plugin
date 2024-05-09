<?php declare(strict_types=1);

namespace Twint\Tests\Core\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Twint\Core\Service\PaymentService;
use Twint\Tests\Helper\ServicesTrait;

class PaymentServiceTest extends TestCase
{
    use ServicesTrait;
    use IntegrationTestBehaviour;

    private PaymentService $paymentService;
    private Context $context;
    private OrderEntity $order;

    /**
     * @return string
     */
    static function getName()
    {
        return "PaymentServiceTest";
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = $this->getContainer()->get(PaymentService::class);
        $customerId = $this->createCustomer('test@example.com');
        $this->context = Context::createDefaultContext();
        $this->order = $this->createOrder($customerId, $this->context, []);
    }

    public function testEmptyGetPendingOrders(): void
    {
        static::assertCount(0, $this->paymentService->getPendingOrders());
    }
    public function testOneProgressGetPendingOrders(): void
    {
        $order = $this->transitionOrder($this->order, StateMachineTransitionActions::ACTION_DO_PAY, $this->context);
        static::assertCount(0, $this->paymentService->getPendingOrders());
    }
    public function testIsOrderPaid(): void
    {
        $order = $this->transitionOrder($this->order, StateMachineTransitionActions::ACTION_PAID, $this->context);
        static::assertTrue($this->paymentService->isOrderPaid($order));
    }
    public function testIsNotOrderPaid(): void
    {
        $order = $this->transitionOrder($this->order, StateMachineTransitionActions::ACTION_DO_PAY, $this->context);
        static::assertFalse($this->paymentService->isOrderPaid($order));
    }
    public function testIsCancelPaid(): void
    {
        $order = $this->transitionOrder($this->order, StateMachineTransitionActions::ACTION_CANCEL, $this->context);
        static::assertTrue($this->paymentService->isCancelPaid($order));
    }
    public function testIsNotCancelPaid(): void
    {
        $order = $this->transitionOrder($this->order, StateMachineTransitionActions::ACTION_DO_PAY, $this->context);
        static::assertFalse($this->paymentService->isCancelPaid($order));
    }
}
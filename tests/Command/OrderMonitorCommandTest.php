<?php declare(strict_types=1);

namespace Twint\Tests\Command;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Symfony\Component\Console\Tester\CommandTester;
use Twint\Command\OrderMonitorCommand;
use Twint\Core\Service\OrderService;
use Twint\Core\Service\PaymentService;
use Twint\Tests\Helper\ServicesTrait;

/**
 * @internal
 */
class OrderMonitorCommandTest extends TestCase
{
    use ServicesTrait;
    use IntegrationTestBehaviour;
    /**
     * @return string
     */
    static function getName()
    {
        return "OrderMonitorCommandTest";
    }
    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new OrderMonitorCommand(
            $this->getContainer()->get(PaymentService::class),
            $this->getContainer()->get(OrderService::class),
        );
    }
    public function testServiceIsDecoratedCorrectly(): void
    {
        static::assertInstanceOf(OrderMonitorCommand::class, $this->command);
    }
    public function testExecuteCommandWithNoOrders(): void
    {
        $runner = new CommandTester($this->command);
        static::assertSame(0, $runner->execute([]));
    }
    public function testExecuteCommandWithOrders(): void
    {
        $customerId = $this->createCustomer('test@example.com');
        $this->context = Context::createDefaultContext();
        $this->order = $this->createOrder($customerId, $this->context, []);
        $runner = new CommandTester($this->command);
        static::assertSame(0, $runner->execute([]));
    }
}
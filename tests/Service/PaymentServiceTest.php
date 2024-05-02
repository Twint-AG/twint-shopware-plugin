<?php declare(strict_types=1);

namespace Twint\Tests\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

class PaymentServiceTest extends TestCase
{
    use IntegrationTestBehaviour;
    public function testRunning(): void
    {
        static::assertFalse(false);
    }
}
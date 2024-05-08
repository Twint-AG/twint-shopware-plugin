<?php declare(strict_types=1);

namespace Twint\Tests\Core\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

class PaymentServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @return string
     */
    static function getName()
    {
        return "PaymentServiceTest";
    }

    public function testRunning(): void
    {
        static::assertFalse(false);
    }
}
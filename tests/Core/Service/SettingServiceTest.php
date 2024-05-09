<?php declare(strict_types=1);

namespace Twint\Tests\Core\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\TestDefaults;
use Twint\Core\Model\TwintSettingStruct;
use Twint\Core\Service\SettingService;
use Twint\Tests\Helper\ServicesTrait;

class SettingServiceTest extends TestCase
{
    use ServicesTrait;
    use IntegrationTestBehaviour;

    private SalesChannelContext $salesChannelContext;

    private SettingService $settingService;

    /**
     * @return string
     */
    static function getName()
    {
        return "SettingServiceTest";
    }

    protected function setUp(): void
    {
        parent::setUp();
        /** @var SalesChannelContextFactory $contextFactory */
        $contextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $this->salesChannelContext = $contextFactory->create('', TestDefaults::SALES_CHANNEL);
        $this->settingService = $this->getContainer()->get(SettingService::class);
    }
    public function testGetSetting(): void
    {
        static::assertContainsOnlyInstancesOf(TwintSettingStruct::class, [$this->settingService->getSetting($this->salesChannelContext->getSalesChannel()->getId())]);
    }
}
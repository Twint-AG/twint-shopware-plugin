<?php declare(strict_types=1);

namespace Twint\Tests;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\PluginCollection;
use Shopware\Core\Framework\Plugin\PluginDefinition;
use Shopware\Core\Framework\Plugin\PluginEntity;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Twint\TwintPayment;

class TwintPaymentTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @var EntityRepository<PluginCollection>
     */
    private EntityRepository $pluginRepository;

    protected function setUp(): void
    {
        /** @var EntityRepository<PluginCollection> $pluginRepository */
        $pluginRepository = $this->getContainer()->get(\sprintf('%s.repository', PluginDefinition::ENTITY_NAME));
        $this->pluginRepository = $pluginRepository;
    }

    public function testThatPluginIsInstalled(): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('baseClass', TwintPayment::class)
        );

        /** @var PluginEntity|null $plugin */
        $plugin = $this->pluginRepository->search($criteria, Context::createDefaultContext())->first();

        static::assertNotNull($plugin, 'Plugin needs to be installed to run testsuite');
    }
}

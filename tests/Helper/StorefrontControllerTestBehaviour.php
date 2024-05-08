<?php declare(strict_types=1);

namespace Twint\Tests\Helper;

use Doctrine\DBAL\Connection;
use Shopware\Core\DevOps\Environment\EnvironmentHelper;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

trait StorefrontControllerTestBehaviour
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $files
     * @param array<string, mixed> $server
     */
    public function request(string $method, string $path, array $data, array $files = [], array $server = [], ?string $content = null, bool $changeHistory = true): Response
    {
        $browser = KernelLifecycleManager::createBrowser($this->getKernel());
        $browser->request($method, EnvironmentHelper::getVariable('APP_URL') . '/' . $path, $data, $files, $server, $content, $changeHistory);

        return $browser->getResponse();
    }

    public function getSalesChannelId(): string
    {
        return (string) $this->getContainer()
            ->get(Connection::class)
            ->fetchOne(
                'SELECT LOWER(HEX(sales_channel_id)) FROM sales_channel_domain WHERE url = :url',
                ['url' => EnvironmentHelper::getVariable('APP_URL')]
            );
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function tokenize(string $route, array $data): array
    {
        $requestStack = new RequestStack();
        $request = new Request();
        /** @var Session $session */
        $session = $this->getSession();
        $request->setSession($session);
        $requestStack->push($request);

        return $data;
    }



    /**
     * @param string $email
     * @return KernelBrowser
     */
    private function login(string $email): KernelBrowser
    {
        $browser = KernelLifecycleManager::createBrowser($this->getKernel());
        $browser->request(
            'POST',
            $_SERVER['APP_URL'] . '/account/login',
            $this->tokenize('frontend.account.login', [
                'username' => $email,
                'password' => 'shopware',
            ])
        );
        $response = $browser->getResponse();
        static::assertSame(200, $response->getStatusCode(), $response->getContent());

        return $browser;
    }
}

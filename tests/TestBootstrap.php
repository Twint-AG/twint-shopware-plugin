<?php declare(strict_types=1);

use Shopware\Core\TestBootstrapper;

$paths = [
    $_SERVER['PROJECT_ROOT'] ?? dirname(__DIR__, 4), //gitlab ci
    $_SERVER['PROJECT_ROOT'] ?? dirname(__DIR__, 2) . '/html' //local
];

$projectRoot = '';
foreach ($paths as $path) {
    if (file_exists($path . '/vendor/autoload.php')) {
        $projectRoot = $path;
        break;
    }
}

$moduleAutoloader = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($moduleAutoloader)) {
    throw new \RuntimeException('Please run `composer dump-autoload` for the directory ' . dirname(__DIR__));
}

require_once $moduleAutoloader;

$shopwareBootstrapLookup = [
    $projectRoot . '/vendor/shopware/core/TestBootstrapper.php', // shopware/production
    $projectRoot . '/src/Core/TestBootstrapper.php',             // shopware/platform
];

foreach ($shopwareBootstrapLookup as $item) {
    if (is_readable($item)) {
        require_once $item;

        break;
    }
}

if (!class_exists(TestBootstrapper::class)) {
    throw new \RuntimeException("Shopware bootstrapper was not found. Tried locations: \n" . implode("\n", $shopwareBootstrapLookup));
}

$loader = (new TestBootstrapper())
    ->setProjectDir($projectRoot)
    ->setLoadEnvFile(true)
    ->addCallingPlugin()
    ->addActivePlugins('TwintPayment')
    ->setForceInstallPlugins(true)
    ->bootstrap()
    ->getClassLoader();
$loader->addPsr4('Twint\\Tests\\', __DIR__);

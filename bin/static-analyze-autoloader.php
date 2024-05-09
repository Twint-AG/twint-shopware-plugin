<?php declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

$paths = [
    $_SERVER['PROJECT_ROOT'] ?? dirname(__DIR__, 4), //gitlab ci
    $_SERVER['PROJECT_ROOT'] ?? dirname(__DIR__, 2) . '/html' //local
];

$projectRoot = '';
foreach ($paths as $path) {
    if (file_exists($path . '/vendor/autoload.php')) {
        $projectRoot = $path;
        require_once $path . '/vendor/autoload.php';
        break;
    }
}

if (file_exists($projectRoot . '/.env')) {
    (new Dotenv())->usePutEnv()->load($projectRoot . '/.env');
}

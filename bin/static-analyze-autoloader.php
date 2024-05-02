<?php declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

$projectRoot = $_SERVER['PROJECT_ROOT'] ?? dirname(__DIR__, 2);
$projectRoot .= '/html';

require_once $projectRoot . '/vendor/autoload.php';

if (file_exists($projectRoot . '/.env')) {
    (new Dotenv())->usePutEnv()->load($projectRoot . '/.env');
}
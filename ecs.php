<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Basic\PsrAutoloadingFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->import(__DIR__ . '/ecs.base.php');
    $ecsConfig->paths([__DIR__.'/src']);

    $ecsConfig->ruleWithConfiguration(PsrAutoloadingFixer::class, [
        'dir' => 'src',
    ]);

    $ecsConfig->cacheDirectory(__DIR__ . '/build/ecs/src');
};

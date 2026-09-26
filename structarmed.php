<?php

declare(strict_types=1);

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Preset\Preset;

return Architecture::define()
    ->withPresets(Preset::PSR4(), Preset::CODEQUALITY())
    ->layer('Exception', 'src/Exception')
    ->layer('Response', 'src/Response')
    ->layer('ResponseFactory', [
        'src/ProblemDetailsResponseFactory.php',
        'src/Psr17ResponseFactoryTrait.php',
    ])
    ->layer('Middleware', [
        'src/ProblemDetailsMiddleware.php',
        'src/ProblemDetailsNotFoundHandler.php',
    ])
    ->layer('Factory', [
        'src/ProblemDetailsMiddlewareFactory.php',
        'src/ProblemDetailsNotFoundHandlerFactory.php',
        'src/ProblemDetailsResponseFactoryFactory.php',
    ])
    ->layer('ConfigProvider', 'src/ConfigProvider.php')
    ->ruleset([
        'Exception'       => [],
        'Response'        => [],
        'ResponseFactory' => ['Exception', 'Response'],
        'Middleware'      => ['ResponseFactory'],
        'Factory'         => ['+Middleware', '+ResponseFactory'],
        'ConfigProvider'  => ['+Factory'],
    ]);

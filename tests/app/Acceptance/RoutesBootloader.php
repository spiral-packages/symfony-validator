<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\App\Acceptance;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Router\Route;
use Spiral\Router\RouterInterface;
use Spiral\Router\Target\Action;

final class RoutesBootloader extends Bootloader
{
    private const ROUTES = [
        '/simple' => 'simple',
        '/array-mapping' => 'arrayMapping',
        '/post' => 'post',
        '/profile' => 'profile',
        '/attachments' => 'attachments',
    ];

    public function boot(RouterInterface $router): void
    {
        foreach (self::ROUTES as $path => $action) {
            $router->setRoute($action, new Route($path, new Action(FormController::class, $action)));
        }
    }
}

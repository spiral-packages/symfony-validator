<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\App\Acceptance;

use Spiral\Bootloader\Attributes\AttributesBootloader;
use Spiral\Bootloader\Http\HttpBootloader;
use Spiral\Bootloader\Http\RouterBootloader;
use Spiral\Bootloader\Security\FiltersBootloader;
use Spiral\Core\Container;
use Spiral\Framework\Kernel;
use Spiral\Nyholm\Bootloader\NyholmBootloader;
use Spiral\Validation\Bootloader\ValidationBootloader;
use Spiral\Validation\Symfony\Bootloader\ValidatorBootloader;

/**
 * The application behind the acceptance front controller: the package is wired the way a user application wires it.
 */
final class HttpKernel extends Kernel
{
    protected const LOAD = [
        AttributesBootloader::class,
        NyholmBootloader::class,
        HttpBootloader::class,
        RouterBootloader::class,
        FiltersBootloader::class,
        ValidationBootloader::class,
        ValidatorBootloader::class,
        RoutesBootloader::class,
    ];

    public function getContainer(): Container
    {
        return $this->container;
    }
}

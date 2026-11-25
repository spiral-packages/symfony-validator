<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\App;

use Spiral\Bootloader\Attributes\AttributesBootloader;
use Spiral\Bootloader\Security\FiltersBootloader;
use Spiral\Core\Container;
use Spiral\Framework\Kernel;
use Spiral\Nyholm\Bootloader\NyholmBootloader;
use Spiral\Validation\Bootloader\ValidationBootloader;
use Spiral\Validation\Symfony\Bootloader\ValidatorBootloader;

final class TestKernel extends Kernel
{
    protected const LOAD = [
        AttributesBootloader::class,
        NyholmBootloader::class,
        FiltersBootloader::class,
        ValidationBootloader::class,
        ValidatorBootloader::class,
    ];

    public function getContainer(): Container
    {
        return $this->container;
    }
}

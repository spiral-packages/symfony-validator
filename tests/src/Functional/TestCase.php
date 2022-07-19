<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\Functional;

use Spiral\Bootloader\Attributes\AttributesBootloader;
use Spiral\Bootloader\Security\FiltersBootloader;
use Spiral\Nyholm\Bootloader\NyholmBootloader;
use Spiral\Validation\Bootloader\ValidationBootloader;
use Spiral\Validation\Symfony\Bootloader\ValidatorBootloader;

abstract class TestCase extends \Spiral\Testing\TestCase
{
    public function rootDirectory(): string
    {
        return __DIR__ . '/../';
    }

    public function defineBootloaders(): array
    {
        return [
            AttributesBootloader::class,
            NyholmBootloader::class,
            FiltersBootloader::class,
            ValidationBootloader::class,
            ValidatorBootloader::class,
        ];
    }
}

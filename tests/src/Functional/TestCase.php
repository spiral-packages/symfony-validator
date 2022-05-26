<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\Functional;

use Spiral\Attributes\Bootloader\AttributesBootloader;
use Spiral\Bootloader\Security\FiltersBootloader;
use Spiral\Http\Bootloader\DiactorosBootloader;
use Spiral\Validation\Bootloader\ValidationBootloader;
use Spiral\Validation\Symfony\Bootloader\ValidatorBootloader;

abstract class TestCase extends \Spiral\Testing\TestCase
{
    public function rootDirectory(): string
    {
        return \dirname(__DIR__ . '/../../app');
    }

    public function defineBootloaders(): array
    {
        return [
            AttributesBootloader::class,
            DiactorosBootloader::class,
            FiltersBootloader::class,
            ValidationBootloader::class,
            ValidatorBootloader::class,
        ];
    }
}

<?php

namespace Spiral\Validation\Symfony\Tests;

class TestCase extends \Spiral\Testing\TestCase
{
    public function rootDirectory(): string
    {
        return __DIR__.'/../';
    }

    public function defineBootloaders(): array
    {
        return [
            \Spiral\Boot\Bootloader\ConfigurationBootloader::class,
            \Spiral\Validation\Symfony\Bootloader\ValidationBootloader::class,
            // ...
        ];
    }
}

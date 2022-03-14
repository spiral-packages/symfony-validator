<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Core\Container;
use Spiral\Config\ConfiguratorInterface;
use Spiral\Validation\Symfony\Validator\Config\ValidatorConfig;

class ValidationBootloader extends Bootloader
{
    protected const BINDINGS = [];
    protected const SINGLETONS = [];

    public function __construct(private ConfiguratorInterface $config)
    {
    }

    public function boot(Container $container): void
    {
        $this->initConfig();
    }

    public function start(Container $container): void
    {
    }

    private function initConfig(): void
    {
        $this->config->setDefaults(
            ValidatorConfig::CONFIG,
            []
        );
    }
}

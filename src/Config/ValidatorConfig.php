<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Validator\Config;

use Spiral\Core\InjectableConfig;

final class ValidatorConfig extends InjectableConfig
{
    public const CONFIG = 'validator';
    protected $config = [];
}

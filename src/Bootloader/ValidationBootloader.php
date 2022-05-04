<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Validation\Symfony\FilterDefinition;
use Spiral\Validation\Symfony\Validation;
use Spiral\Validation\ValidationInterface;
use Spiral\Validation\ValidationProvider;

class ValidationBootloader extends Bootloader
{
    protected const BINDINGS = [];
    protected const SINGLETONS = [
        Validation::class => [self::class, 'initValidation'],
    ];

    public function boot(ValidationProvider $provider): void
    {
        $provider->register(
            FilterDefinition::class,
            static fn(Validation $validation): ValidationInterface => $validation
        );
    }

    private function initValidation(): ValidationInterface
    {
        return new Validation(
            \Symfony\Component\Validator\Validation::createValidatorBuilder()
                ->enableAnnotationMapping()
                ->getValidator()
        );
    }
}

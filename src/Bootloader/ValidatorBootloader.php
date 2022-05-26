<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Bootloader\Http\HttpBootloader;
use Spiral\Validation\Bootloader\ValidationBootloader;
use Spiral\Validation\Symfony\FilterDefinition;
use Spiral\Validation\Symfony\Http\Request\FilesBag;
use Spiral\Validation\Symfony\Validation;
use Spiral\Validation\ValidationInterface;
use Spiral\Validation\ValidationProvider;

class ValidatorBootloader extends Bootloader
{
    protected const DEPENDENCIES = [
        ValidationBootloader::class
    ];

    protected const SINGLETONS = [
        Validation::class => [self::class, 'initValidation'],
    ];

    public function init(HttpBootloader $http): void
    {
        $http->addInputBag('symfonyFiles', [
            'class'  => FilesBag::class,
            'source' => 'getUploadedFiles',
            'alias' => 'symfony-file'
        ]);
    }

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

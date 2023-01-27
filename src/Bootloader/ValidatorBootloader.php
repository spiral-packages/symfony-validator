<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Bootloader;

use Psr\Container\ContainerInterface;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Bootloader\Http\HttpBootloader;
use Spiral\Validation\Bootloader\ValidationBootloader;
use Spiral\Validation\Symfony\FilterDefinition;
use Spiral\Validation\Symfony\Http\Request\FilesBag;
use Spiral\Validation\Symfony\Validation;
use Spiral\Validation\ValidationInterface;
use Spiral\Validation\ValidationProvider;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\EmailValidator;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;

class ValidatorBootloader extends Bootloader
{
    protected const DEPENDENCIES = [
        ValidationBootloader::class
    ];

    protected const SINGLETONS = [
        Validation::class => [self::class, 'initValidation'],
        ConstraintValidatorFactoryInterface::class => [self::class, 'initConstraintValidatorFactory'],
    ];

    public function init(HttpBootloader $http): void
    {
        $http->addInputBag('symfonyFiles', [
            'class'  => FilesBag::class,
            'source' => 'getUploadedFiles',
            'alias' => 'symfony-file'
        ]);
    }

    public function boot(ValidationProvider $provider, ValidationBootloader $validation): void
    {
        $provider->register(
            FilterDefinition::class,
            static fn(Validation $validation): ValidationInterface => $validation
        );
        $validation->setDefaultValidator(FilterDefinition::class);
    }

    private function initValidation(ConstraintValidatorFactoryInterface $validatorFactory): ValidationInterface
    {
        return new Validation(
            \Symfony\Component\Validator\Validation::createValidatorBuilder()
                ->enableAnnotationMapping()
                ->setConstraintValidatorFactory($validatorFactory)
                ->getValidator()
        );
    }

    private function initConstraintValidatorFactory(ContainerInterface $container): ConstraintValidatorFactoryInterface
    {
        if ($container->has(ConstraintValidatorFactoryInterface::class)) {
            return $container->get(ConstraintValidatorFactoryInterface::class);
        }

        // see https://github.com/symfony/validator/commit/c7e2dd03170a27f8ac04f2908fe7a6a4ca17e0f2
        return new ConstraintValidatorFactory(
            [EmailValidator::class => new EmailValidator(Email::VALIDATION_MODE_HTML5)]
        );
    }
}

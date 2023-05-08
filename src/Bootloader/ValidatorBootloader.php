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
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\EmailValidator;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\ContainerConstraintValidatorFactory;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidatorBootloader extends Bootloader
{
    protected const DEPENDENCIES = [
        ValidationBootloader::class
    ];

    protected const SINGLETONS = [
        Validation::class => Validation::class,
        ValidatorInterface::class => [self::class, 'initSymfonyValidator'],
        ConstraintValidatorFactoryInterface::class => ContainerConstraintValidatorFactory::class,
    ];

    protected const BINDINGS = [
        EmailValidator::class => [self::class, 'initEmailValidator']
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

    private function initSymfonyValidator(ConstraintValidatorFactoryInterface $validatorFactory): ValidatorInterface
    {
        return \Symfony\Component\Validator\Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->setConstraintValidatorFactory($validatorFactory)
            ->getValidator();
    }

    /**
     * See https://github.com/symfony/validator/commit/c7e2dd03170a27f8ac04f2908fe7a6a4ca17e0f2
     */
    private function initEmailValidator(): EmailValidator
    {
        return new EmailValidator(Email::VALIDATION_MODE_HTML5);
    }
}

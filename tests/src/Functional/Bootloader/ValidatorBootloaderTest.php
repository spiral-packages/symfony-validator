<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\Functional\Bootloader;

use Spiral\Validation\Symfony\FilterDefinition;
use Spiral\Validation\Symfony\Tests\Functional\TestCase;
use Spiral\Validation\Symfony\Validation;
use Spiral\Validation\ValidationInterface;
use Spiral\Validation\ValidationProviderInterface;
use Spiral\Validation\Symfony\Bootloader\ValidatorBootloader;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\EmailValidator;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\ContainerConstraintValidatorFactory;

final class ValidatorBootloaderTest extends TestCase
{
    public function testBootloaderRegistered(): void
    {
        $this->assertBootloaderRegistered(ValidatorBootloader::class);
    }

    public function testValidationRegistered(): void
    {
        $provider = $this->getContainer()->get(ValidationProviderInterface::class);

        $this->assertInstanceOf(Validation::class, $provider->getValidation(FilterDefinition::class));
        $this->assertContainerBoundAsSingleton(ValidationInterface::class, Validation::class);
    }

    public function testConstraintValidatorFactoryShouldBeBoundAsSingleton(): void
    {
        $this->assertContainerBoundAsSingleton(
            ConstraintValidatorFactoryInterface::class,
            ContainerConstraintValidatorFactory::class
        );
    }

    public function testEmailValidatorShouldBeBound(): void
    {
        $factory = $this->getContainer()->get(ConstraintValidatorFactoryInterface::class);
        $validator = $factory->getInstance(new Email());

        $this->assertInstanceOf(EmailValidator::class, $validator);
        $this->assertSame(
            Email::VALIDATION_MODE_HTML5,
            (new \ReflectionProperty($validator, 'defaultMode'))->getValue($validator)
        );
    }
}

<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\Functional\Bootloader;

use Spiral\Validation\Symfony\FilterDefinition;
use Spiral\Validation\Symfony\Tests\Functional\TestCase;
use Spiral\Validation\Symfony\Validation;
use Spiral\Validation\ValidationInterface;
use Spiral\Validation\ValidationProviderInterface;
use Spiral\Validation\Symfony\Bootloader\ValidatorBootloader;
use Symfony\Component\Validator\Constraints\EmailValidator;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;

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
            ConstraintValidatorFactory::class
        );
    }

    public function testDefaultConstraintValidatorFactoryContainsEmailValidator(): void
    {
        $factory = $this->getContainer()->get(ConstraintValidatorFactoryInterface::class);
        $validators = (new \ReflectionProperty($factory, 'validators'))->getValue($factory);

        $this->assertCount(1, $validators);
        $this->assertInstanceOf(EmailValidator::class, $validators[EmailValidator::class]);
    }
}

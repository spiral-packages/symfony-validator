<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\Functional\Bootloader;

use Spiral\Boot\BootloadManagerInterface;
use Spiral\Validation\Symfony\Bootloader\ValidatorBootloader;
use Spiral\Validation\Symfony\FilterDefinition;
use Spiral\Validation\Symfony\Tests\Functional\TestCase;
use Spiral\Validation\Symfony\Validation;
use Spiral\Validation\ValidationInterface;
use Spiral\Validation\ValidationProviderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\EmailValidator;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\ContainerConstraintValidatorFactory;
use Symfony\Component\Validator\Validator\RecursiveValidator;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Testo\Assert;
use Testo\Test;

#[Test]
final class ValidatorBootloaderTest extends TestCase
{
    public function testBootloaderRegistered(): void
    {
        Assert::contains(
            $this->getContainer()->get(BootloadManagerInterface::class)->getClasses(),
            ValidatorBootloader::class,
        );
    }

    public function testValidationRegistered(): void
    {
        $provider = $this->getContainer()->get(ValidationProviderInterface::class);

        Assert::instanceOf($provider->getValidation(FilterDefinition::class), Validation::class);
        $this->assertContainerBoundAsSingleton(ValidationInterface::class, Validation::class);
    }

    public function testConstraintValidatorFactoryShouldBeBoundAsSingleton(): void
    {
        $this->assertContainerBoundAsSingleton(
            ConstraintValidatorFactoryInterface::class,
            ContainerConstraintValidatorFactory::class,
        );
    }

    public function testEmailValidatorShouldBeBound(): void
    {
        $factory = $this->getContainer()->get(ConstraintValidatorFactoryInterface::class);
        $validator = $factory->getInstance(new Email());

        Assert::instanceOf($validator, EmailValidator::class);
        Assert::same(
            (new \ReflectionProperty($validator, 'defaultMode'))->getValue($validator),
            Email::VALIDATION_MODE_HTML5,
        );
    }

    public function testSymfonyValidatorShouldBeBoundAsSingleton(): void
    {
        $this->assertContainerBoundAsSingleton(ValidatorInterface::class, RecursiveValidator::class);
    }
}

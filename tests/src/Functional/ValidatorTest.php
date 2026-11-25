<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\Functional;

use Spiral\Validation\Symfony\Tests\App\Filters\SimpleFilter;
use Spiral\Validation\Symfony\Validation;
use Spiral\Validation\ValidatorInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Testo\Assert;
use Testo\Skip;
use Testo\Test;

#[Test]
final class ValidatorTest extends TestCase
{
    public function testValidArrayData(): void
    {
        $validator = $this->validate(['email' => 'foo@gmail.com'], ['email' => [new Constraints\Email()]]);

        Assert::true($validator->isValid());
        Assert::same($validator->getErrors(), []);
    }

    public function testInvalidArrayData(): void
    {
        $validator = $this->validate(
            ['username' => '', 'email' => 'foo'],
            ['username' => [new Constraints\NotBlank()], 'email' => [new Constraints\Email()]],
        );

        Assert::false($validator->isValid());
        Assert::count($validator->getErrors(), 2);
    }

    public function testObjectDataValidatedByAttributes(): void
    {
        $filter = new SimpleFilter();
        $filter->username = 'foo';
        $filter->email = 'foo';

        $validator = $this->validate($filter);

        Assert::false($validator->isValid());
        Assert::same($validator->getErrors(), ['email' => ['This value is not a valid email address.']]);
        Assert::true($validator->hasError('email'));
        Assert::false($validator->hasError('username'));
    }

    public function testWithDataValidatesNewData(): void
    {
        $rules = ['email' => [new Constraints\Email()]];
        $validator = $this->validate(['email' => 'foo'], $rules);
        Assert::false($validator->isValid());

        $copy = $validator->withData(['email' => 'foo@gmail.com']);

        Assert::notSame($copy, $validator);
        Assert::true($copy->isValid());
        Assert::same($copy->getValue('email'), 'foo@gmail.com');
        Assert::false($validator->isValid());
        Assert::same($validator->getValue('email'), 'foo');
    }

    public function testContextSelectsValidationGroups(): void
    {
        $validator = $this->validate(
            ['username' => ''],
            ['username' => [new Constraints\NotBlank(groups: ['strict'])]],
        );

        Assert::null($validator->getContext());
        Assert::true($validator->isValid());

        $strict = $validator->withContext('strict');

        Assert::notSame($strict, $validator);
        Assert::same($strict->getContext(), 'strict');
        Assert::false($strict->isValid());
        Assert::null($validator->getContext());
    }

    public function testErrorsAreComputedOnce(): void
    {
        $calls = 0;
        $rule = new Constraints\Callback(
            static function (mixed $value, ExecutionContextInterface $context) use (&$calls): void {
                ++$calls;
                $context->addViolation('Invalid.');
            },
        );
        $validator = $this->validate(['name' => 'foo'], ['name' => [$rule]]);

        Assert::false($validator->isValid());
        Assert::same($validator->getErrors(), $validator->getErrors());
        Assert::same($calls, 1);
    }

    public function testWithContextDropsPreviousErrors(): void
    {
        $validator = $this->validate(['username' => ''], ['username' => [new Constraints\NotBlank()]], 'Default');
        Assert::false($validator->isValid());

        Assert::true($validator->withContext('strict')->isValid());
    }

    public function testGetValueFromArrayData(): void
    {
        $value = new class {
            public function getValue(): string
            {
                return 'unwrapped';
            }
        };
        $validator = $this->validate(['name' => 'foo', 'empty' => null, 'wrapped' => $value]);

        Assert::same($validator->getValue('name'), 'foo');
        Assert::same($validator->getValue('name', 'default'), 'foo');
        Assert::same($validator->getValue('wrapped'), 'unwrapped');
        Assert::null($validator->getValue('missing'));
        Assert::same($validator->getValue('missing', 'default'), 'default');
        Assert::same($validator->getValue('empty', 'default'), 'default');
    }

    public function testHasValueInArrayData(): void
    {
        $validator = $this->validate(['name' => 'foo', 'empty' => null]);

        Assert::true($validator->hasValue('name'));
        Assert::true($validator->hasValue('empty'));
        Assert::false($validator->hasValue('missing'));
    }

    public function testHasValueInArrayAccessData(): void
    {
        $validator = $this->validate(new \ArrayObject(['name' => 'foo', 'empty' => null]));

        Assert::true($validator->hasValue('name'));
        Assert::false($validator->hasValue('empty'));
        Assert::false($validator->hasValue('missing'));
    }

    #[Skip('Bug: errors of array data are keyed by the property path "[email]", so hasError("email") is false')]
    public function testHasErrorForArrayData(): void
    {
        $validator = $this->validate(['email' => 'foo'], ['email' => [new Constraints\Email()]]);

        Assert::true($validator->hasError('email'));
    }

    #[Skip('Bug: getValue() and hasValue() read the data as an array and fail on a filter object')]
    public function testValueOfObjectData(): void
    {
        $filter = new SimpleFilter();
        $filter->username = 'foo';

        $validator = $this->validate($filter);

        Assert::true($validator->hasValue('username'));
        Assert::same($validator->getValue('username'), 'foo');
    }

    #[Skip('Bug: messages keep Symfony placeholders and plural variants, e.g. "{{ limit }} character or more.|..."')]
    public function testErrorMessageIsInterpolated(): void
    {
        $validator = $this->validate(['name' => 'foo'], ['name' => [new Constraints\Length(min: 5)]]);

        Assert::same(
            \array_values($validator->getErrors()),
            [['This value is too short. It should have 5 characters or more.']],
        );
    }

    private function validate(mixed $data, array $rules = [], mixed $context = null): ValidatorInterface
    {
        return $this->getContainer()->get(Validation::class)->validate($data, $rules, $context);
    }
}

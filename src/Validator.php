<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony;

use Spiral\Translator\Traits\TranslatorTrait;
use Spiral\Validation\ValidatorInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\ConstraintViolationInterface;

final class Validator implements ValidatorInterface
{
    use TranslatorTrait;

    /** @var array<string, list<string>> */
    private array $errors;

    private mixed $context;
    private ?Constraints\Collection $rules = null;

    /**
     * @psalm-suppress MixedArgumentTypeCoercion Symfony's Collection checks the rule types itself and throws on invalid ones.
     */
    public function __construct(
        private \Symfony\Component\Validator\Validator\ValidatorInterface $validator,
        private mixed $data,
        array $rules,
        mixed $context = null,
    ) {
        if ($rules !== []) {
            $this->rules = new Constraints\Collection($rules);
        }

        $this->context = $context;
        $this->errors = [];
    }

    #[\Override]
    public function withData(mixed $data): ValidatorInterface
    {
        $validator = clone $this;
        $validator->data = $data;

        return $validator;
    }

    #[\Override]
    public function getValue(string $field, mixed $default = null): mixed
    {
        return $this->unwrap($this->fields()[$field] ?? $default);
    }

    #[\Override]
    public function hasValue(string $field): bool
    {
        return self::hasField($this->fields(), $field);
    }

    #[\Override]
    public function withContext(mixed $context): ValidatorInterface
    {
        $validator = clone $this;
        $validator->context = $context;
        $validator->errors = [];

        return $validator;
    }

    #[\Override]
    public function getContext(): mixed
    {
        return $this->context;
    }

    #[\Override]
    public function isValid(): bool
    {
        return $this->getErrors() === [];
    }

    /**
     * @psalm-suppress InvalidReturnStatement, InvalidReturnType Bug: each field holds a list of messages,
     *     while the interface promises a single message per field.
     */
    #[\Override]
    public function getErrors(): array
    {
        $this->validate();

        return $this->errors;
    }

    /**
     * Check if value has any error associated.
     */
    public function hasError(string $field): bool
    {
        return isset($this->getErrors()[$field]);
    }

    public function __clone()
    {
        $this->errors = [];
    }

    /**
     * Destruct the service.
     */
    public function __destruct()
    {
        $this->data = [];
    }

    final protected function validate(): void
    {
        if ($this->errors !== []) {
            // already validated
            return;
        }

        $this->errors = [];

        /** @var ConstraintViolationInterface[] $violations */
        $violations = $this->validator->validate($this->data, $this->rules, $this->groups());

        foreach ($violations as $violation) {
            $this->errors[$violation->getPropertyPath()][] = $this->say(
                $violation->getMessageTemplate(),
                $violation->getParameters(),
            );
        }
    }

    private static function hasField(mixed $fields, string $field): bool
    {
        if (is_array($fields)) {
            return array_key_exists($field, $fields);
        }

        return isset($fields[$field]);
    }

    /**
     * The context is arbitrary: it selects Symfony validation groups only when it is groups, any other context
     * validates the default group.
     *
     * @return string|GroupSequence|list<string|GroupSequence>|null
     */
    private function groups(): string|GroupSequence|array|null
    {
        $context = $this->context;
        if (is_string($context) || $context instanceof GroupSequence) {
            return $context;
        }

        if (!is_array($context)) {
            return null;
        }

        $groups = [];
        foreach ($context as $group) {
            if (!is_string($group) && !$group instanceof GroupSequence) {
                return null;
            }

            $groups[] = $group;
        }

        return $groups;
    }

    /**
     * Object data other than {@see \ArrayAccess} (e.g. an {@see AttributesFilter}) is read through its public
     * properties; an uninitialized typed property counts as missing.
     */
    private function fields(): mixed
    {
        if (is_object($this->data) && !$this->data instanceof \ArrayAccess) {
            return get_object_vars($this->data);
        }

        return $this->data;
    }

    private function unwrap(mixed $value): mixed
    {
        if (is_object($value) && method_exists($value, 'getValue')) {
            return $value->getValue();
        }

        return $value;
    }
}

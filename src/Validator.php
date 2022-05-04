<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony;

use Spiral\Translator\Traits\TranslatorTrait;
use Spiral\Validation\ValidatorInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\ConstraintViolationInterface;

final class Validator implements ValidatorInterface
{
    use TranslatorTrait;

    private array $errors;
    private array|string|null $context;
    private ?Constraints\Collection $rules = null;

    public function __construct(
        private \Symfony\Component\Validator\Validator\ValidatorInterface $validator,
        private mixed $data,
        array $rules,
        array|string|null $context = null
    ) {
        if ($rules !== []) {
            $this->rules = new Constraints\Collection($rules);
        }

        $this->context = $context;
        $this->errors = [];
    }

    /**
     * Destruct the service.
     */
    public function __destruct()
    {
        $this->data = [];
    }

    public function __clone()
    {
        $this->errors = [];
    }

    public function withData(mixed $data): ValidatorInterface
    {
        $validator = clone $this;
        $validator->data = $data;

        return $validator;
    }

    /**
     * @inheritdoc
     */
    public function getValue(string $field, mixed $default = null): mixed
    {
        $value = $this->data[$field] ?? $default;

        if (is_object($value) && method_exists($value, 'getValue')) {
            return $value->getValue();
        }

        return $value;
    }

    public function hasValue(string $field): bool
    {
        if (is_array($this->data)) {
            return array_key_exists($field, $this->data);
        }

        return isset($this->data[$field]);
    }

    public function withContext(mixed $context): ValidatorInterface
    {
        $validator = clone $this;
        $validator->context = $context;
        $validator->errors = [];

        return $validator;
    }

    public function getContext(): mixed
    {
        return $this->context;
    }

    public function isValid(): bool
    {
        return $this->getErrors() === [];
    }

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

    final protected function validate(): void
    {
        if ($this->errors !== []) {
            // already validated
            return;
        }

        $this->errors = [];

        /** @var ConstraintViolationInterface[] $violations */
        $violations = $this->validator->validate($this->data, $this->rules, $this->context);

        foreach ($violations as $violation) {
            $this->errors[$violation->getPropertyPath()][] = $this->say(
                $violation->getMessageTemplate(),
                $violation->getParameters()
            );
        }
    }
}

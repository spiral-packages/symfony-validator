<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony;

use Spiral\Filters\Filter;
use Spiral\Validation\ValidationInterface;
use Spiral\Validation\ValidatorInterface;
use Spiral\Filters\FilterBag;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidatorInterface;

class Validation implements ValidationInterface
{
    public function __construct(
        private SymfonyValidatorInterface $validator
    ) {
    }

    public function validate(mixed $data, array $rules, $context = null): ValidatorInterface
    {
        if ($data instanceof FilterBag) {
            $data = $data->filter;
        }

        if ($data instanceof Filter && !$data instanceof AttributesFilter) {
            $data = $data->getData();
        }

        return new Validator($this->validator, $data, $rules, $context);
    }
}

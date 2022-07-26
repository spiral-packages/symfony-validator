<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony;

use Spiral\Filters\Model\Filter;
use Spiral\Validation\ValidationInterface;
use Spiral\Validation\ValidatorInterface;
use Spiral\Filters\Model\FilterBag;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidatorInterface;

class Validation implements ValidationInterface
{
    public function __construct(
        private readonly SymfonyValidatorInterface $validator
    ) {
    }

    public function validate(mixed $data, array $rules, $context = null): ValidatorInterface
    {
        if ($data instanceof FilterBag) {
            $filter = $data->filter;

            if ($filter instanceof AttributesFilter) {
                $data = $filter;
            } elseif ($filter instanceof Filter) {
                $data = $filter->getData();
            } else {
                $data = $data->entity->toArray();
            }
        }

        return new Validator($this->validator, $data, $rules, $context);
    }
}

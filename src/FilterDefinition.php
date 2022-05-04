<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony;

use Spiral\Filters\FilterDefinitionInterface;
use Spiral\Filters\ShouldBeValidated;

class FilterDefinition implements FilterDefinitionInterface, ShouldBeValidated
{
    public function mappingSchema(): array
    {
        return [];
    }

    public function validationRules(): array
    {
        return [];
    }
}

<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony;

use Spiral\Filters\Filter;
use Spiral\Filters\FilterDefinitionInterface;
use Spiral\Filters\HasFilterDefinition;

class AttributesFilter extends Filter implements HasFilterDefinition
{
    public function filterDefinition(): FilterDefinitionInterface
    {
        return new FilterDefinition();
    }
}

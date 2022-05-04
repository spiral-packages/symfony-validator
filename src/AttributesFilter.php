<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony;

use Spiral\Filters\Filter;
use Spiral\Filters\FilterDefinitionInterface;

class AttributesFilter extends Filter
{
    public function filterDefinition(): FilterDefinitionInterface
    {
        return new FilterDefinition();
    }
}

<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony;

use Spiral\Filters\Dto\Filter;
use Spiral\Filters\Dto\FilterDefinitionInterface;
use Spiral\Filters\Dto\HasFilterDefinition;

class AttributesFilter extends Filter implements HasFilterDefinition
{
    public function filterDefinition(): FilterDefinitionInterface
    {
        return new FilterDefinition();
    }
}

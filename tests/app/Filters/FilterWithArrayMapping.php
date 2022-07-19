<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\App\Filters;

use Spiral\Filters\Dto\Filter;
use Spiral\Filters\Dto\FilterDefinitionInterface;
use Spiral\Filters\Dto\HasFilterDefinition;
use Spiral\Validation\Symfony\FilterDefinition;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;

final class FilterWithArrayMapping extends Filter implements HasFilterDefinition
{
    public function filterDefinition(): FilterDefinitionInterface
    {
        return new FilterDefinition(
            [
                'username' => [new NotBlank()],
                'email' => [new NotBlank(), new Email()],
                'image' => [new Image()]
            ],
            [
                'username' => 'username',
                'email' => 'email',
                'image' => 'symfony-file:image'
            ]
        );
    }
}

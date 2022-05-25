<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\App\Filters;

use Spiral\Filters\Attribute\Input\Post;
use Spiral\Validation\Symfony\AttributesFilter;
use Symfony\Component\Validator\Constraints;

final class SimpleFilter extends AttributesFilter
{
    #[Post]
    #[Constraints\NotBlank]
    public string $username;

    #[Post]
    #[Constraints\Email]
    #[Constraints\NotBlank]
    public string $email;
}

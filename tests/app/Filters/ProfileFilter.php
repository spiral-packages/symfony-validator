<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\App\Filters;

use Spiral\Filters\Attribute\NestedFilter;
use Spiral\Validation\Symfony\Attribute\Input\File;
use Spiral\Validation\Symfony\AttributesFilter;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints;

/**
 * Files of a nested filter are read through a prefixed input.
 */
final class ProfileFilter extends AttributesFilter
{
    #[File]
    #[Constraints\Image]
    public UploadedFile $image;

    #[NestedFilter(class: AvatarFilter::class)]
    public AvatarFilter $child;
}

<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\App\Filters;

use Spiral\Validation\Symfony\Attribute\Input\File;
use Spiral\Validation\Symfony\AttributesFilter;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints;

final class AvatarFilter extends AttributesFilter
{
    #[File]
    #[Constraints\Image]
    public UploadedFile $avatar;
}

<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\App\Filters;

use Spiral\Validation\Symfony\Attribute\Input\File;
use Spiral\Validation\Symfony\AttributesFilter;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints;

final class AttachmentsFilter extends AttributesFilter
{
    /** @var list<UploadedFile> */
    #[File]
    #[Constraints\Count(min: 1)]
    #[Constraints\All([new Constraints\File(maxSize: '1k')])]
    public array $attachments;
}

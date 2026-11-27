<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\App\Acceptance;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Spiral\Validation\Symfony\Tests\App\Filters\AttachmentsFilter;
use Spiral\Validation\Symfony\Tests\App\Filters\CreatePostFilter;
use Spiral\Validation\Symfony\Tests\App\Filters\FilterWithArrayMapping;
use Spiral\Validation\Symfony\Tests\App\Filters\ProfileFilter;
use Spiral\Validation\Symfony\Tests\App\Filters\SimpleFilter;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Echoes the validated filter back as JSON, so a client sees what the application received.
 */
final class FormController
{
    public function simple(SimpleFilter $filter): array
    {
        return ['username' => $filter->username, 'email' => $filter->email];
    }

    public function arrayMapping(FilterWithArrayMapping $filter): array
    {
        return \array_map(self::describe(...), $filter->getData());
    }

    public function post(CreatePostFilter $filter): array
    {
        return [
            'title' => $filter->title,
            'slug' => $filter->slug,
            'sort' => $filter->sort,
            'image' => self::describe($filter->image),
        ];
    }

    /**
     * Also echoes the `notes` upload the filter does not read, as the application reads it after validation.
     */
    public function profile(ProfileFilter $filter, ServerRequestInterface $request): array
    {
        $notes = $request->getUploadedFiles()['notes'] ?? null;

        return [
            'image' => self::describe($filter->image),
            'avatar' => self::describe($filter->child->avatar),
            'notes' => $notes instanceof UploadedFileInterface ? (string) $notes->getStream() : null,
        ];
    }

    public function attachments(AttachmentsFilter $filter): array
    {
        return \array_map(self::describe(...), $filter->attachments);
    }

    private static function describe(mixed $value): mixed
    {
        return $value instanceof UploadedFile
            ? ['name' => $value->getClientOriginalName(), 'size' => $value->getSize(), 'mimeType' => $value->getMimeType()]
            : $value;
    }
}

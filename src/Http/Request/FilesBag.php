<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Http\Request;

use Psr\Http\Message\UploadedFileInterface;
use Spiral\Http\Request\InputBag;

/**
 * Uploaded files as Symfony files, keeping nested arrays (e.g. `files[]`) as arrays.
 *
 * Wraps only what is read. One wrapper per PSR upload for the whole process: the input manager builds
 * a new bag for every prefixed (nested filter) input, and all of them must hand out the same file.
 */
final class FilesBag extends InputBag
{
    /** @var \WeakMap<UploadedFileInterface, UploadedFile>|null */
    private static ?\WeakMap $files = null;

    #[\Override]
    public function all(): array
    {
        return self::wrapTree(parent::all());
    }

    /**
     * Counts without wrapping: wrapping copies every file.
     */
    #[\Override]
    public function count(): int
    {
        return \count(parent::all());
    }

    #[\Override]
    public function get(int|string $name, mixed $default = null): mixed
    {
        return $this->has($name) ? self::wrap(parent::get($name)) : $default;
    }

    #[\Override]
    public function __debugInfo(): array
    {
        return parent::all();
    }

    private static function wrap(mixed $value): mixed
    {
        return match (true) {
            $value instanceof UploadedFileInterface => self::file($value),
            \is_array($value) => self::wrapTree($value),
            default => $value,
        };
    }

    private static function file(UploadedFileInterface $file): UploadedFile
    {
        $files = self::$files ??= new \WeakMap();

        return $files[$file] ??= new UploadedFile($file);
    }

    private static function wrapTree(array $files): array
    {
        return \array_map(self::wrap(...), $files);
    }
}

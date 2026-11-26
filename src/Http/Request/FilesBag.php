<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Http\Request;

use Psr\Http\Message\UploadedFileInterface;
use Spiral\Http\Request\InputBag;
use Symfony\Bridge\PsrHttpMessage\Factory\UploadedFile;

final class FilesBag extends InputBag
{
    public function __construct(array $data, string $prefix = '')
    {
        parent::__construct($this->wrapFiles($data), $prefix);
    }

    /**
     * Gets a temporary file path.
     *
     * @psalm-suppress FalsableReturnStatement, InvalidFalsableReturnType tempnam() returns false only when the
     *     system temp directory is unusable; the resulting TypeError is the existing failure mode.
     */
    protected function getTemporaryPath(): string
    {
        return \tempnam(\sys_get_temp_dir(), \uniqid('symfony', true));
    }

    /**
     * Wraps every file of the uploaded-files tree, keeping nested arrays (e.g. `files[]`) as arrays.
     */
    private function wrapFiles(array $files): array
    {
        return \array_map(
            fn(UploadedFileInterface|array $file): UploadedFile|array => \is_array($file)
                ? $this->wrapFiles($file)
                : new UploadedFile($file, fn(): string => $this->getTemporaryPath()),
            $files,
        );
    }
}

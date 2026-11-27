<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Http\Request;

use Psr\Http\Message\UploadedFileInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\UploadedFile as BridgeUploadedFile;
use Symfony\Component\HttpFoundation\File\File;

/**
 * The bridge's Symfony view of a PSR upload that leaves the PSR upload untouched.
 *
 * The bridge builds on the stream's file when it is an HTTP upload, otherwise on a temporary copy
 * that lives as long as this object or any of its clones. Never on another local file: moving would
 * rename the caller's file.
 *
 * @internal
 */
final class UploadedFile extends BridgeUploadedFile
{
    private readonly TemporaryFile $temporary;

    public function __construct(UploadedFileInterface $file)
    {
        $temporary = $this->temporary = new TemporaryFile();

        try {
            parent::__construct(new NonConsumingUploadedFile($file), $temporary->create(...));
        } catch (\Throwable $e) {
            // The exception's trace may keep this half-built object alive.
            $temporary->remove();
            throw $e;
        }
    }

    #[\Override]
    public function move(string $directory, ?string $name = null): File
    {
        $file = parent::move($directory, $name);
        $this->temporary->release();

        return $file;
    }
}

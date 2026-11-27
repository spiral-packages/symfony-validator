<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Http\Request;

use Psr\Http\Message\UploadedFileInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\UploadedFile as BridgeUploadedFile;

/**
 * The bridge's Symfony view of a PSR upload that leaves the PSR upload untouched.
 *
 * The bridge builds on the stream's file when it is an HTTP upload, otherwise on a temporary copy
 * that lives as long as this object. Never on another local file: moving would rename the caller's file.
 *
 * @internal
 */
final class UploadedFile extends BridgeUploadedFile
{
    private ?string $temporaryPath = null;

    public function __construct(UploadedFileInterface $file)
    {
        try {
            parent::__construct(
                new NonConsumingUploadedFile($file),
                fn(): string => $this->temporaryPath = self::createTemporaryPath(),
            );
        } catch (\Throwable $e) {
            // The destructor doesn't run when the constructor throws.
            $this->removeTemporaryFile();
            throw $e;
        }
    }

    public function __destruct()
    {
        $this->removeTemporaryFile();
    }

    /**
     * @psalm-suppress FalsableReturnStatement, InvalidFalsableReturnType tempnam() returns false only when the
     *     system temp directory is unusable; the resulting TypeError is the existing failure mode.
     */
    private static function createTemporaryPath(): string
    {
        return \tempnam(\sys_get_temp_dir(), \uniqid('symfony', true));
    }

    private function removeTemporaryFile(): void
    {
        // Gone when the file was moved away.
        $this->temporaryPath === null or !\is_file($this->temporaryPath) or \unlink($this->temporaryPath);
    }
}

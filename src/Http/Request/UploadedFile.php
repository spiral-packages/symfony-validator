<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Http\Request;

use Psr\Http\Message\UploadedFileInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\UploadedFile as BridgeUploadedFile;

/**
 * The bridge's Symfony view of a PSR upload that leaves the PSR upload untouched.
 *
 * The bridge builds on the stream's file when it is an HTTP upload or, through
 * {@see NonConsumingUploadedFile::$localPath}, any local file; otherwise on a temporary copy
 * that lives as long as this object.
 *
 * @internal
 */
final class UploadedFile extends BridgeUploadedFile
{
    private ?string $temporaryPath = null;

    public function __construct(UploadedFileInterface $file)
    {
        $source = new NonConsumingUploadedFile($file);

        parent::__construct(
            $source,
            fn(): string => $source->localPath ?? $this->temporaryPath = self::createTemporaryPath(),
        );
    }

    public function __destruct()
    {
        // Gone when the file was moved away.
        $this->temporaryPath === null or !\is_file($this->temporaryPath) or \unlink($this->temporaryPath);
    }

    /**
     * @psalm-suppress FalsableReturnStatement, InvalidFalsableReturnType tempnam() returns false only when the
     *     system temp directory is unusable; the resulting TypeError is the existing failure mode.
     */
    private static function createTemporaryPath(): string
    {
        return \tempnam(\sys_get_temp_dir(), \uniqid('symfony', true));
    }
}

<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Http\Request;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * A view of a PSR upload whose {@see moveTo()} copies the content and leaves the original upload readable.
 *
 * Keeps the stream and the metadata, not the PSR upload itself: the bag caches wrappers in a {@see \WeakMap}
 * keyed by the PSR upload, and a wrapper referencing its key would keep the entry alive.
 *
 * @internal
 */
final class NonConsumingUploadedFile implements UploadedFileInterface
{
    private readonly ?StreamInterface $stream;
    private readonly ?int $size;
    private readonly int $error;
    private readonly ?string $clientFilename;
    private readonly ?string $clientMediaType;

    public function __construct(UploadedFileInterface $file)
    {
        $this->size = $file->getSize();
        $this->error = $file->getError();
        $this->clientFilename = $file->getClientFilename();
        $this->clientMediaType = $file->getClientMediaType();
        // A failed upload has no stream: PSR implementations throw on getStream().
        $this->stream = $this->error === \UPLOAD_ERR_OK ? $file->getStream() : null;
    }

    #[\Override]
    public function getStream(): StreamInterface
    {
        return $this->stream ?? throw new \RuntimeException('Cannot retrieve stream due to upload error.');
    }

    /**
     * Copies the content to the target. A non-seekable stream is copied from its position and stays drained.
     */
    #[\Override]
    public function moveTo(string $targetPath): void
    {
        $stream = $this->getStream();
        $position = $stream->isSeekable() ? $stream->tell() : null;
        $position === null or $stream->rewind();

        // Not SplFileObject: on PHP 8.2 it leaves a stale stat cache, and the copy then looks empty.
        $target = @\fopen($targetPath, 'wb') ?: throw new \RuntimeException(\sprintf('Cannot write to "%s".', $targetPath));
        // An empty read ends the copy too: some streams never report EOF.
        while (($chunk = $stream->read(1048576)) !== '') {
            \fwrite($target, $chunk) === \strlen($chunk)
                or throw new \RuntimeException(\sprintf('Cannot write to "%s".', $targetPath));
        }
        \fclose($target);

        // Whoever reads the PSR stream later starts where they left off.
        $position === null or $stream->seek($position);
    }

    #[\Override]
    public function getSize(): ?int
    {
        return $this->size;
    }

    #[\Override]
    public function getError(): int
    {
        return $this->error;
    }

    #[\Override]
    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    #[\Override]
    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }
}

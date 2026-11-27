<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\Functional\Http\Request\Stub;

use Psr\Http\Message\StreamInterface;

/**
 * A readable, non-seekable stream whose reads are answered by a callback and that never reports EOF.
 */
final class ScriptedStream implements StreamInterface
{
    /**
     * @param \Closure(int): string $read
     */
    public function __construct(
        private readonly \Closure $read,
    ) {}

    public function close(): void {}

    public function detach()
    {
        return null;
    }

    public function getSize(): ?int
    {
        return null;
    }

    public function tell(): int
    {
        throw new \RuntimeException('Not seekable.');
    }

    public function eof(): bool
    {
        return false;
    }

    public function isSeekable(): bool
    {
        return false;
    }

    public function seek(int $offset, int $whence = \SEEK_SET): void
    {
        throw new \RuntimeException('Not seekable.');
    }

    public function rewind(): void
    {
        throw new \RuntimeException('Not seekable.');
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        throw new \RuntimeException('Not writable.');
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read(int $length): string
    {
        return ($this->read)($length);
    }

    public function getContents(): string
    {
        return '';
    }

    public function getMetadata(?string $key = null)
    {
        return $key === null ? [] : null;
    }

    public function __toString(): string
    {
        return '';
    }
}

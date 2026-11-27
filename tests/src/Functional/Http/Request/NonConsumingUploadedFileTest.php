<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\Functional\Http\Request;

use Nyholm\Psr7\Stream;
use Nyholm\Psr7\UploadedFile;
use Psr\Http\Message\StreamInterface;
use Spiral\Validation\Symfony\Http\Request\NonConsumingUploadedFile;
use Spiral\Validation\Symfony\Tests\Functional\Http\Request\Stub\ScriptedStream;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class NonConsumingUploadedFileTest
{
    public function testMetadataIsTakenFromTheUpload(): void
    {
        $file = new NonConsumingUploadedFile(new UploadedFile(Stream::create('content'), 7, \UPLOAD_ERR_OK, 'a.txt', 'text/plain'));

        Assert::same($file->getSize(), 7);
        Assert::same($file->getError(), \UPLOAD_ERR_OK);
        Assert::same($file->getClientFilename(), 'a.txt');
        Assert::same($file->getClientMediaType(), 'text/plain');
    }

    public function testFailedUploadHasNoStream(): void
    {
        $file = new NonConsumingUploadedFile(new UploadedFile(Stream::create(''), 0, \UPLOAD_ERR_NO_FILE));
        Expect::exception(\RuntimeException::class);

        $file->getStream();
    }

    public function testUnwritableTargetFails(): void
    {
        $file = new NonConsumingUploadedFile(new UploadedFile(Stream::create('content'), 7, \UPLOAD_ERR_OK));
        Expect::exception(\RuntimeException::class);

        $file->moveTo(\sys_get_temp_dir() . '/missing-' . \uniqid() . '/file');
    }

    public function testLocalFileIsCopiedToOtherTarget(): void
    {
        $source = self::temporaryFile('content');
        $file = new NonConsumingUploadedFile(new UploadedFile($source, 7, \UPLOAD_ERR_OK));
        $target = self::temporaryFile('');

        $file->moveTo($target);

        Assert::same(\file_get_contents($source), 'content');
        Assert::same(\file_get_contents($target), 'content');
        \unlink($target);
        \unlink($source);
    }

    public function testCopyEndsOnEmptyReadWithoutEof(): void
    {
        $chunks = ['con', 'tent', ''];
        $stream = new ScriptedStream(static function () use (&$chunks): string {
            return \array_shift($chunks) ?? throw new \LogicException('Read after the end.');
        });
        $file = new NonConsumingUploadedFile(new UploadedFile($stream, 7, \UPLOAD_ERR_OK));
        $target = self::temporaryFile('');

        $file->moveTo($target);

        Assert::same(\file_get_contents($target), 'content');
        \unlink($target);
    }

    public function testNonSeekableStreamIsCopiedFromItsPosition(): void
    {
        $stream = Stream::create('content');
        $stream->seek(3);
        $nonSeekable = new class($stream) implements StreamInterface {
            public function __construct(private readonly StreamInterface $stream) {}

            public function __toString(): string
            {
                return (string) $this->stream;
            }

            public function close(): void
            {
                $this->stream->close();
            }

            public function detach()
            {
                return $this->stream->detach();
            }

            public function getSize(): ?int
            {
                return $this->stream->getSize();
            }

            public function tell(): int
            {
                throw new \RuntimeException('Not seekable.');
            }

            public function eof(): bool
            {
                return $this->stream->eof();
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
                return $this->stream->read($length);
            }

            public function getContents(): string
            {
                return $this->stream->getContents();
            }

            public function getMetadata(?string $key = null)
            {
                return $this->stream->getMetadata($key);
            }
        };
        $file = new NonConsumingUploadedFile(new UploadedFile($nonSeekable, 7, \UPLOAD_ERR_OK));
        $target = self::temporaryFile('');

        $file->moveTo($target);

        Assert::same(\file_get_contents($target), 'tent');
        \unlink($target);
    }

    public function testStreamIsCopiedInChunksOfOneMebibyte(): void
    {
        $content = \str_repeat('x', 1048577);
        $stream = new class(Stream::create($content)) implements StreamInterface {
            /** @var list<int> */
            public array $reads = [];

            public function __construct(private readonly StreamInterface $stream) {}

            public function __toString(): string
            {
                return (string) $this->stream;
            }

            public function close(): void
            {
                $this->stream->close();
            }

            public function detach()
            {
                return $this->stream->detach();
            }

            public function getSize(): ?int
            {
                return $this->stream->getSize();
            }

            public function tell(): int
            {
                return $this->stream->tell();
            }

            public function eof(): bool
            {
                return $this->stream->eof();
            }

            public function isSeekable(): bool
            {
                return true;
            }

            public function seek(int $offset, int $whence = \SEEK_SET): void
            {
                $this->stream->seek($offset, $whence);
            }

            public function rewind(): void
            {
                $this->stream->rewind();
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
                $this->reads[] = $length;

                return $this->stream->read($length);
            }

            public function getContents(): string
            {
                return $this->stream->getContents();
            }

            public function getMetadata(?string $key = null)
            {
                return $this->stream->getMetadata($key);
            }
        };
        $file = new NonConsumingUploadedFile(new UploadedFile($stream, \strlen($content), \UPLOAD_ERR_OK));
        $target = self::temporaryFile('');

        $file->moveTo($target);

        Assert::same(\file_get_contents($target), $content);
        Assert::same(\array_unique($stream->reads), [1048576]);
        \unlink($target);
    }

    private static function temporaryFile(string $content): string
    {
        $path = (string) \tempnam(\sys_get_temp_dir(), 'non-consuming');
        \file_put_contents($path, $content);

        return $path;
    }
}

<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\Functional\Http\Request;

use Nyholm\Psr7\Stream;
use Nyholm\Psr7\UploadedFile as PsrUploadedFile;
use Spiral\Validation\Symfony\Http\Request\FilesBag;
use Spiral\Validation\Symfony\Tests\Functional\Http\Request\Stub\ScriptedStream;
use Symfony\Bridge\PsrHttpMessage\Factory\UploadedFile;
use Testo\Assert;
use Testo\Test;

#[Test]
final class FilesBagTest
{
    private const FIXTURE = __DIR__ . '/../../../../app/fixtures/sample-1.jpg';

    public function testFlatFileIsWrapped(): void
    {
        $bag = new FilesBag(['image' => self::file('photo.jpg')]);

        $image = $bag->get('image');
        Assert::instanceOf($image, UploadedFile::class);
        Assert::same($image->getClientOriginalName(), 'photo.jpg');
        Assert::same($image->getClientMimeType(), 'text/plain');
        Assert::same($image->getContent(), 'content');
        Assert::true($image->isValid());
    }

    public function testNestedTreeIsWrappedKeepingItsStructure(): void
    {
        $bag = new FilesBag([
            'image' => self::file('photo.jpg'),
            'attachments' => [self::file('a.txt'), ['deep' => self::file('b.txt')]],
        ]);

        Assert::same(\array_keys($bag->all()), ['image', 'attachments']);
        Assert::same(\array_keys($bag->get('attachments')), [0, 1]);
        Assert::same(\array_keys($bag->get('attachments.1')), ['deep']);
        foreach (['image' => 'photo.jpg', 'attachments.0' => 'a.txt', 'attachments.1.deep' => 'b.txt'] as $path => $name) {
            $file = $bag->get($path);
            Assert::instanceOf($file, UploadedFile::class);
            Assert::same($file->getClientOriginalName(), $name);
        }
        Assert::instanceOf($bag->all()['attachments'][1]['deep'], UploadedFile::class);
    }

    public function testOtherValuesAreReturnedAsIs(): void
    {
        $default = self::file('default.txt');
        $bag = new FilesBag(['scalar' => 'value', 'empty' => null]);

        Assert::same($bag->get('scalar'), 'value');
        Assert::same($bag->all(), ['scalar' => 'value', 'empty' => null]);
        Assert::null($bag->get('empty', 'default'));
        Assert::same($bag->get('missing', $default), $default);
    }

    public function testUnreadFileIsNotTouched(): void
    {
        $unread = self::file('unread.txt');
        $stream = $unread->getStream();
        $stream->seek(3);

        (new FilesBag(['image' => self::file('photo.jpg'), 'unread' => $unread]))->get('image');

        Assert::same($stream->tell(), 3);
        Assert::same($stream->getContents(), 'tent');
    }

    public function testReadFileStaysReadable(): void
    {
        $psr = self::file('photo.jpg');
        $stream = $psr->getStream();
        $stream->seek(3);

        $file = (new FilesBag(['image' => $psr]))->get('image');

        Assert::same($file->getContent(), 'content');
        Assert::same($stream->getContents(), 'tent');
        Assert::same((string) $psr->getStream(), 'content');
    }

    public function testSameUploadIsWrappedOnceAcrossBags(): void
    {
        $files = ['image' => self::file('photo.jpg'), 'child' => ['avatar' => self::file('avatar.jpg')]];

        $image = (new FilesBag($files))->get('image');
        $avatar = (new FilesBag($files))->get('child.avatar');

        Assert::same((new FilesBag($files))->get('image'), $image);
        Assert::same((new FilesBag($files))->all()['image'], $image);
        Assert::same((new FilesBag($files, 'child'))->get('avatar'), $avatar);
    }

    public function testTemporaryCopyIsRemovedWithTheUpload(): void
    {
        $psr = self::file('photo.jpg');
        $path = (new FilesBag(['image' => $psr]))->get('image')->getPathname();
        Assert::same(\file_get_contents($path), 'content');

        unset($psr);

        Assert::false(\is_file($path));
    }

    public function testTemporaryCopyLivesAsLongAsTheFile(): void
    {
        $psr = self::file('photo.jpg');
        $file = (new FilesBag(['image' => $psr]))->get('image');
        unset($psr);

        Assert::same($file->getContent(), 'content');

        $path = $file->getPathname();
        unset($file);

        Assert::false(\is_file($path));
    }

    public function testMovedFileIsNotRemoved(): void
    {
        $dir = \sys_get_temp_dir() . '/' . \uniqid('files-bag', true);
        $psr = self::file('photo.jpg');
        $file = (new FilesBag(['image' => $psr]))->get('image');
        $temporary = $file->getPathname();

        $moved = $file->move($dir, 'moved.txt');
        unset($file, $psr);

        Assert::false(\is_file($temporary));
        Assert::same(\file_get_contents($moved->getPathname()), 'content');
        \unlink($moved->getPathname());
        \rmdir($dir);
    }

    public function testLocalFileSurvivesMove(): void
    {
        $path = (string) \tempnam(\sys_get_temp_dir(), 'files-bag');
        \copy(self::FIXTURE, $path);
        $psr = new PsrUploadedFile(\fopen($path, 'rb'), \filesize($path), \UPLOAD_ERR_OK, 'photo.jpg', 'image/jpeg');
        $dir = \sys_get_temp_dir() . '/files-bag-' . \uniqid();

        $file = (new FilesBag(['image' => $psr]))->get('image');
        Assert::notSame($file->getPathname(), $path);
        Assert::true($file->isValid());
        $moved = $file->move($dir, 'moved.jpg');

        Assert::same(\file_get_contents($moved->getPathname()), \file_get_contents(self::FIXTURE));
        Assert::same(\file_get_contents($path), \file_get_contents(self::FIXTURE));
        Assert::same((string) $psr->getStream(), \file_get_contents(self::FIXTURE));
        unset($file, $psr);
        \unlink($moved->getPathname());
        \rmdir($dir);
        \unlink($path);
    }

    public function testTemporaryCopyIsRemovedWhenCopyingFails(): void
    {
        $copy = null;
        $stream = new ScriptedStream(static function () use (&$copy): string {
            // The copy being written is the only open plain file of the temp dir.
            foreach (\get_resources('stream') as $resource) {
                $uri = \stream_get_meta_data($resource)['uri'] ?? '';
                \is_file($uri) && \str_starts_with(\realpath($uri), \realpath(\sys_get_temp_dir())) and $copy = $uri;
            }
            throw new \RuntimeException('Broken stream.');
        });
        $psr = new PsrUploadedFile($stream, 7, \UPLOAD_ERR_OK, 'a.txt', 'text/plain');

        try {
            (new FilesBag(['file' => $psr]))->get('file');
            Assert::fail('The copy must fail.');
        } catch (\RuntimeException $e) {
            Assert::same($e->getMessage(), 'Broken stream.');
        }

        Assert::string($copy);
        Assert::false(\is_file($copy));
    }

    public function testFailedUploadKeepsItsError(): void
    {
        $psr = new PsrUploadedFile(Stream::create(''), 0, \UPLOAD_ERR_NO_FILE, '', 'application/octet-stream');

        $file = (new FilesBag(['image' => $psr]))->get('image');

        Assert::instanceOf($file, UploadedFile::class);
        Assert::same($file->getError(), \UPLOAD_ERR_NO_FILE);
        Assert::false($file->isValid());
        Assert::same($file->getPathname(), '');
    }

    private static function file(string $name): PsrUploadedFile
    {
        return new PsrUploadedFile(Stream::create('content'), 7, \UPLOAD_ERR_OK, $name, 'text/plain');
    }
}

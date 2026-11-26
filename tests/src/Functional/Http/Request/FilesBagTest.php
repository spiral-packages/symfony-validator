<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\Functional\Http\Request;

use Nyholm\Psr7\Stream;
use Nyholm\Psr7\UploadedFile as PsrUploadedFile;
use Spiral\Validation\Symfony\Http\Request\FilesBag;
use Symfony\Bridge\PsrHttpMessage\Factory\UploadedFile;
use Testo\Assert;
use Testo\Test;

#[Test]
final class FilesBagTest
{
    public function testFlatFileIsWrapped(): void
    {
        $bag = new FilesBag(['image' => self::file('photo.jpg')]);

        $image = $bag->get('image');
        Assert::instanceOf($image, UploadedFile::class);
        Assert::same($image->getClientOriginalName(), 'photo.jpg');
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
    }

    private static function file(string $name): PsrUploadedFile
    {
        return new PsrUploadedFile(Stream::create('content'), 7, \UPLOAD_ERR_OK, $name, 'text/plain');
    }
}

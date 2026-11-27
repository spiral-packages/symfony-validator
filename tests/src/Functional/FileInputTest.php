<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\Functional;

use Nyholm\Psr7\Stream;
use Nyholm\Psr7\UploadedFile;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Spiral\Filters\Exception\ValidationException;
use Spiral\Framework\Spiral;
use Spiral\Testing\Attribute\TestScope;
use Spiral\Validation\Symfony\Tests\App\Filters\AttachmentsFilter;
use Spiral\Validation\Symfony\Tests\App\Filters\CreatePostFilter;
use Spiral\Validation\Symfony\Tests\App\Filters\ProfileFilter;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

/**
 * Filters reading files with `#[File]` from uploads that are not HTTP uploads, as under RoadRunner.
 */
#[Test]
#[TestScope(Spiral::HttpRequest)]
final class FileInputTest extends TestCase
{
    public function testUploadsStayReadableAfterValidation(): void
    {
        $image = self::image();
        $unrelated = self::upload('unrelated.txt', 'Unrelated content.');

        $filter = $this->createFilter(CreatePostFilter::class, $this->createRequest(
            ['title' => 'New post', 'slug' => 'new-post', 'sort' => 1],
            ['image' => $image, 'unrelated' => $unrelated],
        ));

        Assert::instanceOf($filter->image, SymfonyUploadedFile::class);
        Assert::same((string) $unrelated->getStream(), 'Unrelated content.');
        Assert::same((string) $image->getStream(), self::imageContent());
    }

    public function testNestedFilterReadsFiles(): void
    {
        $image = self::image();
        $avatar = self::image();

        $filter = $this->createFilter(ProfileFilter::class, $this->createRequest([], [
            'image' => $image,
            'child' => ['avatar' => $avatar],
        ]));

        Assert::same((string) $filter->image->getContent(), self::imageContent());
        Assert::same((string) $filter->child->avatar->getContent(), self::imageContent());
        Assert::same((string) $avatar->getStream(), self::imageContent());
    }

    public function testNestedFilterValidatesFiles(): void
    {
        $request = $this->createRequest([], [
            'image' => self::image(),
            'child' => ['avatar' => self::upload('avatar.txt', 'Not an image.')],
        ]);

        try {
            $this->createFilter(ProfileFilter::class, $request);
        } catch (ValidationException $e) {
            Assert::same(\array_keys($e->errors), ['child']);
            return;
        }

        Assert::fail('The nested file is not validated.');
    }

    public function testMultipleUploadIsReadAsArray(): void
    {
        $filter = $this->createFilter(AttachmentsFilter::class, $this->createRequest([], [
            'attachments' => [self::upload('a.txt', 'A'), self::upload('b.txt', 'B')],
        ]));

        Assert::same(\array_map(
            static fn(SymfonyUploadedFile $file): string => $file->getClientOriginalName(),
            $filter->attachments,
        ), ['a.txt', 'b.txt']);
    }

    public function testFilesOfMultipleUploadAreValidated(): void
    {
        $request = $this->createRequest([], [
            'attachments' => [self::upload('a.txt', 'A'), self::upload('big.txt', \str_repeat('B', 2000))],
        ]);
        Expect::exception(ValidationException::class);

        $this->createFilter(AttachmentsFilter::class, $request);
    }

    private static function image(): UploadedFileInterface
    {
        return self::upload('photo.jpg', self::imageContent());
    }

    private static function upload(string $name, string $content): UploadedFileInterface
    {
        return new UploadedFile(Stream::create($content), \strlen($content), \UPLOAD_ERR_OK, $name);
    }

    private static function imageContent(): string
    {
        return (string) \file_get_contents(\dirname(__DIR__, 2) . '/app/fixtures/sample-1.jpg');
    }

    /**
     * @template T of object
     * @param class-string<T> $filterClass
     * @return T
     */
    private function createFilter(string $filterClass, ServerRequestInterface $request): object
    {
        $container = $this->getContainer();
        $container->bindSingleton(ServerRequestInterface::class, $request);

        return $container->get($filterClass);
    }

    private function createRequest(array $data, array $files): ServerRequestInterface
    {
        return $this->getContainer()->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('POST', '/foo')
            ->withParsedBody($data)
            ->withUploadedFiles($files);
    }
}

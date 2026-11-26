<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\Acceptance;

use Spiral\Validation\Symfony\Tests\Acceptance\Support\ServesApp;
use Testo\Assert;
use Testo\Test;

/**
 * Files uploaded over HTTP to `/post`, a filter that reads the file with the `#[File]` input attribute
 * and validates it with the `Image` constraint.
 */
#[Test]
final class FileUploadTest
{
    use ServesApp;

    private const FIELDS = ['title' => 'New post', 'slug' => 'new-post', 'sort' => '1'];

    public function testImageIsAccepted(): void
    {
        $image = self::image();

        $response = self::server()->post('/post', self::FIELDS, [$image]);

        Assert::same(self::assertAccepted($response), [
            'title' => 'New post',
            'slug' => 'new-post',
            'sort' => 1,
            'image' => ['name' => 'photo.jpg', 'size' => \strlen($image['content']), 'mimeType' => 'image/jpeg'],
        ]);
    }

    public function testFileThatIsNotImageIsRejected(): void
    {
        $response = self::server()->post('/post', self::FIELDS, [self::textFile('image')]);

        Assert::same(self::assertRejected($response), ['image']);
    }

    public function testFileFieldWithoutChosenFileIsRejected(): void
    {
        $noFile = ['field' => 'image', 'name' => '', 'type' => 'application/octet-stream', 'content' => ''];

        $response = self::server()->post('/post', self::FIELDS, [$noFile]);

        Assert::same(self::assertRejected($response), ['image']);
    }

    public function testInvalidFieldsAreRejectedAlongWithValidImage(): void
    {
        $response = self::server()->post(
            '/post',
            ['title' => 'Post', 'slug' => 'new-post', 'sort' => '-1'],
            [self::image()],
        );

        Assert::array(self::assertRejected($response))->sameElementsAs(['title', 'sort']);
    }

    public function testNestedUploadInSameRequestDoesNotBreakValidation(): void
    {
        $response = self::server()->post('/post', self::FIELDS, [self::image(), self::textFile('attachments[]')]);

        self::assertAccepted($response);
    }
}

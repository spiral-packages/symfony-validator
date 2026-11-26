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

    public function testNestedFilterReadsFilesAndOtherUploadsStayReadable(): void
    {
        $image = self::image();

        $response = self::server()->post('/profile', [], [$image, self::image('child[avatar]'), self::textFile('notes')]);

        $description = ['name' => 'photo.jpg', 'size' => \strlen($image['content']), 'mimeType' => 'image/jpeg'];
        Assert::same(self::assertAccepted($response), [
            'image' => $description,
            'avatar' => $description,
            'notes' => 'Not an image.',
        ]);
    }

    public function testNestedFilterRejectsInvalidFile(): void
    {
        $response = self::server()->post('/profile', [], [self::image(), self::textFile('child[avatar]')]);

        Assert::same(self::assertRejected($response), ['child']);
    }

    public function testMultipleUploadIsAccepted(): void
    {
        $response = self::server()->post('/attachments', [], [self::textFile('attachments[]'), self::textFile('attachments[]')]);

        $description = ['name' => 'notes.txt', 'size' => 13, 'mimeType' => 'text/plain'];
        Assert::same(self::assertAccepted($response), [$description, $description]);
    }

    public function testMultipleUploadWithEmptyFieldIsRejected(): void
    {
        $noFile = ['field' => 'attachments[]', 'name' => '', 'type' => 'application/octet-stream', 'content' => ''];

        $response = self::server()->post('/attachments', [], [self::textFile('attachments[]'), $noFile]);

        Assert::same(self::assertRejected($response), ['attachments[1]']);
    }
}

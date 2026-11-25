<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\Acceptance;

use Spiral\Validation\Symfony\Tests\Acceptance\Support\ServesApp;
use Testo\Assert;
use Testo\Skip;
use Testo\Test;

/**
 * Form fields submitted over HTTP to filters validated by Symfony constraints:
 * `/simple` declares the constraints with attributes, `/array-mapping` with a filter definition.
 */
#[Test]
final class FormValidationTest
{
    use ServesApp;

    public function testValidUrlencodedFormIsAccepted(): void
    {
        $response = self::server()->postForm('/simple', ['username' => 'john', 'email' => 'john@example.com']);

        Assert::same(self::assertAccepted($response), ['username' => 'john', 'email' => 'john@example.com']);
    }

    public function testValidMultipartFormIsAccepted(): void
    {
        $response = self::server()->post('/simple', ['username' => 'john', 'email' => 'john@example.com']);

        Assert::same(self::assertAccepted($response), ['username' => 'john', 'email' => 'john@example.com']);
    }

    public function testInvalidFieldsAreRejected(): void
    {
        $response = self::server()->postForm('/simple', ['username' => '', 'email' => 'john@']);

        Assert::array(self::assertRejected($response))->sameElementsAs(['username', 'email']);
    }

    public function testMissingFieldIsRejected(): void
    {
        $response = self::server()->postForm('/simple', ['email' => 'john@example.com']);

        Assert::same(self::assertRejected($response), ['username']);
    }

    public function testEmailIsValidatedInHtml5Mode(): void
    {
        $response = self::server()->postForm('/simple', ['username' => 'john', 'email' => 'john doe@example.com']);

        Assert::same(self::assertRejected($response), ['email']);
    }

    public function testValidFormWithDefinitionIsAccepted(): void
    {
        $response = self::server()->post(
            '/array-mapping',
            ['username' => 'john', 'email' => 'john@example.com'],
            [self::image()],
        );

        $data = self::assertAccepted($response);

        Assert::same($data['username'] ?? null, 'john');
        Assert::same($data['email'] ?? null, 'john@example.com');
        Assert::same($data['image']['name'] ?? null, 'photo.jpg');
    }

    public function testInvalidFormWithDefinitionIsRejected(): void
    {
        $response = self::server()->post(
            '/array-mapping',
            ['username' => 'john', 'email' => 'john@'],
            [self::textFile('image')],
        );

        Assert::count(self::assertRejected($response), 2);
    }

    #[Skip('Bug: errors of a filter definition are keyed by the property path, e.g. "[email]" instead of "email"')]
    public function testErrorsOfFormWithDefinitionAreKeyedByFieldName(): void
    {
        $response = self::server()->post(
            '/array-mapping',
            ['username' => 'john', 'email' => 'john@'],
            [self::textFile('image')],
        );

        Assert::array(self::assertRejected($response))->sameElementsAs(['email', 'image']);
    }
}

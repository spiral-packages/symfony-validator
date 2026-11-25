<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\Acceptance\Support;

use Testo\Assert;
use Testo\Lifecycle\AfterClass;
use Testo\Lifecycle\BeforeClass;

/**
 * Runs one application server for all tests of the case.
 */
trait ServesApp
{
    private static ?AppServer $server = null;

    #[BeforeClass]
    public static function startServer(): void
    {
        self::$server = AppServer::start();
    }

    #[AfterClass]
    public static function stopServer(): void
    {
        self::$server?->stop();
        self::$server = null;
    }

    private static function server(): AppServer
    {
        return self::$server ?? throw new \LogicException('The server is not started.');
    }

    /**
     * @return array<string, mixed> The decoded body of the response.
     */
    private static function assertAccepted(HttpResponse $response): array
    {
        Assert::same($response->status, 200, self::describe($response));

        return $response->json();
    }

    /**
     * Asserts a validation failure and returns the names of the invalid fields.
     *
     * @return list<array-key>
     */
    private static function assertRejected(HttpResponse $response): array
    {
        Assert::same($response->status, 422, self::describe($response));
        $errors = $response->json()['errors'] ?? null;
        Assert::array($errors)->notEmpty();

        return \array_keys($errors);
    }

    /**
     * The response with the PHP errors and warnings from the server log: a fatal error leaves the body empty.
     */
    private static function describe(HttpResponse $response): string
    {
        $errors = \preg_grep('/\bPHP (Fatal error|Parse error|Warning):/', \explode("\n", self::server()->serverLog())) ?: [];

        return \implode("\n", [(string) $response, ...$errors]);
    }

    /**
     * @return array{field: string, name: string, type: string, content: string}
     */
    private static function image(string $field = 'image'): array
    {
        return [
            'field' => $field,
            'name' => 'photo.jpg',
            'type' => 'image/jpeg',
            'content' => (string) \file_get_contents(\dirname(__DIR__, 3) . '/app/fixtures/sample-1.jpg'),
        ];
    }

    /**
     * @return array{field: string, name: string, type: string, content: string}
     */
    private static function textFile(string $field): array
    {
        return ['field' => $field, 'name' => 'notes.txt', 'type' => 'text/plain', 'content' => 'Not an image.'];
    }
}

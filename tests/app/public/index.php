<?php

/**
 * Front controller of the acceptance application, served by `php -S`.
 *
 * Answers JSON: the controller result with 200, `{"errors": ...}` with 422 when a filter fails validation,
 * `{"exception": ..., "message": ...}` with 500 on any other failure.
 * The runtime directory comes from the `ACCEPTANCE_RUNTIME` environment variable.
 */

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Scope;
use Spiral\Filters\Exception\ValidationException;
use Spiral\Framework\Spiral;
use Spiral\Http\Http;
use Spiral\Validation\Symfony\Tests\App\Acceptance\HttpKernel;

$root = \dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';

$emit = static function (int $status, mixed $payload): void {
    \http_response_code($status);
    \header('Content-Type: application/json');
    echo \json_encode($payload, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
};

try {
    $runtime = (string) \getenv('ACCEPTANCE_RUNTIME') ?: throw new \RuntimeException('ACCEPTANCE_RUNTIME is not set.');
    $app = HttpKernel::create(
        directories: [
            'root' => $root,
            'app' => $root . '/tests/app',
            'runtime' => $runtime,
            'cache' => $runtime . '/cache',
        ],
        handleErrors: false,
    )->run() ?? throw new \RuntimeException('The application failed to boot.');

    $factory = new Psr17Factory();
    $request = (new ServerRequestCreator($factory, $factory, $factory, $factory))->fromGlobals();

    $response = $app->getContainer()->runScope(
        new Scope(Spiral::Http),
        static fn(Http $http): ResponseInterface => $http->handle($request),
    );

    \http_response_code($response->getStatusCode());
    foreach ($response->getHeaders() as $name => $values) {
        \header($name . ': ' . \implode(', ', $values));
    }
    echo $response->getBody();
} catch (ValidationException $e) {
    $emit(422, ['errors' => $e->errors]);
} catch (\Throwable $e) {
    $emit(500, ['exception' => $e::class, 'message' => $e->getMessage()]);
}

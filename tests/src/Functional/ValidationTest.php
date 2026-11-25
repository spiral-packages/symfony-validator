<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\Functional;

use Nyholm\Psr7\UploadedFile;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spiral\Core\Scope;
use Spiral\Filters\Exception\ValidationException;
use Spiral\Framework\Spiral;
use Spiral\Validation\Symfony\Tests\App\Filters\CreatePostFilter;
use Spiral\Validation\Symfony\Tests\App\Filters\FilterWithArrayMapping;
use Spiral\Validation\Symfony\Tests\App\Filters\SimpleFilter;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

#[Test]
final class ValidationTest extends TestCase
{
    public static function requestsSuccessProvider(): \Traversable
    {
        yield [SimpleFilter::class, ['username' => 'foo', 'email' => 'foo@gmail.com']];
        yield [FilterWithArrayMapping::class, ['username' => 'foo', 'email' => 'foo@gmail.com'], true];
        yield [CreatePostFilter::class, ['title' => 'New post', 'slug' => 'new-post', 'sort' => 1], true];
    }

    public static function requestsErrorProvider(): \Traversable
    {
        yield [SimpleFilter::class, ['email' => 'foo@gmail.com']];
        yield [SimpleFilter::class, ['username' => 'foo']];
        yield [SimpleFilter::class, ['username' => 'foo', 'email' => 'foo']];

        yield [FilterWithArrayMapping::class, ['email' => 'foo@gmail.com']];
        yield [FilterWithArrayMapping::class, ['username' => 'foo']];
        yield [FilterWithArrayMapping::class, ['username' => 'foo', 'email' => 'foo']];

        yield [CreatePostFilter::class, ['title' => 'New post', 'slug' => 'new-post', 'sort' => -1]];
        yield [CreatePostFilter::class, ['title' => 'New post', 'slug' => 'new-post']];
        yield [CreatePostFilter::class, ['title' => 'foo', 'slug' => 'foo', 'sort' => 1]];
    }

    #[DataProvider('requestsSuccessProvider')]
    public function testValidationSuccess(string $filterClass, array $data, bool $withFile = false): void
    {
        $filter = $this->createFilter($filterClass, $this->createRequest($data, $withFile))->getData();

        if ($withFile) {
            Assert::instanceOf($filter['image'], SymfonyUploadedFile::class);
            unset($filter['image']);
        }

        Assert::same($filter, $data);
    }

    #[DataProvider('requestsErrorProvider')]
    public function testValidationError(string $filterClass, array $data, bool $withFile = false): void
    {
        $request = $this->createRequest($data, $withFile);
        Expect::exception(ValidationException::class);

        $this->createFilter($filterClass, $request);
    }

    /**
     * Resolves the filter inside the request scope: the filter input is bound only there.
     */
    private function createFilter(string $filterClass, ServerRequestInterface $request): object
    {
        return $this->getContainer()->runScope(
            new Scope(Spiral::HttpRequest, [ServerRequestInterface::class => $request]),
            static fn(ContainerInterface $container): object => $container->get($filterClass),
        );
    }

    private function createRequest(array $data, bool $withFile = false): ServerRequestInterface
    {
        $factory = $this->getContainer()->get(ServerRequestFactoryInterface::class);

        $request = $factory->createServerRequest('POST', '/foo')->withParsedBody($data);

        if ($withFile) {
            $path = \dirname(__DIR__, 2) . '/app/fixtures/sample-1.jpg';

            $request = $request->withUploadedFiles([
                'image' => new UploadedFile(
                    fopen($path, 'rb'),
                    filesize($path),
                    0,
                    $path,
                ),
            ]);
        }

        return $request;
    }
}

<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\Functional;

use Spiral\Filters\Exception\ValidationException;
use Spiral\Filters\InputInterface;
use Spiral\Validation\Symfony\Tests\App\Filters\CreatePostFilter;
use Spiral\Validation\Symfony\Tests\App\Filters\FilterWithArrayMapping;
use Spiral\Validation\Symfony\Tests\App\Filters\SimpleFilter;

final class ValidationTest extends TestCase
{
    /** @dataProvider requestsSuccessProvider */
    public function testValidationSuccess(string $filterClass, array $data): void
    {
        $this->getContainer()->bind(InputInterface::class, $this->initInputScope($data));

        $filter = $this->getContainer()->get($filterClass);

        $this->assertSame($data, $filter->getData());
    }

    /** @dataProvider requestsErrorProvider */
    public function testValidationError(string $filterClass, array $data): void
    {
        $this->getContainer()->bind(InputInterface::class, $this->initInputScope($data));

        $this->expectException(ValidationException::class);
        $this->getContainer()->get($filterClass);
    }

    public function requestsSuccessProvider(): \Traversable
    {
        yield [SimpleFilter::class, ['username' => 'foo', 'email' => 'foo@gmail.com']];
        yield [FilterWithArrayMapping::class, ['username' => 'foo', 'email' => 'foo@gmail.com']];
        yield [CreatePostFilter::class, ['title' => 'New post', 'slug' => 'new-post', 'sort' => 1]];
    }

    public function requestsErrorProvider(): \Traversable
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

    private function initInputScope(array $data): InputInterface
    {
        return new class($data) implements InputInterface {

            public function __construct(
                private array $data
            ) {
            }

            public function withPrefix(string $prefix, bool $add = true): InputInterface
            {
                return $this;
            }

            public function getValue(string $source, string $name = null): mixed
            {
                return $this->data[$name] ?? null;
            }

            public function hasValue(string $source, string $name): bool
            {
                return isset($this->data);
            }
        };
    }
}

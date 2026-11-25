<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\Functional;

use Nyholm\Psr7\UploadedFile;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spiral\Core\Attribute;
use Spiral\Core\Scope;
use Spiral\Filters\Exception\ValidationException;
use Spiral\Filters\Model\FilterBag;
use Spiral\Filters\Model\FilterInterface;
use Spiral\Framework\Spiral;
use Spiral\Http\Request\InputManager;
use Spiral\Models\DataEntity;
use Spiral\Validation\Symfony\Tests\App\Filters\CreatePostFilter;
use Spiral\Validation\Symfony\Tests\App\Filters\FilterWithArrayMapping;
use Spiral\Validation\Symfony\Tests\App\Filters\SimpleFilter;
use Spiral\Validation\Symfony\Validation;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Symfony\Component\Validator\Constraints;
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

    public function testFilterBagWithAttributesFilterValidatesTheFilterObject(): void
    {
        $filter = new SimpleFilter();
        $filter->username = 'foo';
        $filter->email = 'foo';

        $validator = $this->getValidation()->validate(new FilterBag($filter, new DataEntity()), []);

        Assert::same($validator->getErrors(), ['email' => ['This value is not a valid email address.']]);
    }

    public function testFilterBagWithFilterValidatesFilterData(): void
    {
        $filter = new FilterWithArrayMapping();
        $filter->setData(['email' => 'foo@gmail.com']);

        $validator = $this->getValidation()->validate(
            new FilterBag($filter, new DataEntity(['email' => 'foo'])),
            ['email' => [new Constraints\Email()]],
        );

        Assert::true($validator->isValid());
        Assert::same($validator->getValue('email'), 'foo@gmail.com');
    }

    public function testFilterBagWithCustomFilterValidatesEntityData(): void
    {
        $filter = new class implements FilterInterface {};

        $validator = $this->getValidation()->validate(
            new FilterBag($filter, new DataEntity(['email' => 'foo'])),
            ['email' => [new Constraints\Email()]],
        );

        Assert::false($validator->isValid());
        Assert::same($validator->getValue('email'), 'foo');
    }

    private function getValidation(): Validation
    {
        return $this->getContainer()->get(Validation::class);
    }

    /**
     * Resolves the filter inside the request scope: the filter input is bound only there.
     * Before spiral/framework 3.15 InputManager is not scoped and reads the request from a plain scope binding.
     */
    private function createFilter(string $filterClass, ServerRequestInterface $request): object
    {
        $bindings = [ServerRequestInterface::class => $request];
        $scoped = (new \ReflectionClass(InputManager::class))->getAttributes(Attribute\Scope::class) !== [];

        return $this->getContainer()->runScope(
            $scoped ? new Scope(Spiral::HttpRequest, $bindings) : $bindings,
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

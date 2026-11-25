<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\Functional;

use Spiral\Core\Container;
use Spiral\Validation\Symfony\Tests\App\TestKernel;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;

/**
 * Boots a fresh application before each test.
 */
abstract class TestCase
{
    private ?TestKernel $app = null;

    #[BeforeTest]
    protected function bootApplication(): void
    {
        $root = \dirname(__DIR__, 2);

        $this->app = TestKernel::create(
            directories: [
                'root' => $root,
                'app' => $root . '/app',
                'runtime' => $root . '/runtime',
                'cache' => $root . '/runtime/cache',
            ],
            handleErrors: false,
        );
        $this->app->run();
    }

    protected function getContainer(): Container
    {
        return ($this->app ?? throw new \LogicException('The application is not booted.'))->getContainer();
    }

    /**
     * @param class-string $alias
     * @param class-string $class
     */
    protected function assertContainerBoundAsSingleton(string $alias, string $class): void
    {
        $container = $this->getContainer();

        Assert::true($container->has($alias), \sprintf('Container [%s] is not bound.', $alias));
        $instance = $container->get($alias);
        Assert::instanceOf($instance, $class);
        Assert::same($container->get($alias), $instance, \sprintf('Container [%s] is not a singleton.', $alias));
    }
}

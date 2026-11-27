<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\Unit\Bootloader;

use Spiral\Bootloader\Http\HttpBootloader;
use Spiral\Config\ConfigManager;
use Spiral\Config\LoaderInterface;
use Spiral\Core\Container;
use Spiral\Http\Config\HttpConfig;
use Spiral\Validation\Bootloader\ValidationBootloader;
use Spiral\Validation\Config\ValidationConfig;
use Spiral\Validation\Symfony\Bootloader\ValidatorBootloader;
use Spiral\Validation\Symfony\FilterDefinition;
use Spiral\Validation\Symfony\Http\Request\FilesBag;
use Spiral\Validation\Symfony\Validation;
use Spiral\Validation\ValidationProvider;
use Symfony\Component\Validator\Validation as SymfonyValidation;
use Testo\Assert;
use Testo\Test;

/**
 * Runs the bootloader without an application.
 */
#[Test]
final class ValidatorBootloaderTest
{
    public function testInitRegistersSymfonyFilesInputBag(): void
    {
        $configs = self::configs();
        $configs->setDefaults(HttpConfig::CONFIG, ['inputBags' => []]);

        (new ValidatorBootloader())->init(new HttpBootloader($configs, new Container()));

        Assert::same(
            $configs->getConfig(HttpConfig::CONFIG)['inputBags'],
            ['symfonyFiles' => ['class' => FilesBag::class, 'source' => 'getUploadedFiles', 'alias' => 'symfony-file']],
        );
    }

    public function testBootMakesFilterDefinitionTheDefaultValidation(): void
    {
        $configs = self::configs();
        $validationBootloader = new ValidationBootloader($configs);
        $validationBootloader->init();
        $container = new Container();
        $validation = new Validation(SymfonyValidation::createValidator());
        $container->bindSingleton(Validation::class, $validation);
        $provider = new ValidationProvider($container);

        (new ValidatorBootloader())->boot($provider, $validationBootloader);

        Assert::same($configs->getConfig(ValidationConfig::CONFIG)['defaultValidator'], FilterDefinition::class);
        Assert::same($provider->getValidation(FilterDefinition::class), $validation);
    }

    private static function configs(): ConfigManager
    {
        return new ConfigManager(new class implements LoaderInterface {
            public function has(string $section): bool
            {
                return false;
            }

            public function load(string $section): array
            {
                throw new \LogicException(\sprintf('No "%s" config to load.', $section));
            }
        });
    }
}

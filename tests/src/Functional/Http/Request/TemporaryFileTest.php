<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\Functional\Http\Request;

use Spiral\Validation\Symfony\Http\Request\TemporaryFile;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class TemporaryFileTest
{
    public function testCannotBeCloned(): void
    {
        $file = new TemporaryFile();
        Expect::exception(\BadMethodCallException::class);

        clone $file;
    }

    public function testCannotBeSerialized(): void
    {
        $file = new TemporaryFile();
        Expect::exception(\BadMethodCallException::class);

        \serialize($file);
    }

    public function testUnserializedPayloadDoesNotRemoveTheFile(): void
    {
        $victim = (string) \tempnam(\sys_get_temp_dir(), 'victim');
        $property = "\0" . TemporaryFile::class . "\0path";
        $payload = \sprintf(
            'O:%d:"%s":1:{s:%d:"%s";s:%d:"%s";}',
            \strlen(TemporaryFile::class),
            TemporaryFile::class,
            \strlen($property),
            $property,
            \strlen($victim),
            $victim,
        );

        try {
            \unserialize($payload);
            Assert::fail('Unserialization must fail.');
        } catch (\BadMethodCallException) {
        }

        Assert::true(\is_file($victim));
        \unlink($victim);
    }
}

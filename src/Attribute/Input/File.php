<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Attribute\Input;

use Psr\Http\Message\UploadedFileInterface;
use Spiral\Attributes\NamedArgumentConstructor;
use Spiral\Filters\Attribute\Input\Input;
use Spiral\Filters\InputInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\UploadedFile as SymfonyUploadedFile;

#[\Attribute(\Attribute::TARGET_PROPERTY), NamedArgumentConstructor]
final class File extends Input
{
    /**
     * @param non-empty-string|null $key
     */
    public function __construct(
        public readonly ?string $key = null,
    ) {
    }

    public function getValue(InputInterface $input, \ReflectionProperty $property): ?SymfonyUploadedFile
    {
        /** @var UploadedFileInterface $file */
        $file = $input->getValue('file', $this->getKey($property));

        return new SymfonyUploadedFile(
            $file, static fn(): string => sys_get_temp_dir());
    }

    public function getSchema(\ReflectionProperty $property): string
    {
        return 'file:' . $this->getKey($property);
    }
}

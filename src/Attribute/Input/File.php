<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Attribute\Input;

use Spiral\Attributes\NamedArgumentConstructor;
use Spiral\Filters\Attribute\Input\AbstractInput;
use Spiral\Filters\InputInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\UploadedFile;

#[\Attribute(\Attribute::TARGET_PROPERTY), NamedArgumentConstructor]
final class File extends AbstractInput
{
    /**
     * @param non-empty-string|null $key
     */
    public function __construct(
        public readonly ?string $key = null,
    ) {
    }

    public function getValue(InputInterface $input, \ReflectionProperty $property): ?UploadedFile
    {
        return $input->getValue('symfony-file', $this->getKey($property));
    }

    public function getSchema(\ReflectionProperty $property): string
    {
        return 'symfony-file:' . $this->getKey($property);
    }
}

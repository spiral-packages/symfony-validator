<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Http\Request;

use Psr\Http\Message\UploadedFileInterface;
use Spiral\Http\Request\InputBag;
use Spiral\Streams\StreamWrapper;
use Symfony\Bridge\PsrHttpMessage\Factory\UploadedFile;

final class FilesBag extends InputBag
{
    private array $files = [];

    public function get(string $name, mixed $default = null): ?UploadedFile
    {
        if (isset($this->files[$name])) {
            return $this->files[$name];
        }

        if (!parent::get($name, $default) instanceof UploadedFileInterface) {
           return null;
        }

        $this->files[$name] = new UploadedFile(parent::get($name, $default), fn(): string => $this->getTemporaryPath());

        return $this->files[$name];
    }

    /**
     * @return UploadedFile[]
     */
    public function all(): array
    {
        foreach (parent::all() as $name => $file) {
            if (!isset($this->files[$name])) {
                $this->files[$name] = new UploadedFile($file, fn(): string => $this->getTemporaryPath());
            }
        }

        return $this->files;
    }

    public function fetch(array $keys, bool $fill = false, mixed $filler = null): array
    {
        $result = [];
        foreach (parent::fetch($keys, $fill, $filler) as $name => $file) {
            if (!isset($this->files[$name])) {
                $this->files[$name] = new UploadedFile($file, fn(): string => $this->getTemporaryPath());
            }
            $result[$name] = $this->files[$name];
        }

        return $result;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->all());
    }

    /**
     * Locale local filename (virtual filename) associated with UploadedFile resource.
     */
    public function getFilename(string $name): ?string
    {
        if (!empty($file = parent::get($name)) && !$file->getError()) {
            return StreamWrapper::getFilename($file->getStream());
        }

        return null;
    }

    /**
     * Gets a temporary file path.
     */
    protected function getTemporaryPath(): string
    {
        return tempnam(sys_get_temp_dir(), uniqid('symfony', true));
    }
}

<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Http\Request;

use Spiral\Http\Request\InputBag;
use Symfony\Bridge\PsrHttpMessage\Factory\UploadedFile;

final class FilesBag extends InputBag
{
    public function __construct(array $data, string $prefix = '')
    {
        foreach ($data as $name => $file) {
            $data[$name] = new UploadedFile($file, fn(): string => $this->getTemporaryPath());
        }

        parent::__construct($data, $prefix);
    }

    /**
     * Gets a temporary file path.
     */
    protected function getTemporaryPath(): string
    {
        return \tempnam(\sys_get_temp_dir(), \uniqid('symfony', true));
    }
}

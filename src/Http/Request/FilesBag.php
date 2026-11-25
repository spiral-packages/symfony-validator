<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Http\Request;

use Spiral\Http\Request\InputBag;
use Symfony\Bridge\PsrHttpMessage\Factory\UploadedFile;

final class FilesBag extends InputBag
{
    /**
     * @psalm-suppress MixedAssignment, MixedArgument Bug: a nested tree from getUploadedFiles() (e.g. `files[]`)
     *     reaches UploadedFile as an array and fails with a TypeError; only a flat list of files is supported.
     */
    public function __construct(array $data, string $prefix = '')
    {
        foreach ($data as $name => $file) {
            $data[$name] = new UploadedFile($file, fn(): string => $this->getTemporaryPath());
        }

        parent::__construct($data, $prefix);
    }

    /**
     * Gets a temporary file path.
     *
     * @psalm-suppress FalsableReturnStatement, InvalidFalsableReturnType tempnam() returns false only when the
     *     system temp directory is unusable; the resulting TypeError is the existing failure mode.
     */
    protected function getTemporaryPath(): string
    {
        return \tempnam(\sys_get_temp_dir(), \uniqid('symfony', true));
    }
}

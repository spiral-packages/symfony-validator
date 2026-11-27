<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Http\Request;

/**
 * Owns a temporary file and removes it when the last holder releases it.
 *
 * Shared by an {@see UploadedFile} and its clones, so the file lives as long as any of them.
 *
 * @internal
 */
final class TemporaryFile
{
    private ?string $path = null;

    /**
     * @psalm-suppress FalsableReturnStatement, InvalidFalsableReturnType, PossiblyFalsePropertyAssignmentValue tempnam() returns false only when the
     *     system temp directory is unusable; the resulting TypeError is the existing failure mode.
     */
    public function create(): string
    {
        return $this->path = \tempnam(\sys_get_temp_dir(), \uniqid('symfony', true));
    }

    /**
     * Gives up the file, e.g. after it was moved: tempnam() may hand out the freed name again.
     */
    public function release(): void
    {
        $this->path = null;
    }

    public function remove(): void
    {
        $this->path === null or !\is_file($this->path) or \unlink($this->path);
        $this->path = null;
    }

    public function __serialize(): array
    {
        throw new \BadMethodCallException(\sprintf('Cannot serialize %s.', self::class));
    }

    /**
     * Forbidden: a crafted payload would delete an arbitrary file on destruction.
     */
    public function __unserialize(array $data): void
    {
        throw new \BadMethodCallException(\sprintf('Cannot unserialize %s.', self::class));
    }

    public function __destruct()
    {
        $this->remove();
    }
}

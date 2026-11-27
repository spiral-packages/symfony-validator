<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\Functional\Http\Request\Stub;

/**
 * A `limited://` stream wrapper whose files accept only as many bytes as the host part allows, like a full disk.
 *
 * `limited://3/file` takes the first 3 bytes written to it and refuses the rest.
 */
final class LimitedStorage
{
    public const PROTOCOL = 'limited';

    /** @var resource|null Set by PHP for the wrapper instances it creates. */
    public $context;

    private int $free = 0;

    public static function register(): void
    {
        \in_array(self::PROTOCOL, \stream_get_wrappers(), true)
            or \stream_wrapper_register(self::PROTOCOL, self::class);
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        $this->free = (int) \parse_url($path, \PHP_URL_HOST);

        return true;
    }

    public function stream_write(string $data): int
    {
        $written = \min(\strlen($data), $this->free);
        $this->free -= $written;

        return $written;
    }
}

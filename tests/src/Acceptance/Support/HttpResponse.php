<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\Acceptance\Support;

final class HttpResponse
{
    private function __construct(
        public readonly int $status,
        public readonly string $body,
    ) {}

    public static function parse(string $raw): self
    {
        [$head, $body] = \explode("\r\n\r\n", $raw, 2) + [1 => ''];
        $statusLine = \strstr($head, "\r\n", true) ?: $head;

        return new self((int) (\explode(' ', $statusLine, 3)[1] ?? 0), $body);
    }

    /**
     * The decoded JSON object; fails with the status and the raw body when the body is anything else.
     */
    public function json(): array
    {
        $data = \json_decode($this->body, true);

        return \is_array($data) ? $data : throw new \RuntimeException('Not a JSON object: ' . $this);
    }

    public function __toString(): string
    {
        return \sprintf("HTTP %d\n%s", $this->status, $this->body);
    }
}

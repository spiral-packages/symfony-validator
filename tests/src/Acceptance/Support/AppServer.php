<?php

declare(strict_types=1);

namespace Spiral\Validation\Symfony\Tests\Acceptance\Support;

/**
 * Serves `tests/app/public/index.php` with the PHP built-in web server and talks to it over plain HTTP.
 *
 * The server runs with a minimal environment; the application runtime, uploaded files and the server log
 * (PHP errors included) go to a temporary directory that {@see stop()} removes.
 */
final class AppServer
{
    private const START_TIMEOUT_SECONDS = 10;

    /**
     * @param resource $process
     */
    private function __construct(
        private readonly mixed $process,
        private readonly int $port,
        private readonly string $tempDir,
    ) {}

    public static function start(): self
    {
        $tempDir = \sys_get_temp_dir() . \DIRECTORY_SEPARATOR . \uniqid('symfony-validator-acceptance-', true);
        \mkdir($tempDir . '/runtime', 0777, true);

        $port = self::freePort();
        $docRoot = \dirname(__DIR__, 3) . '/app/public';
        $log = $tempDir . '/server.log';
        # PHP errors go to the log: a deprecation printed into the body (old dependencies on new PHP) breaks the JSON.
        $process = \proc_open(
            [
                \PHP_BINARY,
                '-d', 'display_errors=0',
                '-d', 'log_errors=1',
                '-S', '127.0.0.1:' . $port,
                '-t', $docRoot,
                $docRoot . '/index.php',
            ],
            [['pipe', 'r'], ['file', $log, 'a'], ['file', $log, 'a']],
            $pipes,
            $docRoot,
            self::environment($tempDir),
        );
        \is_resource($process) or throw new \RuntimeException('Unable to start the PHP built-in web server.');

        $server = new self($process, $port, $tempDir);
        $server->waitUntilListening();

        return $server;
    }

    public function stop(): void
    {
        \proc_terminate($this->process);
        \proc_close($this->process);
        self::remove($this->tempDir);
    }

    /**
     * Sends a `multipart/form-data` POST request, the way a browser submits a form.
     *
     * @param array<string, string> $fields
     * @param list<array{field: string, name: string, type: string, content: string}> $files A field like
     *        `files[]` nests the upload in `$_FILES`; an empty name submits the field with no file chosen.
     */
    public function post(string $path, array $fields = [], array $files = []): HttpResponse
    {
        $boundary = \bin2hex(\random_bytes(16));
        $body = '';
        foreach ($fields as $name => $value) {
            $body .= "--{$boundary}\r\nContent-Disposition: form-data; name=\"{$name}\"\r\n\r\n{$value}\r\n";
        }
        foreach ($files as $file) {
            $body .= "--{$boundary}\r\n"
                . "Content-Disposition: form-data; name=\"{$file['field']}\"; filename=\"{$file['name']}\"\r\n"
                . "Content-Type: {$file['type']}\r\n\r\n{$file['content']}\r\n";
        }
        $body .= "--{$boundary}--\r\n";

        return $this->request($path, 'multipart/form-data; boundary=' . $boundary, $body);
    }

    /**
     * Sends an `application/x-www-form-urlencoded` POST request.
     *
     * @param array<string, string> $fields
     */
    public function postForm(string $path, array $fields): HttpResponse
    {
        return $this->request($path, 'application/x-www-form-urlencoded', \http_build_query($fields));
    }

    public function serverLog(): string
    {
        return (string) @\file_get_contents($this->tempDir . '/server.log');
    }

    private static function freePort(): int
    {
        $socket = \stream_socket_server('tcp://127.0.0.1:0');
        $socket === false and throw new \RuntimeException('Unable to find a free port.');
        $address = (string) \stream_socket_get_name($socket, false);
        \fclose($socket);

        return (int) \substr($address, (int) \strrpos($address, ':') + 1);
    }

    /**
     * Nothing of the test process environment leaks into the application; Windows needs `SystemRoot` for sockets.
     *
     * @return array<string, string>
     */
    private static function environment(string $tempDir): array
    {
        $env = [
            'ACCEPTANCE_RUNTIME' => $tempDir . '/runtime',
            'TMPDIR' => $tempDir,
            'TMP' => $tempDir,
            'TEMP' => $tempDir,
        ];
        foreach (['PATH', 'SystemRoot'] as $name) {
            $value = \getenv($name);
            $value === false or $env[$name] = $value;
        }

        return $env;
    }

    private static function remove(string $path): void
    {
        if (\is_dir($path) && !\is_link($path)) {
            foreach (\scandir($path) ?: [] as $item) {
                $item === '.' || $item === '..' or self::remove($path . '/' . $item);
            }
            @\rmdir($path);
            return;
        }

        @\unlink($path);
    }

    private function request(string $path, string $contentType, string $body): HttpResponse
    {
        $socket = \stream_socket_client('tcp://127.0.0.1:' . $this->port, $errno, $error, 5);
        $socket === false and throw new \RuntimeException(\sprintf('Unable to connect to the server: %s.', $error));

        # HTTP/1.0: the server answers unchunked and closes the connection, so the response is read to the end.
        \fwrite($socket, \implode("\r\n", [
            "POST {$path} HTTP/1.0",
            'Host: 127.0.0.1:' . $this->port,
            'Content-Type: ' . $contentType,
            'Content-Length: ' . \strlen($body),
            '',
            $body,
        ]));
        $raw = (string) \stream_get_contents($socket);
        \fclose($socket);

        return HttpResponse::parse($raw);
    }

    private function waitUntilListening(): void
    {
        $deadline = \microtime(true) + self::START_TIMEOUT_SECONDS;
        while (\microtime(true) < $deadline) {
            $socket = @\stream_socket_client('tcp://127.0.0.1:' . $this->port, $errno, $error, 1);
            if ($socket !== false) {
                \fclose($socket);
                return;
            }

            \usleep(50_000);
        }

        $log = $this->serverLog();
        $this->stop();
        throw new \RuntimeException("The PHP built-in web server did not start:\n" . $log);
    }
}

<?php
declare(strict_types=1);

namespace CardPay\Core;

final class Request
{
    /** @var array<string,mixed>|null */
    private ?array $json = null;

    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly string $rawBody,
        /** @var array<string,mixed> */ public readonly array $query,
        /** @var array<string,mixed> */ public readonly array $post,
        /** @var array<string,mixed> */ public readonly array $files,
        /** @var array<string,string> */ public readonly array $headers,
        public readonly string $ip,
        public readonly string $rawQuery = '',
    ) {}

    public static function capture(): self
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = rawurldecode((string) parse_url($uri, PHP_URL_PATH));
        $scriptDir = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
        if (str_ends_with($scriptDir, '/public')) $scriptDir = substr($scriptDir, 0, -7);
        if ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir)) ?: '/';
        }
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = (string) $value;
            }
        }
        foreach (['CONTENT_TYPE' => 'content-type', 'CONTENT_LENGTH' => 'content-length'] as $server => $name) {
            if (isset($_SERVER[$server])) $headers[$name] = (string) $_SERVER[$server];
        }
        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            '/' . ltrim($path, '/'),
            file_get_contents('php://input') ?: '',
            $_GET,
            $_POST,
            $_FILES,
            $headers,
            (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),
            (string) (parse_url($uri, PHP_URL_QUERY) ?? ''),
        );
    }

    /** @return array<string,mixed> */
    public function json(): array
    {
        if ($this->json !== null) return $this->json;
        $decoded = json_decode($this->rawBody, true);
        return $this->json = is_array($decoded) ? $decoded : [];
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->json()[$key] ?? $default;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function isJson(): bool
    {
        return str_contains((string) $this->header('content-type', ''), 'application/json');
    }
}

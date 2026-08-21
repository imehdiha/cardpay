<?php
declare(strict_types=1);

namespace CardPay\Core;

final class Response
{
    /** @param array<string,string> $headers */
    public function __construct(
        public readonly string $body = '',
        public readonly int $status = 200,
        public readonly array $headers = [],
    ) {}

    public static function json(array $data, int $status = 200, array $headers = []): self
    {
        return new self(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), $status, ['Content-Type' => 'application/json; charset=utf-8'] + $headers);
    }

    public static function success(array $data = [], int $status = 200, array $headers = []): self
    {
        return self::json(['success' => true, 'data' => $data], $status, $headers);
    }

    public static function error(string $code, string $message, int $status = 400, array $details = []): self
    {
        return self::json(['success' => false, 'error' => ['code' => $code, 'message' => $message, 'details' => $details]], $status);
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return new self('', $status, ['Location' => $url]);
    }

    public function send(): never
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) header("{$name}: {$value}");
        echo $this->body;
        exit;
    }
}

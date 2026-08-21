<?php
declare(strict_types=1);

namespace CardPay\Core;

final class Logger
{
    private const SENSITIVE = ['password','secret','authorization','card_number','iban','db_password','token'];
    public function __construct(private readonly string $file) {}

    public function log(string $level, string $message, array $context = []): void
    {
        $line = json_encode(['time' => gmdate('c'), 'level' => $level, 'message' => $message, 'context' => $this->redact($context)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        @file_put_contents($this->file, $line, FILE_APPEND | LOCK_EX);
    }
    private function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE, true)) $data[$key] = '[REDACTED]';
            elseif (is_array($value)) $data[$key] = $this->redact($value);
        }
        return $data;
    }
}

<?php
declare(strict_types=1);

namespace CardPay\Core;

use RuntimeException;

final class Crypto
{
    private string $key;
    public function __construct(string $appKey)
    {
        if ($appKey === '') throw new RuntimeException('APP_KEY is not configured.');
        $decoded = str_starts_with($appKey, 'base64:') ? base64_decode(substr($appKey, 7), true) : false;
        $this->key = hash('sha256', $decoded !== false ? $decoded : $appKey, true);
    }
    public static function generateKey(): string { return 'base64:' . base64_encode(random_bytes(32)); }
    public function encrypt(string $plain): string
    {
        $iv = random_bytes(12); $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) throw new RuntimeException('Encryption failed.');
        return base64_encode($iv . $tag . $cipher);
    }
    public function decrypt(string $payload): string
    {
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) < 29) throw new RuntimeException('Invalid encrypted payload.');
        $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
        if ($plain === false) throw new RuntimeException('Decryption failed.');
        return $plain;
    }
}

<?php
declare(strict_types=1);

namespace CardPay\Core;

final class Session
{
    public function start(int $lifetime = 120): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        session_name('cardpay_session');
        session_set_cookie_params([
            'lifetime' => $lifetime * 60,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
    public function get(string $key, mixed $default = null): mixed { return $_SESSION[$key] ?? $default; }
    public function put(string $key, mixed $value): void { $_SESSION[$key] = $value; }
    public function forget(string $key): void { unset($_SESSION[$key]); }
    public function regenerate(): void { session_regenerate_id(true); }
    public function destroy(): void { $_SESSION = []; if (session_status() === PHP_SESSION_ACTIVE) session_destroy(); }
    public function flash(string $type, string $message): void { $_SESSION['_flash'] = compact('type', 'message'); }
    public function pullFlash(): ?array { $flash = $_SESSION['_flash'] ?? null; unset($_SESSION['_flash']); return $flash; }
}

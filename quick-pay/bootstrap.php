<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 7200,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self' https:; frame-ancestors 'none'; img-src 'self' data:; style-src 'self'; script-src 'self'");

/** @var array<string, mixed> $config */
$config = require __DIR__ . '/config.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['quick_pay_csrf'])) {
        $_SESSION['quick_pay_csrf'] = bin2hex(random_bytes(24));
    }

    return (string) $_SESSION['quick_pay_csrf'];
}

function csrf_is_valid(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['quick_pay_csrf'])
        && hash_equals((string) $_SESSION['quick_pay_csrf'], $token);
}

function flash_error(?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['quick_pay_error'] = $message;
        return null;
    }

    $current = $_SESSION['quick_pay_error'] ?? null;
    unset($_SESSION['quick_pay_error']);

    return is_string($current) ? $current : null;
}

function redirect_to(string $url): never
{
    header('Location: ' . $url, true, 303);
    exit;
}

function local_path(string $file = ''): string
{
    $folder = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php')));
    $folder = $folder === '/' ? '' : rtrim($folder, '/');
    $file = ltrim($file, '/');

    return $folder . '/' . $file;
}

function fa_number(int $amount): string
{
    return strtr(number_format($amount), [
        '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
        '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
        ',' => '٬',
    ]);
}

function toman(int $amount): string
{
    return fa_number($amount) . ' تومان';
}

function rial(int $amount): string
{
    return fa_number($amount) . ' ریال';
}

/** @param array<string, mixed> $settings */
function public_folder_url(array $settings): string
{
    $configured = rtrim((string) ($settings['public_url'] ?? ''), '/');
    if ($configured !== '') {
        return $configured;
    }

    $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https')
        ? 'https'
        : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    if (!preg_match('/^[a-z0-9.\-:\[\]]+$/i', $host)) {
        $host = 'localhost';
    }

    return $scheme . '://' . $host . rtrim(local_path(), '/');
}

/** @param array<string, mixed> $settings */
function cardpay_missing_settings(array $settings): array
{
    $labels = [
        'cardpay_url' => 'CardPay URL',
        'public_key' => 'Public Key',
        'secret' => 'Secret',
    ];
    $missing = [];

    foreach ($labels as $key => $label) {
        if (trim((string) ($settings[$key] ?? '')) === '') {
            $missing[] = $label;
        }
    }

    return $missing;
}

/** @param array<string, mixed> $settings */
function cardpay_is_configured(array $settings): bool
{
    return cardpay_missing_settings($settings) === [];
}

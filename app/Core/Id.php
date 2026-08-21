<?php
declare(strict_types=1);

namespace CardPay\Core;

final class Id
{
    public static function prefixed(string $prefix, int $bytes = 12): string { return $prefix . '_' . rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '='); }
    public static function secret(int $bytes = 32): string { return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '='); }
}

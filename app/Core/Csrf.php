<?php
declare(strict_types=1);

namespace CardPay\Core;

final class Csrf
{
    public function __construct(private readonly Session $session) {}
    public function token(): string
    {
        $token = $this->session->get('_csrf');
        if (!is_string($token)) { $token = bin2hex(random_bytes(32)); $this->session->put('_csrf', $token); }
        return $token;
    }
    public function valid(?string $token): bool
    {
        $expected = $this->session->get('_csrf');
        return is_string($expected) && is_string($token) && hash_equals($expected, $token);
    }
}

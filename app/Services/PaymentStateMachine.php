<?php
declare(strict_types=1);

namespace CardPay\Services;

use CardPay\Core\DomainException;

final class PaymentStateMachine
{
    private const ALLOWED = [
        'pending' => ['paid','expired','canceled','manual_review'],
        'manual_review' => ['paid','rejected'],
        'expired' => ['manual_review','paid'],
        'paid' => [], 'canceled' => [], 'rejected' => [],
    ];
    public function assert(string $from, string $to): void
    {
        if (!in_array($to, self::ALLOWED[$from] ?? [], true)) {
            throw new DomainException('invalid_status_transition', "Payment cannot transition from {$from} to {$to}.", 409);
        }
    }
    public function can(string $from, string $to): bool { return in_array($to, self::ALLOWED[$from] ?? [], true); }
}

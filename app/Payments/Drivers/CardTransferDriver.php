<?php
declare(strict_types=1);

namespace CardPay\Payments\Drivers;

use CardPay\Core\DomainException;
use CardPay\Payments\PaymentDriverInterface;

final class CardTransferDriver implements PaymentDriverInterface
{
    public function name(): string { return 'card_transfer'; }
    public function createPayment(array $context): array { return $context; }
    public function getPaymentPageData(array $payment): array { return $payment; }
    public function verifyPayment(array $payment): array { return ['verified' => $payment['status'] === 'paid', 'status' => $payment['status']]; }
    public function cancelPayment(array $payment): bool { return $payment['status'] === 'pending'; }
    public function supportsRefund(): bool { return false; }
    public function refundPayment(array $payment, ?int $amount = null): array { throw new DomainException('refund_not_supported', 'Card transfer payments do not support automatic refunds.', 422); }
}

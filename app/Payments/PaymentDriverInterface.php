<?php
declare(strict_types=1);

namespace CardPay\Payments;

interface PaymentDriverInterface
{
    public function name(): string;
    public function createPayment(array $context): array;
    public function getPaymentPageData(array $payment): array;
    public function verifyPayment(array $payment): array;
    public function cancelPayment(array $payment): bool;
    public function supportsRefund(): bool;
    public function refundPayment(array $payment, ?int $amount = null): array;
}

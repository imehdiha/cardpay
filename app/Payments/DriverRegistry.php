<?php
declare(strict_types=1);

namespace CardPay\Payments;

use CardPay\Core\DomainException;

final class DriverRegistry
{
    /** @var array<string,PaymentDriverInterface> */
    private array $drivers = [];
    public function register(PaymentDriverInterface $driver): void { $this->drivers[$driver->name()] = $driver; }
    public function get(string $name): PaymentDriverInterface { return $this->drivers[$name] ?? throw new DomainException('payment_driver_not_found', 'Payment driver is unavailable.', 500); }
}

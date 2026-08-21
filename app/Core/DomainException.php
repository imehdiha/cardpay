<?php
declare(strict_types=1);

namespace CardPay\Core;

use RuntimeException;

final class DomainException extends RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message, public readonly int $httpStatus = 400, public readonly array $details = []) { parent::__construct($message); }
}

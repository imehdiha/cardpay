<?php
declare(strict_types=1);

namespace CardPay\Core;

use RuntimeException;

final class ValidationException extends RuntimeException
{
    public function __construct(public readonly array $errors, string $message = 'Validation failed.') { parent::__construct($message); }
}

<?php
declare(strict_types=1);

namespace CardPay\Core;

final class Config
{
    /** @param array<string,mixed> $items */
    public function __construct(private array $items = []) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->items;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }

    public function set(string $key, mixed $newValue): void
    {
        $parts = explode('.', $key);
        $value =& $this->items;
        foreach ($parts as $part) {
            if (!isset($value[$part]) || !is_array($value[$part])) {
                $value[$part] = [];
            }
            $value =& $value[$part];
        }
        $value = $newValue;
    }
}

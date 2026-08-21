<?php
declare(strict_types=1);

namespace CardPay\Core;

use RuntimeException;

final class Container
{
    /** @var array<string,mixed> */
    private array $bindings = [];
    /** @var array<string,mixed> */
    private array $resolved = [];

    public function set(string $id, mixed $value): void { $this->bindings[$id] = $value; }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->resolved)) {
            return $this->resolved[$id];
        }
        if (!array_key_exists($id, $this->bindings)) {
            throw new RuntimeException("Container binding not found: {$id}");
        }
        $value = $this->bindings[$id];
        return $this->resolved[$id] = is_callable($value) ? $value($this) : $value;
    }
}

<?php
declare(strict_types=1);

namespace CardPay\Core;

use PDO;
use Throwable;

final class Database
{
    public function __construct(private readonly PDO $pdo, private readonly string $prefix = '')
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }
    public function pdo(): PDO { return $this->pdo; }
    public function table(string $name): string { return $this->prefix . $name; }
    public function prefix(): string { return $this->prefix; }

    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql); $stmt->execute($params);
        $row = $stmt->fetch(); return $row === false ? null : $row;
    }
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
    }
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql); $stmt->execute($params); return $stmt->rowCount();
    }
    public function insert(string $sql, array $params = []): int
    {
        $this->execute($sql, $params); return (int) $this->pdo->lastInsertId();
    }
    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $callback($this);
            $this->pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }
}

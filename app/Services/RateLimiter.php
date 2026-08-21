<?php
declare(strict_types=1);

namespace CardPay\Services;

use CardPay\Core\Database;
use CardPay\Core\DomainException;
use PDOException;

final class RateLimiter
{
    public function __construct(private readonly Database $db) {}
    public function hit(string $scope, string $key, int $limit, int $seconds = 60): void
    {
        $bucket = intdiv(time(), $seconds) * $seconds; $table = $this->db->table('rate_limits');
        $row = $this->db->fetch("SELECT id,attempts FROM {$table} WHERE scope=? AND rate_key=? AND window_start=?", [$scope, hash('sha256', $key), $bucket]);
        if ($row) {
            $attempts = (int) $row['attempts'] + 1;
            $this->db->execute("UPDATE {$table} SET attempts=? WHERE id=?", [$attempts, $row['id']]);
        } else {
            $attempts = 1;
            try { $this->db->insert("INSERT INTO {$table} (scope,rate_key,window_start,attempts,expires_at) VALUES (?,?,?,?,?)", [$scope, hash('sha256', $key), $bucket, 1, gmdate('Y-m-d H:i:s', $bucket + $seconds + 60)]); }
            catch (PDOException) { $attempts = $limit + 1; }
        }
        if ($attempts > $limit) throw new DomainException('rate_limit_exceeded', 'Too many requests. Please try again later.', 429, ['retry_after' => ($bucket + $seconds) - time()]);
    }
}

<?php
declare(strict_types=1);

namespace CardPay\Services;

use CardPay\Core\Database;
use CardPay\Core\DomainException;
use PDOException;

final class TokenAllocator
{
    public function __construct(private readonly Database $db) {}
    /** @return array{id:int,token:int,payable_amount:int} */
    public function allocate(int $cardId, int $originalAmount, int $digits): array
    {
        if (!in_array($digits, [2,3], true)) throw new DomainException('validation_failed', 'Token digits must be 2 or 3.', 422);
        $tokens = range(1, $digits === 2 ? 99 : 999);
        for ($i = count($tokens) - 1; $i > 0; $i--) { $j = random_int(0, $i); [$tokens[$i], $tokens[$j]] = [$tokens[$j], $tokens[$i]]; }
        $table = $this->db->table('payment_token_reservations');
        foreach ($tokens as $token) {
            $payable = $originalAmount + $token;
            try {
                $id = $this->db->insert("INSERT INTO {$table} (payment_id,bank_card_id,payable_amount,token,active_key,created_at) VALUES (NULL,?,?,?,?,?)", [$cardId, $payable, $token, 1, gmdate('Y-m-d H:i:s')]);
                return ['id' => $id, 'token' => $token, 'payable_amount' => $payable];
            } catch (PDOException $e) {
                if (!in_array((string) $e->getCode(), ['23000','19'], true)) throw $e;
            }
        }
        throw new DomainException('token_pool_exhausted', 'No payment token is currently available for this amount and card.', 409);
    }
    public function bindPayment(int $reservationId, int $paymentId): void { $this->db->execute('UPDATE ' . $this->db->table('payment_token_reservations') . ' SET payment_id=? WHERE id=?', [$paymentId, $reservationId]); }
    public function cooldown(int $paymentId, int $minutes): void
    {
        $release = gmdate('Y-m-d H:i:s', time() + ($minutes * 60));
        $this->db->execute('UPDATE ' . $this->db->table('payment_token_reservations') . ' SET release_at=? WHERE payment_id=? AND active_key=1', [$release, $paymentId]);
    }
    public function releaseDue(int $limit = 100): int
    {
        $table = $this->db->table('payment_token_reservations');
        $rows = $this->db->fetchAll("SELECT id FROM {$table} WHERE active_key=1 AND release_at IS NOT NULL AND release_at<=? ORDER BY id LIMIT " . max(1, $limit), [gmdate('Y-m-d H:i:s')]);
        if ($rows === []) return 0;
        $ids = array_map('intval', array_column($rows, 'id'));
        return $this->db->execute("UPDATE {$table} SET active_key=NULL WHERE id IN (" . implode(',', $ids) . ')');
    }
}

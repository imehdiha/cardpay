<?php
declare(strict_types=1);

namespace CardPay\Repositories;

use CardPay\Core\Database;

final class PaymentRepository
{
    public function __construct(private readonly Database $db) {}
    public function findPublic(string $id): ?array
    {
        $p = $this->db->table('payments'); $a = $this->db->table('applications'); $b = $this->db->table('bank_cards');
        return $this->db->fetch("SELECT p.*,a.name AS application_name,a.webhook_url,b.title AS card_title,b.bank_name,b.card_holder_name,b.card_number_encrypted FROM {$p} p JOIN {$a} a ON a.id=p.application_id JOIN {$b} b ON b.id=p.bank_card_id WHERE p.public_id=?", [$id]);
    }
    public function findForApplication(string $id, int $appId): ?array { return $this->db->fetch('SELECT * FROM ' . $this->db->table('payments') . ' WHERE public_id=? AND application_id=?', [$id, $appId]); }
    public function all(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $where = ['1=1']; $params = [];
        foreach (['status','application_id','bank_card_id'] as $key) if (!empty($filters[$key])) { $where[] = "p.{$key}=?"; $params[] = $filters[$key]; }
        if (!empty($filters['q'])) { $where[] = '(p.public_id LIKE ? OR p.external_order_id LIKE ?)'; $params[] = '%' . $filters['q'] . '%'; $params[] = '%' . $filters['q'] . '%'; }
        $params[] = $limit; $params[] = $offset;
        return $this->db->fetchAll('SELECT p.*,a.name AS application_name,b.title AS card_title FROM ' . $this->db->table('payments') . ' p JOIN ' . $this->db->table('applications') . ' a ON a.id=p.application_id JOIN ' . $this->db->table('bank_cards') . ' b ON b.id=p.bank_card_id WHERE ' . implode(' AND ', $where) . ' ORDER BY p.id DESC LIMIT ? OFFSET ?', $params);
    }
    public function transition(int $id, string $from, string $to, array $extra = []): bool
    {
        $sets = ['status=?', 'updated_at=?']; $params = [$to, gmdate('Y-m-d H:i:s')];
        foreach ($extra as $key => $value) { $sets[] = "{$key}=?"; $params[] = $value; }
        $params[] = $id; $params[] = $from;
        return $this->db->execute('UPDATE ' . $this->db->table('payments') . ' SET ' . implode(',', $sets) . ' WHERE id=? AND status=?', $params) === 1;
    }
}

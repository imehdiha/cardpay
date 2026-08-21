<?php
declare(strict_types=1);

namespace CardPay\Repositories;

use CardPay\Core\Crypto;
use CardPay\Core\Database;

final class CardRepository
{
    public function __construct(private readonly Database $db, private readonly Crypto $crypto) {}
    public function all(): array { return $this->db->fetchAll('SELECT * FROM ' . $this->db->table('bank_cards') . ' ORDER BY id DESC'); }
    public function find(int $id, bool $activeOnly = false): ?array
    {
        $sql = 'SELECT * FROM ' . $this->db->table('bank_cards') . ' WHERE id=?' . ($activeOnly ? ' AND is_active=1' : '');
        return $this->db->fetch($sql, [$id]);
    }
    public function reveal(array $card): array { $card['card_number'] = $this->crypto->decrypt($card['card_number_encrypted']); return $card; }
    public function create(array $data): int
    {
        $number = preg_replace('/\D+/', '', (string) $data['card_number']); $now = gmdate('Y-m-d H:i:s');
        return $this->db->insert('INSERT INTO ' . $this->db->table('bank_cards') . ' (title,bank_name,card_number_encrypted,card_number_last_four,card_holder_name,iban_encrypted,description,sms_parser_id,is_active,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,1,?,?)', [
            $data['title'], $data['bank_name'], $this->crypto->encrypt($number), substr($number, -4), $data['card_holder_name'],
            empty($data['iban']) ? null : $this->crypto->encrypt((string) $data['iban']), $data['description'] ?? null, $data['sms_parser_id'] ?: null, $now, $now,
        ]);
    }
}

<?php
declare(strict_types=1);

namespace CardPay\Repositories;

use CardPay\Core\Crypto;
use CardPay\Core\Database;
use CardPay\Core\Id;

final class DeviceRepository
{
    public function __construct(private readonly Database $db, private readonly Crypto $crypto) {}
    public function all(): array { return $this->db->fetchAll('SELECT d.*, b.title AS card_title FROM ' . $this->db->table('devices') . ' d JOIN ' . $this->db->table('bank_cards') . ' b ON b.id=d.bank_card_id ORDER BY d.id DESC'); }
    public function findAuthenticated(string $key): ?array { return $this->db->fetch('SELECT * FROM ' . $this->db->table('devices') . ' WHERE device_key=? AND is_active=1 AND revoked_at IS NULL', [$key]); }
    /** @return array{device:array,secret:string} */
    public function create(array $data): array
    {
        $key = 'dev_' . Id::secret(18); $secret = 'dsk_' . Id::secret(32); $now = gmdate('Y-m-d H:i:s');
        $id = $this->db->insert('INSERT INTO ' . $this->db->table('devices') . ' (name,platform,device_key,device_secret_encrypted,secret_fingerprint,bank_card_id,is_active,sms_count,created_at) VALUES (?,?,?,?,?,?,1,0,?)', [$data['name'], $data['platform'], $key, $this->crypto->encrypt($secret), hash('sha256', $secret), $data['bank_card_id'], $now]);
        return ['device' => $this->db->fetch('SELECT * FROM ' . $this->db->table('devices') . ' WHERE id=?', [$id]), 'secret' => $secret];
    }
    public function touch(int $id, string $ip): void { $this->db->execute('UPDATE ' . $this->db->table('devices') . ' SET last_seen_at=?,last_ip=?,sms_count=sms_count+1 WHERE id=?', [gmdate('Y-m-d H:i:s'), $ip, $id]); }
}

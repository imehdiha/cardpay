<?php
declare(strict_types=1);

namespace CardPay\Repositories;

use CardPay\Core\Crypto;
use CardPay\Core\Database;
use CardPay\Core\Id;

final class ApplicationRepository
{
    public function __construct(private readonly Database $db, private readonly Crypto $crypto) {}
    public function all(): array { return $this->db->fetchAll('SELECT * FROM ' . $this->db->table('applications') . ' ORDER BY id DESC'); }
    public function find(int $id): ?array { return $this->db->fetch('SELECT * FROM ' . $this->db->table('applications') . ' WHERE id=?', [$id]); }
    public function findAuthenticated(string $publicKey): ?array
    {
        $keys = $this->db->table('application_api_keys'); $apps = $this->db->table('applications');
        return $this->db->fetch("SELECT a.*, k.id AS api_key_id, k.secret_encrypted FROM {$keys} k JOIN {$apps} a ON a.id=k.application_id WHERE k.public_key=? AND k.is_active=1 AND k.revoked_at IS NULL AND a.is_active=1", [$publicKey]);
    }
    /** @return array{application:array,secret:string} */
    public function create(array $data): array
    {
        $now = gmdate('Y-m-d H:i:s'); $publicKey = 'cp_pk_' . Id::secret(18); $secret = 'cp_sk_' . Id::secret(32);
        $appId = $this->db->insert('INSERT INTO ' . $this->db->table('applications') . ' (name,slug,description,public_key,webhook_url,callback_url,allowed_domains,is_active,token_digits,payment_expiration_minutes,default_bank_card_id,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)', [
            $data['name'], $data['slug'], $data['description'] ?? null, $publicKey, $data['webhook_url'] ?: null, $data['callback_url'] ?: null,
            $data['allowed_domains'] ?? null, 1, $data['token_digits'], $data['payment_expiration_minutes'], $data['default_bank_card_id'] ?: null, $now, $now,
        ]);
        $this->db->insert('INSERT INTO ' . $this->db->table('application_api_keys') . ' (application_id,public_key,secret_encrypted,secret_fingerprint,label,is_active,created_at) VALUES (?,?,?,?,?,1,?)', [
            $appId, $publicKey, $this->crypto->encrypt($secret), hash('sha256', $secret), 'Primary', $now,
        ]);
        return ['application' => $this->find($appId), 'secret' => $secret];
    }
    /** @return array{public_key:string,secret:string} */
    public function rotate(int $appId): array
    {
        $now = gmdate('Y-m-d H:i:s'); $public = 'cp_pk_' . Id::secret(18); $secret = 'cp_sk_' . Id::secret(32);
        $this->db->execute('UPDATE ' . $this->db->table('application_api_keys') . ' SET is_active=0, revoked_at=? WHERE application_id=? AND is_active=1', [$now, $appId]);
        $this->db->insert('INSERT INTO ' . $this->db->table('application_api_keys') . ' (application_id,public_key,secret_encrypted,secret_fingerprint,label,is_active,created_at) VALUES (?,?,?,?,?,1,?)', [$appId, $public, $this->crypto->encrypt($secret), hash('sha256', $secret), 'Rotated', $now]);
        $this->db->execute('UPDATE ' . $this->db->table('applications') . ' SET public_key=?, updated_at=? WHERE id=?', [$public, $now, $appId]);
        return ['public_key' => $public, 'secret' => $secret];
    }
    public function touchKey(int $id): void { $this->db->execute('UPDATE ' . $this->db->table('application_api_keys') . ' SET last_used_at=? WHERE id=?', [gmdate('Y-m-d H:i:s'), $id]); }
}

<?php
declare(strict_types=1);

namespace CardPay\Services;

use CardPay\Core\Database;

final class AuditService
{
    private const REDACT = ['password','password_hash','secret','secret_encrypted','card_number','card_number_encrypted','iban','iban_encrypted','app_key'];
    public function __construct(private readonly Database $db) {}
    public function record(string $action, ?string $entityType = null, string|int|null $entityId = null, array $old = [], array $new = [], ?int $actorId = null): void
    {
        $this->db->insert('INSERT INTO ' . $this->db->table('audit_logs') . ' (actor_type,actor_id,action,entity_type,entity_id,old_values,new_values,ip,user_agent,created_at) VALUES (?,?,?,?,?,?,?,?,?,?)', [
            $actorId ? 'user' : 'system', $actorId, $action, $entityType, $entityId, $this->json($old), $this->json($new), $_SERVER['REMOTE_ADDR'] ?? null, substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500), gmdate('Y-m-d H:i:s'),
        ]);
    }
    private function json(array $data): ?string
    {
        foreach ($data as $key => $value) { if (in_array(strtolower((string) $key), self::REDACT, true)) $data[$key] = '[REDACTED]'; elseif (is_array($value)) $data[$key] = json_decode($this->json($value) ?: '{}', true); }
        return $data === [] ? null : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

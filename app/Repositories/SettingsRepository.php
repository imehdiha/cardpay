<?php
declare(strict_types=1);

namespace CardPay\Repositories;

use CardPay\Core\Database;

final class SettingsRepository
{
    public function __construct(private readonly Database $db) {}
    public function all(): array
    {
        $rows = $this->db->fetchAll('SELECT setting_key, setting_value, value_type FROM ' . $this->db->table('settings'));
        $result = [];
        foreach ($rows as $row) {
            $result[$row['setting_key']] = match ($row['value_type']) {
                'int' => (int) $row['setting_value'], 'bool' => (bool) $row['setting_value'],
                'json' => json_decode((string) $row['setting_value'], true), default => $row['setting_value'],
            };
        }
        return $result;
    }
    public function get(string $key, mixed $default = null): mixed { return $this->all()[$key] ?? $default; }
    public function set(string $key, mixed $value, string $type = 'string', bool $public = false): void
    {
        $table = $this->db->table('settings');
        $stored = $type === 'json' ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;
        $existing = $this->db->fetch("SELECT id FROM {$table} WHERE setting_key = ?", [$key]);
        if ($existing) $this->db->execute("UPDATE {$table} SET setting_value=?, value_type=?, is_public=?, updated_at=? WHERE id=?", [$stored, $type, (int) $public, gmdate('Y-m-d H:i:s'), $existing['id']]);
        else $this->db->insert("INSERT INTO {$table} (setting_key,setting_value,value_type,is_public,updated_at) VALUES (?,?,?,?,?)", [$key, $stored, $type, (int) $public, gmdate('Y-m-d H:i:s')]);
    }
}

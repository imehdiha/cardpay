<?php
declare(strict_types=1);

namespace CardPay\Core;

use RuntimeException;

final class Migrator
{
    public function __construct(private readonly Database $db, private readonly string $path) {}

    public function migrate(): void
    {
        $table = $this->db->table('migrations');
        $driver = $this->db->pdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $id = $driver === 'mysql' ? 'BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $this->db->pdo()->exec("CREATE TABLE IF NOT EXISTS {$table} (id {$id}, version VARCHAR(120) NOT NULL UNIQUE, executed_at VARCHAR(32) NOT NULL)");
        foreach (glob($this->path . '/*.php') ?: [] as $file) {
            $migration = require $file;
            if (!is_array($migration) || !isset($migration['version'], $migration['up']) || !is_callable($migration['up'])) {
                throw new RuntimeException("Invalid migration: {$file}");
            }
            $exists = $this->db->fetch("SELECT id FROM {$table} WHERE version = ?", [$migration['version']]);
            if ($exists) continue;
            if ($driver === 'mysql') {
                // MySQL DDL implicitly commits, so wrapping schema DDL in a PDO transaction is unsafe.
                $migration['up']($this->db);
                $this->db->insert("INSERT INTO {$table} (version, executed_at) VALUES (?, ?)", [$migration['version'], gmdate('Y-m-d H:i:s')]);
            } else {
                $this->db->transaction(function () use ($migration, $table): void {
                    $migration['up']($this->db);
                    $this->db->insert("INSERT INTO {$table} (version, executed_at) VALUES (?, ?)", [$migration['version'], gmdate('Y-m-d H:i:s')]);
                });
            }
        }
    }
}

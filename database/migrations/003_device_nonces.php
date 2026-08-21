<?php
declare(strict_types=1);

use CardPay\Core\Database;

return [
    'version' => '003_device_nonces',
    'up' => static function (Database $db): void {
        $p = $db->prefix();
        $mysql = $db->pdo()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
        $id = $mysql ? 'BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $big = $mysql ? 'BIGINT UNSIGNED' : 'INTEGER';
        $engine = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
        $db->pdo()->exec("CREATE TABLE {$p}device_nonces (id {$id}, device_id {$big} NOT NULL, nonce VARCHAR(190) NOT NULL, expires_at VARCHAR(32) NOT NULL, created_at VARCHAR(32) NOT NULL, UNIQUE (device_id, nonce)){$engine}");
        $db->pdo()->exec("CREATE INDEX {$p}device_nonce_expiry_idx ON {$p}device_nonces (expires_at)");
    },
];

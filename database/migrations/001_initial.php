<?php
declare(strict_types=1);

use CardPay\Core\Database;

return [
    'version' => '001_initial',
    'up' => static function (Database $db): void {
        $p = $db->prefix();
        $mysql = $db->pdo()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
        $id = $mysql ? 'BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $big = $mysql ? 'BIGINT UNSIGNED' : 'INTEGER';
        $int = $mysql ? 'INT UNSIGNED' : 'INTEGER';
        $bool = $mysql ? 'TINYINT(1)' : 'INTEGER';
        $text = $mysql ? 'LONGTEXT' : 'TEXT';
        $engine = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
        $tables = [];

        $tables[] = "CREATE TABLE {$p}users (
            id {$id}, name VARCHAR(120) NOT NULL, username VARCHAR(190) NOT NULL UNIQUE,
            mobile VARCHAR(30) NULL, password_hash VARCHAR(255) NOT NULL, role VARCHAR(40) NOT NULL DEFAULT 'admin',
            is_active {$bool} NOT NULL DEFAULT 1, last_login_at VARCHAR(32) NULL, last_login_ip VARCHAR(64) NULL,
            created_at VARCHAR(32) NOT NULL, updated_at VARCHAR(32) NOT NULL
        ){$engine}";
        $tables[] = "CREATE TABLE {$p}settings (
            id {$id}, setting_key VARCHAR(190) NOT NULL UNIQUE, setting_value {$text} NULL,
            value_type VARCHAR(30) NOT NULL DEFAULT 'string', is_public {$bool} NOT NULL DEFAULT 0,
            updated_at VARCHAR(32) NOT NULL
        ){$engine}";
        $tables[] = "CREATE TABLE {$p}sms_parsers (
            id {$id}, name VARCHAR(150) NOT NULL, bank_name VARCHAR(150) NOT NULL, sender_pattern VARCHAR(255) NULL,
            amount_regex VARCHAR(500) NOT NULL, date_regex VARCHAR(500) NULL, time_regex VARCHAR(500) NULL,
            transaction_type_regex VARCHAR(500) NULL, positive_keywords {$text} NULL, negative_keywords {$text} NULL,
            sample_sms {$text} NULL, is_active {$bool} NOT NULL DEFAULT 1, created_at VARCHAR(32) NOT NULL, updated_at VARCHAR(32) NOT NULL
        ){$engine}";
        $tables[] = "CREATE TABLE {$p}bank_cards (
            id {$id}, title VARCHAR(150) NOT NULL, bank_name VARCHAR(150) NOT NULL, card_number_encrypted {$text} NOT NULL,
            card_number_last_four VARCHAR(4) NOT NULL, card_holder_name VARCHAR(190) NOT NULL, iban_encrypted {$text} NULL,
            description {$text} NULL, sms_parser_id {$big} NULL, is_active {$bool} NOT NULL DEFAULT 1,
            created_at VARCHAR(32) NOT NULL, updated_at VARCHAR(32) NOT NULL
        ){$engine}";
        $tables[] = "CREATE INDEX {$p}bank_cards_active_idx ON {$p}bank_cards (is_active)";
        $tables[] = "CREATE TABLE {$p}applications (
            id {$id}, name VARCHAR(150) NOT NULL, slug VARCHAR(150) NOT NULL UNIQUE, description {$text} NULL,
            public_key VARCHAR(100) NOT NULL UNIQUE, webhook_url VARCHAR(500) NULL, callback_url VARCHAR(500) NULL,
            allowed_domains {$text} NULL, is_active {$bool} NOT NULL DEFAULT 1, token_digits {$int} NOT NULL DEFAULT 3,
            payment_expiration_minutes {$int} NOT NULL DEFAULT 30, default_bank_card_id {$big} NULL,
            created_at VARCHAR(32) NOT NULL, updated_at VARCHAR(32) NOT NULL
        ){$engine}";
        $tables[] = "CREATE TABLE {$p}application_api_keys (
            id {$id}, application_id {$big} NOT NULL, public_key VARCHAR(100) NOT NULL UNIQUE,
            secret_encrypted {$text} NOT NULL, secret_fingerprint VARCHAR(64) NOT NULL, label VARCHAR(120) NULL,
            is_active {$bool} NOT NULL DEFAULT 1, last_used_at VARCHAR(32) NULL, revoked_at VARCHAR(32) NULL, created_at VARCHAR(32) NOT NULL
        ){$engine}";
        $tables[] = "CREATE INDEX {$p}api_keys_app_idx ON {$p}application_api_keys (application_id, is_active)";
        $tables[] = "CREATE TABLE {$p}devices (
            id {$id}, name VARCHAR(150) NOT NULL, platform VARCHAR(20) NOT NULL, device_key VARCHAR(100) NOT NULL UNIQUE,
            device_secret_encrypted {$text} NOT NULL, secret_fingerprint VARCHAR(64) NOT NULL, bank_card_id {$big} NOT NULL,
            is_active {$bool} NOT NULL DEFAULT 1, last_seen_at VARCHAR(32) NULL, last_ip VARCHAR(64) NULL,
            sms_count {$big} NOT NULL DEFAULT 0, created_at VARCHAR(32) NOT NULL, revoked_at VARCHAR(32) NULL
        ){$engine}";
        $tables[] = "CREATE INDEX {$p}devices_card_idx ON {$p}devices (bank_card_id, is_active)";
        $tables[] = "CREATE TABLE {$p}payments (
            id {$id}, public_id VARCHAR(80) NOT NULL UNIQUE, application_id {$big} NOT NULL, bank_card_id {$big} NOT NULL,
            driver VARCHAR(60) NOT NULL DEFAULT 'card_transfer', external_order_id VARCHAR(190) NULL,
            original_amount {$big} NOT NULL, token {$int} NOT NULL, payable_amount {$big} NOT NULL, currency VARCHAR(10) NOT NULL DEFAULT 'IRR',
            description {$text} NULL, customer_name VARCHAR(190) NULL, customer_mobile VARCHAR(30) NULL,
            customer_reference VARCHAR(190) NULL, status VARCHAR(30) NOT NULL, expires_at VARCHAR(32) NOT NULL,
            paid_at VARCHAR(32) NULL, canceled_at VARCHAR(32) NULL, matched_sms_id {$big} NULL,
            return_url VARCHAR(500) NULL, callback_url VARCHAR(500) NULL, metadata_json {$text} NULL,
            created_at VARCHAR(32) NOT NULL, updated_at VARCHAR(32) NOT NULL
        ){$engine}";
        $tables[] = "CREATE INDEX {$p}payments_match_idx ON {$p}payments (bank_card_id, status, payable_amount, expires_at)";
        $tables[] = "CREATE INDEX {$p}payments_app_idx ON {$p}payments (application_id, status, created_at)";
        $tables[] = "CREATE INDEX {$p}payments_created_idx ON {$p}payments (created_at)";
        $tables[] = "CREATE TABLE {$p}payment_token_reservations (
            id {$id}, payment_id {$big} NULL, bank_card_id {$big} NOT NULL, payable_amount {$big} NOT NULL,
            token {$int} NOT NULL, active_key {$bool} NULL DEFAULT 1, release_at VARCHAR(32) NULL, created_at VARCHAR(32) NOT NULL,
            UNIQUE (bank_card_id, payable_amount, active_key)
        ){$engine}";
        $tables[] = "CREATE INDEX {$p}token_release_idx ON {$p}payment_token_reservations (active_key, release_at)";
        $tables[] = "CREATE TABLE {$p}incoming_sms (
            id {$id}, device_id {$big} NOT NULL, bank_card_id {$big} NOT NULL, message_id VARCHAR(190) NOT NULL,
            sender VARCHAR(190) NULL, raw_sms {$text} NOT NULL, received_at VARCHAR(32) NOT NULL, server_received_at VARCHAR(32) NOT NULL,
            source_ip VARCHAR(64) NULL, parse_status VARCHAR(30) NOT NULL DEFAULT 'pending', parsed_amount {$big} NULL,
            parsed_transaction_at VARCHAR(32) NULL, parse_error VARCHAR(500) NULL, match_status VARCHAR(30) NOT NULL DEFAULT 'unmatched',
            matched_payment_id {$big} NULL, used_at VARCHAR(32) NULL, created_at VARCHAR(32) NOT NULL,
            UNIQUE (device_id, message_id)
        ){$engine}";
        $tables[] = "CREATE INDEX {$p}sms_match_idx ON {$p}incoming_sms (bank_card_id, match_status, parsed_amount, created_at)";
        $tables[] = "CREATE TABLE {$p}payment_matches (
            id {$id}, payment_id {$big} NOT NULL, incoming_sms_id {$big} NOT NULL, match_type VARCHAR(30) NOT NULL,
            confidence VARCHAR(20) NOT NULL, decided_by {$big} NULL, notes {$text} NULL, created_at VARCHAR(32) NOT NULL,
            UNIQUE (payment_id, incoming_sms_id)
        ){$engine}";
        $tables[] = "CREATE TABLE {$p}manual_review_requests (
            id {$id}, payment_id {$big} NOT NULL, incoming_sms_id {$big} NULL, reported_amount {$big} NULL,
            approximate_paid_at VARCHAR(32) NULL, contact_mobile VARCHAR(30) NULL, customer_note {$text} NULL,
            receipt_path VARCHAR(500) NULL, actual_amount {$big} NULL, internal_note {$text} NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'pending', reviewed_by {$big} NULL, reviewed_at VARCHAR(32) NULL,
            created_at VARCHAR(32) NOT NULL, updated_at VARCHAR(32) NOT NULL
        ){$engine}";
        $tables[] = "CREATE INDEX {$p}manual_review_idx ON {$p}manual_review_requests (status, created_at)";
        $tables[] = "CREATE TABLE {$p}webhook_events (
            id {$id}, event_id VARCHAR(80) NOT NULL UNIQUE, application_id {$big} NOT NULL, payment_id {$big} NOT NULL,
            event_type VARCHAR(80) NOT NULL, payload_json {$text} NOT NULL, created_at VARCHAR(32) NOT NULL
        ){$engine}";
        $tables[] = "CREATE INDEX {$p}webhook_event_payment_idx ON {$p}webhook_events (payment_id, event_type)";
        $tables[] = "CREATE TABLE {$p}webhook_deliveries (
            id {$id}, webhook_event_id {$big} NOT NULL, url VARCHAR(500) NOT NULL, attempt {$int} NOT NULL DEFAULT 0,
            status VARCHAR(30) NOT NULL DEFAULT 'pending', response_status {$int} NULL, response_body {$text} NULL,
            duration_ms {$int} NULL, error_message VARCHAR(500) NULL, next_attempt_at VARCHAR(32) NULL,
            last_attempt_at VARCHAR(32) NULL, created_at VARCHAR(32) NOT NULL, updated_at VARCHAR(32) NOT NULL
        ){$engine}";
        $tables[] = "CREATE INDEX {$p}webhook_due_idx ON {$p}webhook_deliveries (status, next_attempt_at)";
        $tables[] = "CREATE TABLE {$p}idempotency_keys (
            id {$id}, application_id {$big} NOT NULL, idempotency_key VARCHAR(190) NOT NULL, request_hash VARCHAR(64) NOT NULL,
            payment_id {$big} NULL, response_json {$text} NULL, expires_at VARCHAR(32) NOT NULL, created_at VARCHAR(32) NOT NULL,
            UNIQUE (application_id, idempotency_key)
        ){$engine}";
        $tables[] = "CREATE TABLE {$p}api_nonces (
            id {$id}, application_id {$big} NOT NULL, nonce VARCHAR(190) NOT NULL, expires_at VARCHAR(32) NOT NULL,
            created_at VARCHAR(32) NOT NULL, UNIQUE (application_id, nonce)
        ){$engine}";
        $tables[] = "CREATE INDEX {$p}nonces_expiry_idx ON {$p}api_nonces (expires_at)";
        $tables[] = "CREATE TABLE {$p}rate_limits (
            id {$id}, scope VARCHAR(40) NOT NULL, rate_key VARCHAR(190) NOT NULL, window_start {$big} NOT NULL,
            attempts {$int} NOT NULL DEFAULT 1, expires_at VARCHAR(32) NOT NULL, UNIQUE (scope, rate_key, window_start)
        ){$engine}";
        $tables[] = "CREATE INDEX {$p}rate_expiry_idx ON {$p}rate_limits (expires_at)";
        $tables[] = "CREATE TABLE {$p}audit_logs (
            id {$id}, actor_type VARCHAR(40) NOT NULL, actor_id {$big} NULL, action VARCHAR(120) NOT NULL,
            entity_type VARCHAR(80) NULL, entity_id VARCHAR(100) NULL, old_values {$text} NULL, new_values {$text} NULL,
            ip VARCHAR(64) NULL, user_agent VARCHAR(500) NULL, created_at VARCHAR(32) NOT NULL
        ){$engine}";
        $tables[] = "CREATE INDEX {$p}audit_entity_idx ON {$p}audit_logs (entity_type, entity_id, created_at)";

        foreach ($tables as $sql) $db->pdo()->exec($sql);
    },
];

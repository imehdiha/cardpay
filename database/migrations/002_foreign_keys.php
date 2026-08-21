<?php
declare(strict_types=1);

use CardPay\Core\Database;

return [
    'version' => '002_foreign_keys',
    'up' => static function (Database $db): void {
        if ($db->pdo()->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') return;
        $p = $db->prefix();
        $constraints = [
            "ALTER TABLE {$p}bank_cards ADD CONSTRAINT {$p}fk_card_parser FOREIGN KEY (sms_parser_id) REFERENCES {$p}sms_parsers(id) ON DELETE SET NULL",
            "ALTER TABLE {$p}applications ADD CONSTRAINT {$p}fk_app_card FOREIGN KEY (default_bank_card_id) REFERENCES {$p}bank_cards(id) ON DELETE SET NULL",
            "ALTER TABLE {$p}application_api_keys ADD CONSTRAINT {$p}fk_key_app FOREIGN KEY (application_id) REFERENCES {$p}applications(id) ON DELETE RESTRICT",
            "ALTER TABLE {$p}devices ADD CONSTRAINT {$p}fk_device_card FOREIGN KEY (bank_card_id) REFERENCES {$p}bank_cards(id) ON DELETE RESTRICT",
            "ALTER TABLE {$p}payments ADD CONSTRAINT {$p}fk_payment_app FOREIGN KEY (application_id) REFERENCES {$p}applications(id) ON DELETE RESTRICT",
            "ALTER TABLE {$p}payments ADD CONSTRAINT {$p}fk_payment_card FOREIGN KEY (bank_card_id) REFERENCES {$p}bank_cards(id) ON DELETE RESTRICT",
            "ALTER TABLE {$p}payment_token_reservations ADD CONSTRAINT {$p}fk_res_payment FOREIGN KEY (payment_id) REFERENCES {$p}payments(id) ON DELETE SET NULL",
            "ALTER TABLE {$p}incoming_sms ADD CONSTRAINT {$p}fk_sms_device FOREIGN KEY (device_id) REFERENCES {$p}devices(id) ON DELETE RESTRICT",
            "ALTER TABLE {$p}incoming_sms ADD CONSTRAINT {$p}fk_sms_card FOREIGN KEY (bank_card_id) REFERENCES {$p}bank_cards(id) ON DELETE RESTRICT",
            "ALTER TABLE {$p}payment_matches ADD CONSTRAINT {$p}fk_match_payment FOREIGN KEY (payment_id) REFERENCES {$p}payments(id) ON DELETE RESTRICT",
            "ALTER TABLE {$p}payment_matches ADD CONSTRAINT {$p}fk_match_sms FOREIGN KEY (incoming_sms_id) REFERENCES {$p}incoming_sms(id) ON DELETE RESTRICT",
            "ALTER TABLE {$p}manual_review_requests ADD CONSTRAINT {$p}fk_review_payment FOREIGN KEY (payment_id) REFERENCES {$p}payments(id) ON DELETE RESTRICT",
            "ALTER TABLE {$p}webhook_events ADD CONSTRAINT {$p}fk_event_payment FOREIGN KEY (payment_id) REFERENCES {$p}payments(id) ON DELETE RESTRICT",
            "ALTER TABLE {$p}webhook_deliveries ADD CONSTRAINT {$p}fk_delivery_event FOREIGN KEY (webhook_event_id) REFERENCES {$p}webhook_events(id) ON DELETE RESTRICT",
            "ALTER TABLE {$p}idempotency_keys ADD CONSTRAINT {$p}fk_idem_app FOREIGN KEY (application_id) REFERENCES {$p}applications(id) ON DELETE RESTRICT",
        ];
        foreach ($constraints as $sql) $db->pdo()->exec($sql);
    },
];

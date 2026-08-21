<?php
declare(strict_types=1);

use CardPay\Core\Database;

return [
    'version' => '004_saman_parser_preset',
    'up' => static function (Database $db): void {
        $table = $db->table('sms_parsers');
        if ($db->fetch("SELECT id FROM {$table} WHERE name=? LIMIT 1", ['واریز بانک سامان'])) return;
        $now = gmdate('Y-m-d H:i:s');
        $db->insert("INSERT INTO {$table} (name,bank_name,sender_pattern,amount_regex,date_regex,time_regex,transaction_type_regex,positive_keywords,negative_keywords,sample_sms,is_active,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)", [
            'واریز بانک سامان',
            'بانک سامان',
            null,
            '/واریز\s+مبلغ\s+(?<amount>[0-9۰-۹,٬ ]+)\s*ریال/u',
            '/(?<date>[0-9۰-۹]{4}\/[0-9۰-۹]{1,2}\/[0-9۰-۹]{1,2})/u',
            '/(?<time>[0-9۰-۹]{1,2}:[0-9۰-۹]{2})/u',
            '/(?<type>واریز|برداشت)/u',
            json_encode(['واریز'], JSON_UNESCAPED_UNICODE),
            json_encode(['برداشت'], JSON_UNESCAPED_UNICODE),
            "بانك سامان\nواريز مبلغ  1,000,000ريال\nبه ‪9001-800-2156834-1‬\nمانده 1,397,604\n1405/5/13\n1:57",
            1,
            $now,
            $now,
        ]);
    },
];

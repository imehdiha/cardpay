<?php
declare(strict_types=1);

namespace CardPay\Services;

final class SmsParserService
{
    public function __construct(private readonly AmountNormalizer $normalizer) {}
    /** @return array{success:bool,amount:?int,transaction_at:?string,error:?string,type:string} */
    public function parse(string $raw, array $parser, string $receivedAt): array
    {
        $normalized = $this->normalizeText($raw);
        $positive = $this->keywords($parser['positive_keywords'] ?? null);
        $negative = $this->keywords($parser['negative_keywords'] ?? null);
        foreach ($negative as $keyword) if ($keyword !== '' && mb_stripos($normalized, $keyword) !== false) return $this->fail('negative_transaction', 'debit');
        if ($positive !== [] && !$this->containsAny($normalized, $positive)) return $this->fail('positive_keyword_not_found', 'unknown');
        if (!empty($parser['sender_pattern']) && @preg_match((string) $parser['sender_pattern'], '') !== false) {
            // Sender is validated by the caller; parser remains independently testable.
        }
        $regex = (string) ($parser['amount_regex'] ?? '');
        if ($regex === '' || @preg_match($regex, $normalized, $match) !== 1) return $this->fail('amount_not_found', 'credit');
        $amount = $this->normalizer->integer((string) ($match['amount'] ?? $match[1] ?? $match[0]));
        if ($amount === null || $amount <= 0) return $this->fail('invalid_amount', 'credit');
        $transactionAt = $receivedAt;
        return ['success' => true, 'amount' => $amount, 'transaction_at' => $transactionAt, 'error' => null, 'type' => 'credit'];
    }
    private function keywords(mixed $value): array
    {
        if (is_array($value)) return array_values(array_filter(array_map('trim', $value)));
        $json = json_decode((string) $value, true);
        if (is_array($json)) return array_values(array_filter(array_map('trim', $json)));
        return array_values(array_filter(array_map('trim', preg_split('/[,\n]+/u', (string) $value) ?: [])));
    }
    private function normalizeText(string $text): string
    {
        return strtr($this->normalizer->normalizeDigits($text), ['ي'=>'ی','ى'=>'ی','ك'=>'ک','ة'=>'ه','ۀ'=>'ه']);
    }
    private function containsAny(string $text, array $keywords): bool { foreach ($keywords as $word) if (mb_stripos($text, $word) !== false) return true; return false; }
    private function fail(string $error, string $type): array { return ['success'=>false,'amount'=>null,'transaction_at'=>null,'error'=>$error,'type'=>$type]; }
}

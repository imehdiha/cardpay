<?php
declare(strict_types=1);
namespace CardPay\Tests\Unit;
use CardPay\Services\AmountNormalizer;use CardPay\Services\SmsParserService;use PHPUnit\Framework\TestCase;
final class SmsParserServiceTest extends TestCase
{
    private function parser():array{return['amount_regex'=>'/واریز[:\s]+(?<amount>[0-9۰-۹,٬ ]+)/u','positive_keywords'=>json_encode(['واریز'],JSON_UNESCAPED_UNICODE),'negative_keywords'=>json_encode(['برداشت','مانده'],JSON_UNESCAPED_UNICODE)];}
    public function test_extracts_credit_amount():void{$r=(new SmsParserService(new AmountNormalizer()))->parse('واریز: ۸۰٬۰۰۰٬۱۲۷ ریال',$this->parser(),'2026-08-03 10:00:00');self::assertTrue($r['success']);self::assertSame(80000127,$r['amount']);}
    public function test_rejects_debit_and_balance_messages():void{$r=(new SmsParserService(new AmountNormalizer()))->parse('برداشت: 500,000 مانده: 2,000,000',$this->parser(),'2026-08-03 10:00:00');self::assertFalse($r['success']);self::assertSame('negative_transaction',$r['error']);}
}

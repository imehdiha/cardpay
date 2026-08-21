<?php
declare(strict_types=1);
namespace CardPay\Tests\Unit;
use CardPay\Services\AmountNormalizer;
use PHPUnit\Framework\TestCase;
final class AmountNormalizerTest extends TestCase
{
    public function test_normalizes_persian_and_arabic_digits():void{$n=new AmountNormalizer();self::assertSame('1234567890',$n->normalizeDigits('۱۲۳٤٥٦۷۸۹۰'));}
    public function test_normalizes_grouped_amount_to_integer():void{$n=new AmountNormalizer();self::assertSame(80000127,$n->integer('۸۰٬۰۰۰٬۱۲۷ ریال'));self::assertNull($n->integer('بدون مبلغ'));}
}

<?php
declare(strict_types=1);

namespace CardPay\Tests\Unit;

use CardPay\Services\AmountNormalizer;
use CardPay\Services\SmsParserService;
use PHPUnit\Framework\TestCase;

final class SamanParserPresetTest extends TestCase
{
    public function test_real_saman_credit_sample_extracts_transfer_not_balance(): void
    {
        $preset = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/resources/parsers/saman-credit-fa.json'), true, 512, JSON_THROW_ON_ERROR);
        $result = (new SmsParserService(new AmountNormalizer()))->parse($preset['sample_sms'], $preset, gmdate('Y-m-d H:i:s'));

        self::assertTrue($result['success']);
        self::assertSame(1000000, $result['amount']);
        self::assertSame('credit', $result['type']);
    }
}

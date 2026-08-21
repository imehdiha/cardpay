<?php
declare(strict_types=1);

namespace CardPay\Services;

final class AmountNormalizer
{
    public function normalizeDigits(string $value): string
    {
        return strtr($value, ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
    }
    public function integer(string $value): ?int
    {
        $digits = preg_replace('/[^0-9]/', '', $this->normalizeDigits($value));
        if ($digits === '' || !ctype_digit($digits)) return null;
        return (int) $digits;
    }
}

<?php
declare(strict_types=1);

namespace CardPay\Core;

use DateTimeImmutable;
use DateTimeZone;

final class Time
{
    public const STORAGE_FORMAT = 'Y-m-d H:i:s';

    public static function formatLocal(mixed $value, string $timezone = 'Asia/Tehran', string $format = 'Y/m/d H:i'): string
    {
        $raw = trim((string) $value);
        if ($raw === '' || $raw === '—') return '—';
        try {
            $date = self::parseUtc($raw);
            return $date->setTimezone(self::zone($timezone))->format($format);
        } catch (\Throwable) {
            return $raw;
        }
    }

    public static function localInputToUtc(string $value, string $timezone = 'Asia/Tehran'): ?string
    {
        $raw = trim($value);
        if ($raw === '') return null;
        $zone = self::zone($timezone);
        foreach (['!Y-m-d\\TH:i', '!Y-m-d\\TH:i:s', '!Y-m-d H:i:s'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $raw, $zone);
            $errors = DateTimeImmutable::getLastErrors();
            if ($date && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date->setTimezone(new DateTimeZone('UTC'))->format(self::STORAGE_FORMAT);
            }
        }
        return null;
    }

    /** @return array{0:string,1:string} UTC start (inclusive) and end (exclusive). */
    public static function localDayBoundsUtc(?string $date = null, string $timezone = 'Asia/Tehran'): array
    {
        $zone = self::zone($timezone);
        $local = $date
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $date, $zone)
            : new DateTimeImmutable('today', $zone);
        if (!$local || ($date !== null && $local->format('Y-m-d') !== $date)) {
            throw new \InvalidArgumentException('Invalid local date.');
        }
        $utc = new DateTimeZone('UTC');
        return [
            $local->setTimezone($utc)->format(self::STORAGE_FORMAT),
            $local->modify('+1 day')->setTimezone($utc)->format(self::STORAGE_FORMAT),
        ];
    }

    public static function localDate(string $timezone = 'Asia/Tehran', string $modifier = 'today'): string
    {
        return (new DateTimeImmutable($modifier, self::zone($timezone)))->format('Y-m-d');
    }

    private static function parseUtc(string $value): DateTimeImmutable
    {
        $utc = new DateTimeZone('UTC');
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
            $date = DateTimeImmutable::createFromFormat('!' . self::STORAGE_FORMAT, $value, $utc);
            if ($date) return $date;
        }
        return new DateTimeImmutable($value, $utc);
    }

    private static function zone(string $timezone): DateTimeZone
    {
        try { return new DateTimeZone($timezone); }
        catch (\Throwable) { return new DateTimeZone('Asia/Tehran'); }
    }
}

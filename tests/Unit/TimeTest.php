<?php
declare(strict_types=1);

namespace CardPay\Tests\Unit;

use CardPay\Core\Time;
use PHPUnit\Framework\TestCase;

final class TimeTest extends TestCase
{
    public function test_utc_is_displayed_in_tehran_timezone(): void
    {
        self::assertSame('2026/08/05 03:30', Time::formatLocal('2026-08-05 00:00:00', 'Asia/Tehran'));
    }

    public function test_local_datetime_input_is_stored_as_utc(): void
    {
        self::assertSame('2026-08-05 08:30:00', Time::localInputToUtc('2026-08-05T12:00', 'Asia/Tehran'));
    }

    public function test_local_day_bounds_are_converted_to_utc(): void
    {
        self::assertSame(['2026-08-04 20:30:00','2026-08-05 20:30:00'], Time::localDayBoundsUtc('2026-08-05', 'Asia/Tehran'));
    }
}

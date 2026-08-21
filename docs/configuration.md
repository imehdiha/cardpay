# پیکربندی

تنظیمات زیرساختی در `.env` و تنظیمات محصول/برند در جدول settings هستند. APP_KEY را بعد از وجود داده رمزنگاری‌شده تغییر ندهید؛ کارت‌ها و Secretهای قبلی دیگر قابل بازیابی نخواهند بود.

مقادیر مهم `.env`: `APP_URL`, `APP_ENV`, `APP_DEBUG`, `APP_KEY`, `DB_*`, `DB_PREFIX`, `SESSION_LIFETIME`, `API_TIMESTAMP_TOLERANCE`, rate limits و upload limit.

در Production، `APP_DEBUG=false`، URL از نوع HTTPS و Timezone معتبر IANA باشد. تاریخ دیتابیس همیشه UTC است؛ Timezone فقط برای نمایش استفاده می‌شود.

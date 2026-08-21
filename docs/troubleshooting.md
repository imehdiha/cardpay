# عیب‌یابی

- **Redirect loop:** Document Root و `.htaccess` را بررسی کنید؛ Root پیشنهادی `public/` است.
- **Installer نیازمندی قرمز:** extension یا Permission مربوط را در cPanel فعال کنید.
- **Database failed:** نام دیتابیس cPanel معمولاً Prefix حساب دارد و User باید به DB متصل شده باشد.
- **Signature invalid:** Raw body، path، query، timezone/clock و newlineهای Canonical را بررسی کنید.
- **SMS unmatched:** کارت Device، Sender، Parser test، مبلغ دقیق، زمان دریافت و وضعیت pending را بررسی کنید.
- **Webhook failed:** TLS، DNS، timeout و response پنل را ببینید؛ سپس Retry دستی و Verify API.
- **Secret گم شده:** Secret قابل بازیابی نیست؛ Rotate کنید و کلاینت را هم‌زمان به‌روز کنید.
- **Log:** `storage/logs/cardpay.log` ساختاریافته و Redacted است؛ `APP_DEBUG` در Production روشن نکنید.

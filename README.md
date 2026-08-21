# CardPay

[![Tests](https://github.com/imehdiha/cardpay/actions/workflows/tests.yml/badge.svg)](https://github.com/imehdiha/cardpay/actions/workflows/tests.yml)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](docs/requirements.md)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

CardPay یک درگاه مستقل و Self-hosted برای مدیریت پرداخت کارت‌به‌کارت یک فروشگاه است. برنامه برای PHP 8.2، MySQL 8/MariaDB و هاست اشتراکی طراحی شده و برای کار اصلی به Node.js، Laravel، Redis، Queue worker یا Cron نیاز ندارد.

> CardPay درگاه رسمی بانکی نیست؛ تأیید پرداخت را با تطبیق مبلغ یکتا و پیامک واریز انجام می‌دهد. پیش از استفادهٔ واقعی، مدل امنیت، تنظیمات دامنه، HTTPS، پشتیبان‌گیری و Parser بانک خود را بررسی و آزمایش کنید.

## وضعیت پروژه

این مخزن نسخه‌ای است که در یک استقرار واقعی استفاده شده و اکنون برای توسعهٔ عمومی منتشر می‌شود. مسیرهای اصلی پرداخت، HMAC، دریافت SMS، تطبیق، بررسی دستی و Webhook دارای تست Unit/Integration هستند؛ بااین‌حال نگهدارنده هیچ تضمین مالی یا بانکی ارائه نمی‌کند و هر استقرار باید مستقلاً ارزیابی شود.

تغییرات هر نسخه در [CHANGELOG](CHANGELOG.md) ثبت می‌شوند.

## قابلیت‌ها

- نصب مرحله‌ای تحت وب و قفل خودکار Installer
- اتصال مستقیم یک سایت با HMAC API، Idempotency و کلیدهای قابل چرخش
- کارت بانکی و آیفون/گوشی دریافت‌کننده با داده‌های حساس رمزنگاری‌شده
- رزرو Race-safe توکن‌های ۲ یا ۳ رقمی با دوره Cooldown
- دریافت امن SMS، Parser قابل تنظیم، تطبیق Fail-safe و بررسی دستی
- صفحه پرداخت Hosted فارسی/RTL با Polling و Backoff
- Webhook امضاشده با Retry مبتنی بر درخواست
- پنل مدیریت، گزارش CSV، Audit log و تنظیمات برندینگ
- OpenAPI، راهنمای cPanel، iOS و Android، مثال PHP/JS/cURL و PHPUnit

## نصب سریع

1. محتوای ZIP را در Document Root یا یک زیرپوشه استخراج کنید.
2. پوشه‌های `storage` و `public/uploads` باید توسط PHP قابل نوشتن باشند.
3. دامنه را باز کنید؛ تا پیش از نصب به `/install` هدایت می‌شوید.
4. اطلاعات دیتابیس و مدیر را وارد و نصب را کامل کنید.
5. پس از ورود، راه‌اندازی چهارمرحله‌ای کارت، الگوی پیامک، آیفون و اتصال سایت را دنبال کنید.

برای جزئیات، [راهنمای نصب](docs/installation.md)، [اتصال به سایت PHP اختصاصی](docs/custom-php-integration.md) و [نصب cPanel](docs/cpanel-installation.md) را ببینید.

## توسعه و تست

```bash
composer install
composer check
php -S 127.0.0.1:8080 -t public public/router.php
```

هیچ حساب آزمایشی ثابت یا Secret پیش‌فرض وجود ندارد. اطلاعات مدیر در Installer ساخته می‌شود. نمونه‌ها فقط از داده جعلی استفاده می‌کنند.

تست‌های Unit و Integration از SQLite درون‌حافظه‌ای استفاده می‌کنند و در GitHub Actions روی PHP 8.2، 8.3 و 8.4 اجرا می‌شوند.

## ساختار

- `app/Core`: HTTP، Router، Database، Session، CSRF، Validation و امنیت
- `app/Services`: منطق Payment، SMS، Webhook، Expiration و گزارش
- `app/Payments`: قرارداد Driver و CardTransferDriver
- `database/migrations`: Schema قابل نصب
- `public`: تنها سطح عمومی وب
- `docs`: مستندات محصول و API
- `tests`: تست‌های Unit و Integration

## امنیت و مشارکت

پیش از استفاده در Production، [مدل امنیت و توصیه‌های استقرار](docs/security.md) را بخوانید. آسیب‌پذیری‌ها را مطابق [سیاست امنیتی](SECURITY.md) خصوصی گزارش کنید و برای ارسال تغییر از [راهنمای مشارکت](CONTRIBUTING.md) استفاده کنید.

## مجوز

CardPay تحت [مجوز MIT](LICENSE) منتشر می‌شود. استفاده، تغییر و استفادهٔ تجاری با حفظ متن مجوز و Copyright مجاز است.

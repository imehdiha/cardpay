# معماری CardPay

## مرزها

ورودی‌ها در Controller اعتبارسنجی و به Service تحویل می‌شوند. Controller هیچ SQL یا منطق مالی ندارد. Serviceها تراکنش و قواعد وضعیت را مدیریت می‌کنند، Repositoryها تنها دسترسی داده‌اند و Viewها فقط خروجی escape‌شده می‌سازند. وابستگی‌ها در `bootstrap.php` به‌صورت صریح ساخته می‌شوند.

جریان اصلی:

`HTTP → Router/Middleware → Controller → Service → Repository/PDO`

رویدادهای دامنه پس از Commit به `WebhookService` داده می‌شوند. شکست Webhook نتیجه مالی را Rollback نمی‌کند و Delivery جداگانه قابل Retry می‌ماند.

## Payment Driver

`PaymentDriverInterface` عملیات create/page-data/verify/cancel/refund-capability را تعریف می‌کند. `CardTransferDriver` Driver نسخه اول است. هسته Driver را از Registry بر اساس `payments.driver` دریافت می‌کند؛ افزودن درگاه آینده به تغییر مدل وضعیت یا Controller نیاز ندارد.

## تخصیص توکن و هم‌زمانی

`TokenAllocator` در تراکنش، توکن را با `random_int` انتخاب و رزرو را درج می‌کند. قید یکتای `(bank_card_id, payable_amount, active_key)` اجازه فقط یک رزرو فعال (`active_key=1`) را می‌دهد. Conflict باعث Retry با توکن دیگر می‌شود. رزرو پس از پایان Payment تا `release_at` فعال است و `ExpirationService` در درخواست‌های بعدی آن را آزاد می‌کند. بنابراین رفتار اصلی به Cron وابسته نیست.

## عملیات Lazy

`MaintenanceService::runBudgeted()` در صفحه پرداخت، API، SMS و پنل اجرا می‌شود و در هر درخواست تعداد محدودی انقضا، آزادسازی رزرو و Retry Webhook را پردازش می‌کند. Endpoint اختیاری `/internal/maintenance` برای نصب‌های حرفه‌ای وجود دارد، ولی برای صحت سیستم لازم نیست.

## Fail-safe matching

Device کارت مقصد را تعیین می‌کند. Parser مبلغ و نوع تراکنش را استخراج می‌کند. فقط یک Payment با کارت، مبلغ دقیق، وضعیت pending و بازه زمانی معتبر می‌تواند خودکار paid شود. صفر یا چند Match، Parser نامطمئن، پیام دیرهنگام و Transition نامعتبر به unmatched/manual review ختم می‌شود؛ هیچ حدسی به موفقیت مالی تبدیل نمی‌شود.

## استقرار Subdirectory

URLها از `APP_URL` و مسیر Request ساخته می‌شوند. Root `.htaccess` درخواست‌ها را به `public` می‌فرستد. بهترین چینش cPanel این است که Document Root مستقیماً `public/` باشد؛ حالت استخراج در Root نیز پشتیبانی می‌شود.

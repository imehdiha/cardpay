# تصمیم‌های فنی

1. PHP خالص با PSR-4 و Dependency Injection صریح انتخاب شد تا روی cPanel سبک باشد و Vendor Production حداقلی بماند.
2. Secret HMAC رمزنگاری می‌شود، نه فقط Hash؛ زیرا سرور برای بررسی HMAC به Secret نیاز دارد. fingerprint/hash جدا برای شناسایی و Audit نگهداری می‌شود.
3. Token cooldown با نگه‌داشتن رزرو Active تا `release_at` پیاده می‌شود.
4. Job دائمی وجود ندارد؛ Maintenance در هر درخواست بودجه محدود دارد. زمان Retry در حالت بدون ترافیک تقریبی است.
5. ریال واحد پیش‌فرض است و هیچ Decimal در هسته مالی وجود ندارد.
6. تاریخ UI در نسخه اول Gregorian محلی‌سازی‌شده است؛ DB/API همیشه UTC/ISO 8601 می‌ماند تا وابستگی سنگین اضافه نشود.
7. حذف داده‌های مالی از UI ارائه نمی‌شود؛ موجودیت‌های عملیاتی غیرفعال یا revoke می‌شوند.
8. `return_url` و `callback_url` هر پرداخت فقط با scheme HTTP/HTTPS و در محدودهٔ allowlist دامنهٔ Application پذیرفته می‌شوند؛ برای Production تعریف allowlist ضروری است.
9. SQLite فقط در Integration test پشتیبانی می‌شود؛ Installer Production نیازمند MySQL/MariaDB است.

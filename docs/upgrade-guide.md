# ارتقا

1. Backup کامل بگیرید و Maintenance window اعلام کنید.
2. `.env`, `storage/installed.lock` و Uploadها را حفظ کنید.
3. فایل‌های Release جدید و `vendor/` را جایگزین کنید.
4. `php bin/migrate.php` را با PHP 8.2 اجرا کنید یا صفحه System را بعد از انتشار بررسی کنید.
5. Smoke test: Login، ساخت Payment آزمایشی، Status و Parser test.

Migrationها idempotent و با جدول migrations نسخه‌بندی شده‌اند. هرگز Migration اجراشده را ویرایش نکنید؛ Migration جدید بسازید.

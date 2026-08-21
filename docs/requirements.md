# نیازمندی‌ها

- PHP 8.2+ با `pdo_mysql`, `openssl`, `mbstring`, `json`, `curl`, `fileinfo`
- MySQL 8 یا MariaDB 10.5+ با InnoDB و utf8mb4
- Apache با mod_rewrite؛ در نبود آن Document Root را روی `public/` بگذارید
- HTTPS معتبر برای Production
- دسترسی نوشتن PHP به `storage/` و `public/uploads/`
- Composer autoload و پوشه `vendor/` (در ZIP Release موجود است)

Node.js، Laravel، Redis، Supervisor، Worker دائمی و Cron الزامی نیستند. زمان‌های Retry بدون ترافیک تقریبی‌اند.

# پشتیبان‌گیری و بازیابی

هم‌زمان از دیتابیس، `.env` و `storage/uploads` نسخه رمزنگاری‌شده بگیرید. `.env` حاوی APP_KEY است و بدون آن Card/Secretها قابل Decrypt نیستند. Backup را خارج از Web root نگه دارید.

برای Restore: فایل‌ها را با همان مسیر برگردانید، دیتابیس را Import کنید، `.env` اصلی را بازگردانید، Permissionها را تنظیم و سپس Login، نمایش کارت و Verify یک Payment غیرواقعی را تست کنید. APP_KEY را جداگانه Rotate نکنید.

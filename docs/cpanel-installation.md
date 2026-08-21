# نصب در cPanel

1. در **MySQL Databases** دیتابیس و User بسازید و All Privileges بدهید.
2. ZIP را در File Manager داخل پوشه‌ای مستقل مانند `/home/USER/cardpay` آپلود و Extract کنید؛ آن را روی فایل‌های سایت اصلی Extract نکنید.
3. پیشنهاد امن: یک Subdomain مانند `pay.example.com` بسازید و Document Root آن را روی `/home/USER/cardpay/public` تنظیم کنید. اگر این امکان نبود، Root `.htaccess` درخواست‌ها را به public می‌فرستد و فایل‌های حساس را می‌بندد.
4. از MultiPHP Manager نسخه PHP 8.2+ را انتخاب کنید و extensionهای Requirements را فعال کنید.
5. Permission پوشه‌های قابل‌نوشتن معمولاً 755 یا 775 است؛ از 777 در Production پرهیز کنید.
6. SSL را فعال کنید و URL HTTPS را در Installer وارد کنید.
7. بعد از نصب، `.env` باید 600/640 و خارج از دسترسی HTTP باشد.

برای Subdirectory مقدار URL کامل مانند `https://example.com/pay` را وارد کنید. لینک‌ها از مسیر واقعی Front Controller ساخته می‌شوند.

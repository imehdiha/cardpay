# مدل امنیت

- Passwordها با `password_hash(PASSWORD_DEFAULT)`؛ Session ID پس از Login rotate می‌شود.
- Cookieها HttpOnly، SameSite=Lax و در HTTPS دارای Secure هستند.
- تمام فرم‌های تغییردهنده CSRF token دارند؛ API با HMAC/Device secret محافظت می‌شود.
- Queryها Prepared و تمام خروجی HTML با `htmlspecialchars` escape می‌شود.
- شماره کارت، شبا و Secretهای لازم برای HMAC با AES-256-GCM و کلیدی مشتق از APP_KEY رمز می‌شوند. Secret خام فقط هنگام ایجاد/Rotation نشان داده می‌شود.
- Login/API/Device rate limit دارند و HMAC nonce/timestamp Replay را کاهش می‌دهد.
- `return_url` و `callback_url` هر پرداخت به allowlist دامنهٔ Application محدود می‌شوند.
- آپلود رسید MIME، extension و size را بررسی می‌کند، نام تصادفی می‌دهد و خارج از سطح عمومی نگه می‌دارد.
- CSP، frame-ancestors، nosniff و Referrer-Policy روی پاسخ‌ها اعمال می‌شوند.
- Production خطای داخلی یا Stack trace نشان نمی‌دهد. Logger فیلدهای password/secret/token/card/db را Redact می‌کند.
- عملیات حساس Audit می‌شوند. مجوز Admin در تمام مسیرهای پنل و بررسی دستی اجباری است.

پیشنهاد عملیاتی: HTTPS اجباری، Document Root روی `public/`، دسترسی 600 یا 640 برای `.env`، تعریف allowlist دامنه برای هر Application، Backup رمزنگاری‌شده و چرخش دوره‌ای کلیدهای Application/Device.

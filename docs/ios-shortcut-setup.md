# راه‌اندازی iOS Shortcut

در پنل به «راه‌اندازی ← موبایل دریافت‌کننده» بروید. اگر Secret نمایش داده نمی‌شود، یک Secret تازه بسازید. برای اتصال معمولی آیفون از Endpoint زیر استفاده می‌شود:

```text
POST /api/v1/devices/shortcut-sms
Content-Type: application/json
```

بدنه شامل مشخصات آیفون و متن خام است:

```json
{"device_key":"dev_...","device_secret":"dsk_...","raw_sms":"متن کامل پیامک"}
```

سرور `message_id` و `received_at` را در صورت نبودن می‌سازد. Secret نباید در URL قرار گیرد. روی هاست واقعی فقط از HTTPS استفاده کنید؛ HTTP صرفاً برای تست در شبکه خصوصی محلی مناسب است.

## ساخت Automation

1. در Shortcuts وارد Automation شوید و یک Automation از نوع Message بسازید.
2. در Message Contains کلمه `مبلغ` را وارد و Run Immediately را فعال کنید.
3. Action «Text» را اضافه کنید؛ داخل کادر Text روی Select Variable بزنید و Shortcut Input را انتخاب کنید. اگر جزئیات متغیر نمایش داده شد، Content یا نوع Text را انتخاب کنید.
4. Action «Get Contents of URL» را با Method برابر POST اضافه کنید.
5. بخش Headers را خالی بگذارید.
6. Request Body را JSON بگذارید و سه فیلد `device_key`، `device_secret` و `raw_sms` بسازید. مقدار `raw_sms` باید خروجی آبی اکشن Text در مرحله سوم باشد.
7. برای اولین تست می‌توانید یک Show Notification بعد از درخواست اضافه کنید.

Apple Shortcuts در بعضی نسخه‌ها نام گزینه‌ها را کمی متفاوت نمایش می‌دهد. محرک Message باید اجازه اجرای خودکار داشته باشد.

## روش پیشرفته

Endpoint `/api/v1/devices/incoming-sms` همچنان برای کلاینت‌هایی که HMAC-SHA256 را پشتیبانی می‌کنند موجود است. این روش به timestamp، nonce و canonical signature نیاز دارد و برای Shortcut عادی توصیه نمی‌شود.

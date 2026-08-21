# راه‌اندازی Android Automation

می‌توان از Tasker، MacroDroid، Automate یا ابزار مشابهی که Trigger دریافت SMS و HTTP Request دارد استفاده کرد؛ CardPay به هیچ‌یک وابسته نیست.

Flow عمومی: SMS Received → فیلتر Sender → خواندن متن کامل → ساخت UUID/message_id → زمان ISO 8601 و Unix → JSON → Canonical/HMAC → HTTP POST.

Endpoint: `/api/v1/devices/incoming-sms`. Headerها همان راهنمای Device در پنل هستند. پاسخ 2xx با `success=true` موفق است. برای retry، همان `message_id` را نگه دارید تا ثبت دوگانه نشود. Secret را در Log، Clipboard عمومی، Query string یا Export بدون رمز نگه ندارید.

# ساخت Payment

`POST /api/v1/payments` با HMAC و `Idempotency-Key` الزامی:

```json
{
  "amount": 80000000,
  "external_order_id": "ORDER-123",
  "description": "خرید اشتراک",
  "customer": {"name": "کاربر نمونه", "mobile": "09120000000"},
  "return_url": "https://merchant.example/result",
  "metadata": {"plan_id": 4}
}
```

`amount` عدد صحیح مثبت در کوچک‌ترین واحد پول است. همان Idempotency key با همان body پاسخ قبلی را می‌دهد؛ استفاده با body متفاوت HTTP 409 است. خروجی شامل `payment_url`, `token`, `payable_amount`, `expires_at` است.

`return_url` و `callback_url` اختیاری فقط باید از نوع HTTP/HTTPS باشند. اگر برای Application دامنه‌های مجاز تعریف شده باشد، host هر دو URL باید همان دامنه یا یکی از زیردامنه‌های آن باشد؛ در غیر این صورت درخواست با HTTP 422 رد می‌شود.

# طراحی API

Base path: `/api/v1`، بدنه JSON و زمان‌ها ISO 8601 UTC هستند. موفقیت با `{success:true,data:{...}}` و خطا با `{success:false,error:{code,message,details}}` برمی‌گردد.

## HMAC

Headerها:

- `X-CardPay-Key`: public key
- `X-CardPay-Timestamp`: Unix seconds
- `X-CardPay-Nonce`: مقدار تصادفی یکتا
- `X-CardPay-Signature`: HMAC-SHA256 هگز
- `Idempotency-Key`: برای ساخت Payment

Canonical string:

```text
UPPER_METHOD\n
/path/without/domain\n
raw_query_string\n
lowercase_hex_sha256(raw_body)\n
timestamp\n
nonce
```

امضا: `hash_hmac('sha256', canonical, secret)`. اختلاف زمان پیش‌فرض ۳۰۰ ثانیه است و nonce در همان Application تا پایان پنجره دوباره پذیرفته نمی‌شود.

## Endpointها

- `POST /api/v1/payments`
- `GET /api/v1/payments/{payment_id}`
- `POST /api/v1/payments/{payment_id}/verify`
- `POST /api/v1/payments/{payment_id}/cancel`
- `GET /api/v1/public/payments/{public_id}/status`
- `POST /api/v1/devices/incoming-sms`
- `POST /api/v1/public/payments/{public_id}/manual-review`

شرح کامل Schemaها در `docs/openapi.yaml` است.

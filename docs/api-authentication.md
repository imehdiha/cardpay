# احراز هویت API

Application را در پنل بسازید و Public key/Secret یک‌بارنمایش را امن ذخیره کنید. برای هر درخواست Timestamp یونیکس، Nonce تصادفی حداقل ۱۲ نویسه و Signature ارسال شود.

```text
canonical = METHOD + "\n"
          + PATH + "\n"
          + RAW_QUERY + "\n"
          + SHA256(RAW_BODY) + "\n"
          + TIMESTAMP + "\n"
          + NONCE
signature = HMAC_SHA256_HEX(canonical, secret)
```

PATH شامل مسیر بدون Domain است. Query باید همان رشته خام ارسال‌شده باشد. JSON body را بعد از امضا تغییر یا دوباره serialize نکنید. tolerance پیش‌فرض ۳۰۰ ثانیه است و Nonce تکراری رد می‌شود.

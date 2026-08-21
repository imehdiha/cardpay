# Webhook

بدنه خام JSON را قبل از Parse نگه دارید. Header `X-CardPay-Signature` برابر HMAC-SHA256 هگز بدنه خام با Secret فعال Application است.

پس از مقایسه constant-time، `event_id` را به‌صورت یکتا ذخیره کنید و سریع HTTP 2xx بدهید. Eventها: `payment.created`, `payment.paid`, `payment.expired`, `payment.canceled`, `payment.manual_review`, `payment.rejected`.

Retry: بلافاصله، ۱، ۵، ۱۵ و ۶۰ دقیقه. چون پردازش Request-driven است، بدون ترافیک ارسال دقیقاً سر موعد تضمین نمی‌شود. دکمه Retry دستی در پنل وجود دارد. Verify API مرجع نهایی است.

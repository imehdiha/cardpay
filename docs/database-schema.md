# طرح دیتابیس

تمام زمان‌ها UTC و مبلغ‌ها Integer در کوچک‌ترین واحد پول هستند. Prefix جدول در Installer اعمال می‌شود.

| جدول | نقش و قیود مهم |
|---|---|
| users | مدیرها، role، password_hash، last_login_at |
| settings | key یکتا، value و type |
| applications | پروژه، public_key یکتا، تنظیمات کارت و Webhook |
| application_api_keys | Secret رمزنگاری‌شده، fingerprint، revoke/usage |
| bank_cards | شماره کارت/شبا رمزنگاری‌شده، last_four، Parser |
| devices | کلید یکتا، Secret رمزنگاری‌شده، کارت و last_seen |
| sms_parsers | الگوها، keywords JSON و نمونه |
| payments | public_id یکتا، مبالغ، وضعیت، expiry و metadata |
| payment_token_reservations | رزرو مبلغ دقیق، active_key و release_at؛ Unique برای جلوگیری از Race |
| incoming_sms | Unique(device_id,message_id)، متن خام، parse/match state |
| payment_matches | پیوند SMS/Payment و نوع خودکار/دستی |
| manual_review_requests | فرم کاربر، رسید امن و نتیجه مدیر |
| webhook_events | event_id یکتا و payload immutable |
| webhook_deliveries | attempt/status/next_attempt/response محدودشده |
| idempotency_keys | Unique(application_id,key)، request hash و payment |
| audit_logs | رخدادهای مدیریتی Redacted |
| rate_limits | bucketهای زمان‌دار |
| api_nonces | Replay protection برای HMAC |
| migrations | نسخه‌های Schema |

Indexهای ترکیبی روی مسیرهای Match (`bank_card_id,status,payable_amount,expires_at`)، فیلتر Payment، SMS، Webhookهای موعدرسیده و تاریخ ایجاد تعریف می‌شوند. Foreign Keyها حذف تصادفی داده مالی را محدود می‌کنند؛ عملیات حذف UI عمدتاً Soft-disable است.

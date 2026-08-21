# کدهای خطا

| Code | HTTP | معنی |
|---|---:|---|
| invalid_api_key | 401 | کلید ناشناخته/غیرفعال |
| invalid_signature | 401 | امضا، Timestamp یا Nonce نامعتبر |
| invalid_device_key | 401 | Device نامعتبر |
| rate_limit_exceeded | 429 | عبور از سقف |
| validation_failed | 422 | ورودی نامعتبر |
| invalid_amount | 422 | مبلغ صحیح مثبت نیست |
| invalid_bank_card | 422 | کارت ناموجود/غیرفعال |
| token_pool_exhausted | 409 | Pool مبلغ/کارت پر است |
| idempotency_conflict | 409 | Key با body متفاوت |
| payment_not_found | 404 | Payment متعلق به Application نیست |
| payment_cannot_be_canceled | 409 | Transition مجاز نیست |
| invalid_status_transition | 409 | گذار وضعیت ممنوع |
| internal_error | 500 | خطای داخلی بدون افشای جزئیات |

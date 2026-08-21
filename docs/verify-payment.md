# Verify و لغو

- `GET /api/v1/payments/{PAY_xxx}` وضعیت جاری را می‌دهد.
- `POST /api/v1/payments/{PAY_xxx}/verify` برای تصمیم نهایی پروژه مبدا توصیه می‌شود.
- `POST /api/v1/payments/{PAY_xxx}/cancel` فقط Pending را لغو می‌کند.

پروژه مقصد نباید فقط به Redirect یا Webhook اعتماد کند. بعد از بازگشت کاربر، Verify امضاشده انجام و `status=paid` بررسی شود. Payment paid دوباره cancel/expire نمی‌شود.

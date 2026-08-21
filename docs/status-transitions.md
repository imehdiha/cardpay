# گذار وضعیت Payment

| از | به | عامل |
|---|---|---|
| pending | paid | Match یکتای SMS یا تأیید دستی |
| pending | expired | ExpirationService |
| pending | canceled | API/Admin |
| pending | manual_review | گزارش کاربر، Match مبهم |
| manual_review | paid | مدیر |
| manual_review | rejected | مدیر |
| expired | manual_review | بررسی پیام دیررس/مدیر |
| expired | paid | فقط تأیید صریح مدیر |

`paid` نهایی و موفق است؛ `canceled` و `rejected` خودکار paid نمی‌شوند. تمام Transitionها در `PaymentStateMachine` متمرکزند. Update مالی شرط وضعیت قبلی دارد تا دو درخواست موازی فقط یک‌بار موفق شوند. Webhook `payment.paid` بر مبنای Event یکتا ایجاد می‌شود.

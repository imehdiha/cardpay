# تنظیم SMS Parser

ابتدا نمونه پیامک واقعیِ بدون اطلاعات شخصی وارد کنید. Positive keyword مانند «واریز» و Negative keyword مانند «برداشت» و «مانده» مانع Match اشتباه می‌شوند. Regex مبلغ باید Capture group با نام `amount` یا گروه اول داشته باشد، مانند:

```regex
/واریز[:\s]+(?<amount>[0-9۰-۹,٬ ]+)/u
```

Parser ارقام فارسی/عربی را انگلیسی، جداکننده را حذف و مبلغ را Integer می‌کند. Sender regex روی Header ورودی بررسی می‌شود. Test Parser را پیش از اتصال به کارت اجرا کنید. Presetها در `resources/parsers` JSON هستند؛ Preset نمونه فقط الگوست و باید با متن رسمی بانک خود آزموده شود.

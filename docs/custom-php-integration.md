# اتصال CardPay به سایت PHP اختصاصی

این نصب CardPay برای یک سایت در نظر گرفته شده است. در پنل از بخش «اتصال سایت» نام و آدرس فروشگاه، آدرس Webhook و صفحه بازگشت مشتری را وارد کنید. سپس Public Key و Secret را فقط یک بار بردارید و Secret را خارج از Document Root نگه دارید.

## ۱. ساخت پرداخت

فایل‌های `examples/php/CardPayClient.php` و `examples/php/create-payment.php` را در پروژه خود کپی کنید. مشخصات اتصال را بهتر است از متغیر محیطی بخوانید:

```php
$client = new CardPayClient(
    getenv('CARDPAY_URL'),
    getenv('CARDPAY_PUBLIC_KEY'),
    getenv('CARDPAY_SECRET')
);

$result = $client->createPayment([
    'amount' => 1000000,
    'external_order_id' => 'ORDER-123',
    'return_url' => 'https://shop.example.com/payment/result',
], 'create-order-123');

header('Location: '.$result['data']['payment_url']);
exit;
```

مبلغ بر حسب ریال است. مقدار `Idempotency-Key` را برای هر سفارش ثابت و یکتا نگه دارید تا ارسال دوباره درخواست، پرداخت تکراری نسازد.

## ۲. دریافت نتیجه قطعی

فایل `examples/php/webhook.php` را مبنا قرار دهید و URL آن را در صفحه «اتصال سایت» ثبت کنید. امضا باید روی بدنه خام درخواست بررسی شود. `event_id` را با محدودیت UNIQUE ذخیره کنید تا یک رویداد دو بار پردازش نشود.

در رویداد `payment.paid`، قبل از پرداخت‌شده‌کردن سفارش این سه مورد را با دیتابیس فروشگاه تطبیق دهید:

- `external_order_id`
- `payable_amount`
- وضعیت فعلی سفارش

صفحه بازگشت مشتری صرفاً برای تجربه کاربری است؛ ملاک قطعی، Webhook معتبر یا پاسخ Verify API است.

## ۳. بررسی دوباره وضعیت

```php
$result = $client->verify('PAY_xxx');
if (($result['data']['status'] ?? '') === 'paid') {
    // وضعیت سفارش را با کنترل مبلغ و شناسه به‌روزرسانی کنید.
}
```

## ترتیب جریان

1. سایت شما سفارش را می‌سازد و API ایجاد پرداخت را صدا می‌زند.
2. مشتری به `payment_url` می‌رود و مبلغ یکتای نمایش‌داده‌شده را کارت‌به‌کارت می‌کند.
3. آیفون پیامک بانک را به CardPay می‌فرستد.
4. CardPay پیامک را با پرداخت منتظر تطبیق می‌دهد.
5. CardPay یک Webhook امضاشده برای سایت شما می‌فرستد.
6. سایت شما سفارش را پرداخت‌شده می‌کند.

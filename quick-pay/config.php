<?php
declare(strict_types=1);

/**
 * تنظیمات همین پوشه
 *
 * سه مقدار cardpay_url، public_key و secret را از بخش «اتصال سایت» پنل
 * CardPay بردارید و دقیقاً بین کوتیشن‌های روبه‌روی هر گزینه قرار دهید.
 */
return [
    'site_name' => 'دفتر',
    'product_name' => 'خرید آزمایشی',
    'product_description' => 'این محصول برای تست درگاه پرداخت در نظر گرفته شده است.',

    // مبلغ بر حسب تومان است.
    'default_amount_toman' => 10_000,

    // نمونه: 'cardpay_url' => 'https://pay.example.com/cardpay',
    'cardpay_url' => '',
    'public_key' => '',
    'secret' => '',

    // اختیاری؛ نمونه: https://office.example.com/pay
    // اگر خالی باشد، آدرس پوشه به‌صورت خودکار از درخواست فعلی ساخته می‌شود.
    'public_url' => '',

    // پس از پایان تست می‌توانید این گزینه را false کنید.
    'show_gateway_errors' => true,
];

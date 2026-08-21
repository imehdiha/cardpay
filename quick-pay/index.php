<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$siteName = (string) ($config['site_name'] ?? 'دفتر');
$productName = (string) ($config['product_name'] ?? 'خرید آزمایشی');
$description = (string) ($config['product_description'] ?? 'این محصول برای تست درگاه پرداخت در نظر گرفته شده است.');
$defaultAmount = (int) ($config['default_amount_toman'] ?? 10_000);
$error = flash_error();
$missingSettings = cardpay_missing_settings($config);
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#f4f5f7">
    <meta name="description" content="صفحه پرداخت <?= h($siteName) ?>">
    <title><?= h($productName) ?> | <?= h($siteName) ?></title>
    <link rel="stylesheet" href="<?= h(local_path('assets/style.css?v=1.2.0')) ?>">
    <script src="<?= h(local_path('assets/app.js?v=1.2.0')) ?>" defer></script>
</head>
<body>
<main class="payment-page">
    <header class="site-header">
        <a class="brand" href="<?= h(local_path()) ?>" aria-label="صفحه اصلی <?= h($siteName) ?>">
            <span class="brand-mark" aria-hidden="true">د</span>
            <strong><?= h($siteName) ?></strong>
        </a>
        <span class="secure-badge"><i aria-hidden="true"></i> پرداخت امن</span>
    </header>

    <section class="checkout-card" aria-labelledby="page-title">
        <div class="card-heading">
            <span>پرداخت سفارش</span>
            <h1 id="page-title"><?= h($productName) ?></h1>
            <p><?= h($description) ?></p>
        </div>

        <div class="product-row">
            <div class="product-mark" aria-hidden="true">۱۰</div>
            <div class="product-info">
                <strong><?= h($productName) ?></strong>
                <small>تعداد: ۱</small>
            </div>
            <strong class="product-price"><?= h(toman($defaultAmount)) ?></strong>
        </div>

        <?php if ($error !== null): ?>
            <div class="alert" role="alert"><span aria-hidden="true">!</span><?= h($error) ?></div>
        <?php endif; ?>

        <div class="order-total">
            <span>مبلغ قابل پرداخت</span>
            <strong><?= h(toman($defaultAmount)) ?></strong>
        </div>

        <form action="<?= h(local_path('create-payment.php')) ?>" method="post" data-payment-form>
            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
            <button class="pay-button" type="submit" data-submit-button>
                <span>پرداخت <?= h(toman($defaultAmount)) ?></span>
                <i aria-hidden="true">←</i>
            </button>
        </form>

        <p class="payment-note">این پرداخت واقعی است و مبلغ از حساب شما کسر می‌شود.</p>

        <?php if ($missingSettings !== []): ?>
            <div class="setup-note">تنظیمات ناقص: <?= h(implode('، ', $missingSettings)) ?></div>
        <?php endif; ?>
    </section>

    <footer class="site-footer">پس از پرداخت، نتیجه در همین صفحه نمایش داده می‌شود.</footer>
</main>
</body>
</html>

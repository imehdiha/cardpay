<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/CardPayClient.php';

$siteName = (string) ($config['site_name'] ?? 'دفتر');
$orderId = (string) ($_GET['order'] ?? '');
$order = null;

if (preg_match('/^QP-[A-Z0-9-]{10,40}$/', $orderId)) {
    $candidate = $_SESSION['quick_pay_orders'][$orderId] ?? null;
    if (is_array($candidate)) {
        $order = $candidate;
    }
}

$status = 'unknown';
$payableAmount = is_array($order) ? (int) ($order['payable_amount_rial'] ?? 0) : 0;
$paymentUrl = is_array($order) ? (string) ($order['payment_url'] ?? '') : '';
$verificationError = false;

if (is_array($order) && cardpay_is_configured($config)) {
    try {
        $client = new CardPayClient(
            (string) $config['cardpay_url'],
            (string) $config['public_key'],
            (string) $config['secret'],
        );
        $response = $client->verify((string) $order['payment_id']);
        $payment = $response['data'] ?? null;
        if (is_array($payment)) {
            $status = (string) ($payment['status'] ?? 'unknown');
            $payableAmount = (int) ($payment['payable_amount'] ?? $payableAmount);
        }
    } catch (Throwable $exception) {
        error_log('[quick-pay verify] ' . $exception->getMessage());
        $status = 'pending';
        $verificationError = true;
    }
}

$isPaid = $status === 'paid';
$isPending = in_array($status, ['pending', 'manual_review'], true);
$isFailed = in_array($status, ['expired', 'canceled', 'rejected'], true);

if ($isPending) {
    header('Refresh: 6');
}
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#f4f5f7">
    <title>نتیجهٔ پرداخت | <?= h($siteName) ?></title>
    <link rel="stylesheet" href="<?= h(local_path('assets/style.css?v=1.2.0')) ?>">
</head>
<body class="result-body">
<main class="result-card">
    <a class="brand result-brand" href="<?= h(local_path()) ?>">
        <span class="brand-mark" aria-hidden="true">د</span>
        <span><strong><?= h($siteName) ?></strong><small>نتیجهٔ پرداخت</small></span>
    </a>

    <?php if ($isPaid): ?>
        <div class="result-icon success" aria-hidden="true">✓</div>
        <span class="result-kicker success">موفق</span>
        <h1>پرداخت تأیید شد</h1>
        <p>پرداخت شما با موفقیت ثبت شد.</p>
    <?php elseif ($isPending): ?>
        <div class="result-icon pending" aria-hidden="true"><i></i></div>
        <span class="result-kicker pending">در انتظار تأیید</span>
        <h1><?= $verificationError ? 'در حال بررسی دوباره' : 'پرداخت در حال بررسی است' ?></h1>
        <p>وضعیت پرداخت به‌صورت خودکار بررسی می‌شود.</p>
    <?php elseif ($isFailed): ?>
        <div class="result-icon failed" aria-hidden="true">×</div>
        <span class="result-kicker failed">ناموفق</span>
        <h1>پرداخت انجام نشد</h1>
        <p>برای تلاش دوباره به صفحه قبل برگردید.</p>
    <?php else: ?>
        <div class="result-icon unknown" aria-hidden="true">؟</div>
        <span class="result-kicker unknown">نامشخص</span>
        <h1>نتیجه پرداخت پیدا نشد</h1>
        <p>نتیجه را در همان مرورگری باز کنید که پرداخت با آن انجام شده است.</p>
    <?php endif; ?>

    <?php if ($payableAmount > 0): ?>
        <div class="result-amount"><span>مبلغ نهایی پرداخت</span><strong><?= h(rial($payableAmount)) ?></strong></div>
    <?php endif; ?>

    <div class="result-actions">
        <?php if ($isPending && $paymentUrl !== ''): ?>
            <a class="pay-button result-primary" href="<?= h($paymentUrl) ?>">بازگشت به صفحهٔ پرداخت <i>←</i></a>
        <?php endif; ?>
        <a class="text-link" href="<?= h(local_path()) ?>"><?= $isPaid ? 'بازگشت به صفحه پرداخت' : 'تلاش دوباره' ?> <span>←</span></a>
    </div>

    <?php if ($isPending): ?><small class="refresh-note"><i></i> بررسی خودکار نتیجه فعال است</small><?php endif; ?>
</main>
</body>
</html>

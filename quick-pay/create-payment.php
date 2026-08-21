<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/CardPayClient.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect_to(local_path());
}

if (!csrf_is_valid($_POST['csrf'] ?? null)) {
    flash_error('صفحه منقضی شده است؛ لطفاً دوباره تلاش کنید.');
    redirect_to(local_path());
}

$lastAttempt = (int) ($_SESSION['quick_pay_last_attempt'] ?? 0);
if ($lastAttempt > time() - 4) {
    flash_error('چند ثانیه صبر کنید و دوباره دکمهٔ پرداخت را بزنید.');
    redirect_to(local_path());
}
$_SESSION['quick_pay_last_attempt'] = time();

if (!cardpay_is_configured($config)) {
    flash_error('تنظیمات اتصال کامل نیست: ' . implode('، ', cardpay_missing_settings($config)));
    redirect_to(local_path());
}

$defaultAmount = (int) ($config['default_amount_toman'] ?? 10_000);
$amountToman = $defaultAmount;
$amountRial = $amountToman * 10;
$orderId = 'QP-' . gmdate('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(3)));
$returnUrl = public_folder_url($config) . '/result.php?order=' . rawurlencode($orderId);
$description = (string) ($config['product_name'] ?? 'خرید آزمایشی');

try {
    $client = new CardPayClient(
        (string) $config['cardpay_url'],
        (string) $config['public_key'],
        (string) $config['secret'],
    );
    $response = $client->createPayment([
        'amount' => $amountRial,
        'external_order_id' => $orderId,
        'description' => $description,
        'return_url' => $returnUrl,
        'metadata' => [
            'source' => 'quick-pay',
        ],
    ], 'quick-pay-' . strtolower($orderId));

    $payment = $response['data'] ?? null;
    $paymentId = is_array($payment) ? (string) ($payment['payment_id'] ?? '') : '';
    $paymentUrl = is_array($payment) ? (string) ($payment['payment_url'] ?? '') : '';

    if ($paymentId === '' || filter_var($paymentUrl, FILTER_VALIDATE_URL) === false) {
        throw new RuntimeException('پاسخ ساخت پرداخت کامل نبود.');
    }

    $scheme = strtolower((string) parse_url($paymentUrl, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        throw new RuntimeException('آدرس صفحهٔ پرداخت معتبر نبود.');
    }

    $_SESSION['quick_pay_orders'][$orderId] = [
        'payment_id' => $paymentId,
        'payment_url' => $paymentUrl,
        'amount_toman' => $amountToman,
        'payable_amount_rial' => (int) ($payment['payable_amount'] ?? $amountRial),
        'created_at' => time(),
    ];
    $_SESSION['quick_pay_orders'] = array_slice(
        (array) $_SESSION['quick_pay_orders'],
        -10,
        null,
        true
    );

    redirect_to($paymentUrl);
} catch (Throwable $exception) {
    error_log('[quick-pay] ' . $exception->getMessage());
    $message = 'ساخت پرداخت انجام نشد.';
    if (($config['show_gateway_errors'] ?? true) === true) {
        $detail = trim(preg_replace('/\s+/u', ' ', $exception->getMessage()) ?? '');
        if ($detail !== '') {
            $shortDetail = function_exists('mb_substr') ? mb_substr($detail, 0, 220) : substr($detail, 0, 220);
            $message .= ' خطای درگاه: ' . $shortDetail;
        }
    }
    flash_error($message);
    redirect_to(local_path());
}

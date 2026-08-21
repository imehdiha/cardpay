<?php
declare(strict_types=1);

// این مقدار را بیرون از Document Root و کنار Secret اتصال سایت نگه دارید.
$cardPaySecret = getenv('CARDPAY_SECRET') ?: '';
$rawBody = file_get_contents('php://input') ?: '';
$receivedSignature = $_SERVER['HTTP_X_CARDPAY_SIGNATURE'] ?? '';

if ($cardPaySecret === '' || $receivedSignature === '') {
    http_response_code(401);
    exit('Missing CardPay credentials');
}

$expectedSignature = hash_hmac('sha256', $rawBody, $cardPaySecret);
if (!hash_equals($expectedSignature, $receivedSignature)) {
    http_response_code(401);
    exit('Invalid signature');
}

try {
    $event = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    http_response_code(400);
    exit('Invalid JSON');
}

$eventId = (string) ($event['event_id'] ?? '');
$eventType = (string) ($event['event_type'] ?? '');
$payment = is_array($event['payment'] ?? null) ? $event['payment'] : [];

// event_id را در دیتابیس فروشگاه UNIQUE کنید. اگر قبلاً ثبت شده، فقط 204 بدهید.
// نمونه: if (webhookAlreadyHandled($eventId)) { http_response_code(204); exit; }
if ($eventId === '') {
    http_response_code(400);
    exit('Missing event id');
}

if ($eventType === 'payment.paid') {
    $orderId = (string) ($payment['external_order_id'] ?? '');
    $paymentId = (string) ($payment['payment_id'] ?? '');
    $paidAmount = (int) ($payment['payable_amount'] ?? 0);

    // سفارش خودتان را فقط پس از تطبیق orderId و مبلغ، پرداخت‌شده کنید.
    // markOrderAsPaid($orderId, $paymentId, $paidAmount, $eventId);
}

http_response_code(204);

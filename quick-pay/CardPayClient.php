<?php
declare(strict_types=1);

final class CardPayClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $publicKey,
        private readonly string $secret,
    ) {}

    /** @param array<string, mixed> $data
     *  @return array<string, mixed>
     */
    public function createPayment(array $data, string $idempotencyKey): array
    {
        return $this->request('POST', '/api/v1/payments', $data, [
            'Idempotency-Key: ' . $idempotencyKey,
        ]);
    }

    /** @return array<string, mixed> */
    public function verify(string $paymentId): array
    {
        return $this->request(
            'POST',
            '/api/v1/payments/' . rawurlencode($paymentId) . '/verify',
            []
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string> $extraHeaders
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $data, array $extraHeaders = []): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('افزونهٔ cURL روی هاست فعال نیست.');
        }

        $body = $data === []
            ? ''
            : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $timestamp = (string) time();
        $nonce = bin2hex(random_bytes(16));
        $canonical = implode("\n", [
            $method,
            $path,
            '',
            hash('sha256', $body),
            $timestamp,
            $nonce,
        ]);
        $signature = hash_hmac('sha256', $canonical, $this->secret);

        $handle = curl_init(rtrim($this->baseUrl, '/') . $path);
        if ($handle === false) {
            throw new RuntimeException('ارتباط با درگاه ساخته نشد.');
        }

        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 18,
            CURLOPT_TIMEOUT => 40,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_DNS_CACHE_TIMEOUT => 300,
            CURLOPT_NOSIGNAL => true,
            CURLOPT_HTTPHEADER => array_merge([
                'Content-Type: application/json',
                'Accept: application/json',
                'X-CardPay-Key: ' . $this->publicKey,
                'X-CardPay-Timestamp: ' . $timestamp,
                'X-CardPay-Nonce: ' . $nonce,
                'X-CardPay-Signature: ' . $signature,
            ], $extraHeaders),
        ]);

        $raw = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($handle);
        curl_close($handle);

        if ($raw === false) {
            throw new RuntimeException($curlError !== '' ? $curlError : 'پاسخی از درگاه دریافت نشد.');
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new RuntimeException('پاسخ درگاه قابل خواندن نبود.');
        }

        if (!is_array($decoded)) {
            throw new RuntimeException('ساختار پاسخ درگاه معتبر نبود.');
        }

        if ($status >= 400 || !($decoded['success'] ?? false)) {
            $message = $decoded['error']['message'] ?? 'درگاه درخواست را نپذیرفت.';
            throw new RuntimeException(is_string($message) ? $message : 'درگاه درخواست را نپذیرفت.');
        }

        return $decoded;
    }
}

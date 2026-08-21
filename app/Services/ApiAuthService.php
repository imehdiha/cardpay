<?php
declare(strict_types=1);

namespace CardPay\Services;

use CardPay\Core\Config;
use CardPay\Core\Crypto;
use CardPay\Core\Database;
use CardPay\Core\DomainException;
use CardPay\Core\Request;
use CardPay\Repositories\ApplicationRepository;
use PDOException;

final class ApiAuthService
{
    public function __construct(private readonly Database $db, private readonly ApplicationRepository $apps, private readonly Crypto $crypto, private readonly Config $config) {}
    public function authenticate(Request $request): array
    {
        $key = trim((string) $request->header('x-cardpay-key', ''));
        $timestamp = (string) $request->header('x-cardpay-timestamp', '');
        $nonce = trim((string) $request->header('x-cardpay-nonce', ''));
        $signature = strtolower(trim((string) $request->header('x-cardpay-signature', '')));
        if ($key === '' || $timestamp === '' || $nonce === '' || $signature === '') throw new DomainException('invalid_api_key', 'API authentication headers are required.', 401);
        $application = $this->apps->findAuthenticated($key);
        if (!$application) throw new DomainException('invalid_api_key', 'API key is invalid or inactive.', 401);
        $tolerance = (int) $this->config->get('security.api_timestamp_tolerance', 300);
        if (!ctype_digit($timestamp) || abs(time() - (int) $timestamp) > $tolerance) throw new DomainException('invalid_signature', 'Request timestamp is outside the allowed window.', 401);
        if (strlen($nonce) < 12 || strlen($nonce) > 190) throw new DomainException('invalid_signature', 'Nonce is invalid.', 401);
        $secret = $this->crypto->decrypt($application['secret_encrypted']);
        $expected = hash_hmac('sha256', self::canonical($request, $timestamp, $nonce), $secret);
        if (!hash_equals($expected, $signature)) throw new DomainException('invalid_signature', 'Request signature is invalid.', 401);
        try {
            $this->db->insert('INSERT INTO ' . $this->db->table('api_nonces') . ' (application_id,nonce,expires_at,created_at) VALUES (?,?,?,?)', [$application['id'], $nonce, gmdate('Y-m-d H:i:s', time() + $tolerance), gmdate('Y-m-d H:i:s')]);
        } catch (PDOException) { throw new DomainException('invalid_signature', 'Request nonce has already been used.', 401); }
        $this->apps->touchKey((int) $application['api_key_id']);
        unset($application['secret_encrypted']);
        return $application;
    }
    public static function canonical(Request $request, string $timestamp, string $nonce): string
    {
        return implode("\n", [$request->method, $request->path, $request->rawQuery, hash('sha256', $request->rawBody), $timestamp, $nonce]);
    }
}

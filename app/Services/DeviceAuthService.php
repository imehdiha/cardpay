<?php
declare(strict_types=1);

namespace CardPay\Services;

use CardPay\Core\Config;
use CardPay\Core\Crypto;
use CardPay\Core\Database;
use CardPay\Core\DomainException;
use CardPay\Core\Request;
use CardPay\Repositories\DeviceRepository;
use PDOException;

final class DeviceAuthService
{
    public function __construct(private readonly Database $db, private readonly DeviceRepository $devices, private readonly Crypto $crypto, private readonly Config $config) {}
    public function authenticateShortcut(Request $request): array
    {
        $key=trim((string)$request->header('x-device-key',''));
        $secret=(string)$request->header('x-device-secret','');
        // Some iOS Shortcuts releases display custom headers in the editor but
        // omit them from the actual request. Accept credentials in JSON too.
        // Headers still take precedence when both forms are present.
        if($key==='')$key=trim((string)$request->input('device_key',''));
        if($secret==='')$secret=(string)$request->input('device_secret','');
        if($key===''||$secret===''||strlen($secret)>190)throw new DomainException('invalid_device_key','Device key and secret are required.',401);
        $device=$this->devices->findAuthenticated($key);
        if(!$device||!hash_equals((string)$device['secret_fingerprint'],hash('sha256',$secret)))throw new DomainException('invalid_device_key','Device credentials are invalid.',401);
        unset($device['device_secret_encrypted'],$device['secret_fingerprint']);return$device;
    }
    public function authenticate(Request $request): array
    {
        $key = trim((string) $request->header('x-device-key', '')); $timestamp = (string) $request->header('x-device-timestamp', '');
        $nonce = trim((string) $request->header('x-device-nonce', '')); $signature = strtolower(trim((string) $request->header('x-device-signature', '')));
        $device = $this->devices->findAuthenticated($key);
        if (!$device) throw new DomainException('invalid_device_key', 'Device credentials are invalid.', 401);
        $tolerance = (int) $this->config->get('security.api_timestamp_tolerance', 300);
        if (!ctype_digit($timestamp) || abs(time() - (int) $timestamp) > $tolerance || strlen($nonce) < 12) throw new DomainException('invalid_device_signature', 'Device timestamp or nonce is invalid.', 401);
        $secret = $this->crypto->decrypt($device['device_secret_encrypted']);
        $expected = hash_hmac('sha256', ApiAuthService::canonical($request, $timestamp, $nonce), $secret);
        if (!hash_equals($expected, $signature)) throw new DomainException('invalid_device_signature', 'Device signature is invalid.', 401);
        try { $this->db->insert('INSERT INTO ' . $this->db->table('device_nonces') . ' (device_id,nonce,expires_at,created_at) VALUES (?,?,?,?)', [$device['id'], $nonce, gmdate('Y-m-d H:i:s', time() + $tolerance), gmdate('Y-m-d H:i:s')]); }
        catch (PDOException) { throw new DomainException('invalid_device_signature', 'Device nonce has already been used.', 401); }
        unset($device['device_secret_encrypted']);
        return $device;
    }
}

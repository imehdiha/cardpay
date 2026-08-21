<?php
declare(strict_types=1);

namespace CardPay\Services;

use CardPay\Core\Crypto;
use CardPay\Core\Database;
use CardPay\Core\Id;

final class WebhookService
{
    private const RETRY_MINUTES = [0, 1, 5, 15, 60];
    public function __construct(private readonly Database $db, private readonly Crypto $crypto) {}
    public function emit(array $payment, string $eventType): ?int
    {
        $url = $payment['webhook_url'] ?? $payment['callback_url'] ?? null;
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL) || !in_array(parse_url($url, PHP_URL_SCHEME), ['http','https'], true)) return null;
        $events = $this->db->table('webhook_events');
        $existing = $this->db->fetch("SELECT id FROM {$events} WHERE payment_id=? AND event_type=?", [$payment['id'], $eventType]);
        if ($existing) return (int) $existing['id'];
        $eventId = Id::prefixed('evt'); $now = gmdate('c');
        $payload = ['event_id'=>$eventId,'event_type'=>$eventType,'created_at'=>$now,'application_id'=>(int)$payment['application_id'],'payment'=>$this->publicPayment($payment),'metadata'=>json_decode((string)($payment['metadata_json'] ?? '{}'), true) ?: []];
        $id = $this->db->insert("INSERT INTO {$events} (event_id,application_id,payment_id,event_type,payload_json,created_at) VALUES (?,?,?,?,?,?)", [$eventId,$payment['application_id'],$payment['id'],$eventType,json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),gmdate('Y-m-d H:i:s')]);
        $this->db->insert('INSERT INTO ' . $this->db->table('webhook_deliveries') . ' (webhook_event_id,url,attempt,status,next_attempt_at,created_at,updated_at) VALUES (?,?,0,?,?,?,?)', [$id,$url,'pending',gmdate('Y-m-d H:i:s'),gmdate('Y-m-d H:i:s'),gmdate('Y-m-d H:i:s')]);
        return $id;
    }
    public function processDue(int $limit = 3): int
    {
        $d = $this->db->table('webhook_deliveries'); $e = $this->db->table('webhook_events');
        $rows = $this->db->fetchAll("SELECT d.*,e.application_id,e.payload_json FROM {$d} d JOIN {$e} e ON e.id=d.webhook_event_id WHERE d.status IN ('pending','failed') AND d.next_attempt_at<=? ORDER BY d.next_attempt_at ASC LIMIT " . max(1,$limit), [gmdate('Y-m-d H:i:s')]);
        foreach ($rows as $row) $this->deliver($row);
        return count($rows);
    }
    public function retry(int $deliveryId): bool
    {
        $d=$this->db->table('webhook_deliveries');$e=$this->db->table('webhook_events');
        $row=$this->db->fetch("SELECT d.*,e.application_id,e.payload_json FROM {$d} d JOIN {$e} e ON e.id=d.webhook_event_id WHERE d.id=?",[$deliveryId]);
        if(!$row)return false; $this->deliver($row,true); return true;
    }
    private function deliver(array $row, bool $manual = false): void
    {
        $key = $this->db->fetch('SELECT secret_encrypted FROM ' . $this->db->table('application_api_keys') . ' WHERE application_id=? AND is_active=1 AND revoked_at IS NULL ORDER BY id DESC LIMIT 1', [$row['application_id']]);
        if (!$key) return;
        $secret = $this->crypto->decrypt($key['secret_encrypted']); $payload=(string)$row['payload_json']; $signature=self::signature($payload,$secret); $started=microtime(true);
        $ch=curl_init((string)$row['url']); curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$payload,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>8,CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_HTTPHEADER=>['Content-Type: application/json','User-Agent: CardPay-Webhook/1.0','X-CardPay-Signature: '.$signature]]);
        $body=curl_exec($ch);$error=curl_error($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);
        $attempt=(int)$row['attempt']+1;$success=$body!==false&&$status>=200&&$status<300;$next=null;$state=$success?'delivered':'failed';
        if(!$success&&$attempt<count(self::RETRY_MINUTES))$next=gmdate('Y-m-d H:i:s',time()+self::RETRY_MINUTES[$attempt]*60);
        if(!$success&&$next===null)$state='exhausted';
        $this->db->execute('UPDATE '.$this->db->table('webhook_deliveries').' SET attempt=?,status=?,response_status=?,response_body=?,duration_ms=?,error_message=?,next_attempt_at=?,last_attempt_at=?,updated_at=? WHERE id=?',[$attempt,$state,$status,substr((string)$body,0,4000),(int)((microtime(true)-$started)*1000),substr($error,0,500),$next,gmdate('Y-m-d H:i:s'),gmdate('Y-m-d H:i:s'),$row['id']]);
    }
    private function publicPayment(array $p): array { return ['payment_id'=>$p['public_id'],'status'=>$p['status'],'external_order_id'=>$p['external_order_id']??null,'original_amount'=>(int)$p['original_amount'],'token'=>(int)$p['token'],'payable_amount'=>(int)$p['payable_amount'],'currency'=>$p['currency'],'paid_at'=>$p['paid_at']??null,'expires_at'=>$p['expires_at']]; }
    public static function signature(string $payload,string $secret):string{return hash_hmac('sha256',$payload,$secret);}
}

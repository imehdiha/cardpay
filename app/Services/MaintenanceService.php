<?php
declare(strict_types=1);

namespace CardPay\Services;

use CardPay\Core\Database;

final class MaintenanceService
{
    public function __construct(private readonly Database $db,private readonly ExpirationService $expiration,private readonly TokenAllocator $tokens,private readonly WebhookService $webhooks){}
    public function runBudgeted():array
    {
        $expired=$this->expiration->expireDue(20);$released=$this->tokens->releaseDue(100);$webhooks=$this->webhooks->processDue(3);$now=gmdate('Y-m-d H:i:s');
        $this->db->execute('DELETE FROM '.$this->db->table('api_nonces').' WHERE expires_at<?',[$now]);
        $this->db->execute('DELETE FROM '.$this->db->table('device_nonces').' WHERE expires_at<?',[$now]);
        $this->db->execute('DELETE FROM '.$this->db->table('rate_limits').' WHERE expires_at<?',[$now]);
        return compact('expired','released','webhooks');
    }
}

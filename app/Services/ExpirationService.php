<?php
declare(strict_types=1);

namespace CardPay\Services;

use CardPay\Core\Config;
use CardPay\Core\Database;
use CardPay\Repositories\PaymentRepository;

final class ExpirationService
{
    public function __construct(private readonly Database $db,private readonly PaymentRepository $payments,private readonly TokenAllocator $tokens,private readonly WebhookService $webhooks,private readonly Config $config){}
    public function expireDue(int $limit=50):int
    {
        $rows=$this->db->fetchAll('SELECT public_id FROM '.$this->db->table('payments')." WHERE status='pending' AND expires_at<=? ORDER BY expires_at ASC LIMIT ".max(1,$limit),[gmdate('Y-m-d H:i:s')]);$count=0;
        foreach($rows as $row){$payment=$this->payments->findPublic($row['public_id']);if(!$payment)continue;if($this->payments->transition((int)$payment['id'],'pending','expired')){$count++;$this->tokens->cooldown((int)$payment['id'],(int)$this->config->get('payment.token_cooldown_minutes',10));$payment=$this->payments->findPublic($row['public_id']);$this->webhooks->emit($payment,'payment.expired');}}
        return $count;
    }
}

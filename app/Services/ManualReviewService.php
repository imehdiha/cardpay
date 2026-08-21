<?php
declare(strict_types=1);

namespace CardPay\Services;

use CardPay\Core\Config;
use CardPay\Core\Database;
use CardPay\Core\DomainException;
use CardPay\Repositories\PaymentRepository;

final class ManualReviewService
{
    public function __construct(private readonly Database $db,private readonly PaymentRepository $payments,private readonly PaymentStateMachine $states,private readonly TokenAllocator $tokens,private readonly WebhookService $webhooks,private readonly Config $config){}
    public function submit(array $payment,array $input,?string $receiptPath=null):int
    {
        $existing=$this->db->fetch('SELECT id FROM '.$this->db->table('manual_review_requests')." WHERE payment_id=? AND status='pending'",[$payment['id']]);if($existing)return(int)$existing['id'];
        if(!in_array($payment['status'],['pending','expired','manual_review'],true))throw new DomainException('payment_not_reviewable','This payment cannot enter manual review.',409);
        if($payment['status']!=='manual_review'){$this->states->assert($payment['status'],'manual_review');$this->payments->transition((int)$payment['id'],$payment['status'],'manual_review');}
        $amount=isset($input['reported_amount'])?filter_var($input['reported_amount'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]):null;if($amount===false)throw new DomainException('validation_failed','Reported amount is invalid.',422);
        $now=gmdate('Y-m-d H:i:s');$id=$this->db->insert('INSERT INTO '.$this->db->table('manual_review_requests').' (payment_id,reported_amount,approximate_paid_at,contact_mobile,customer_note,receipt_path,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?)',[$payment['id'],$amount,$input['approximate_paid_at']??null,$input['contact_mobile']??$payment['customer_mobile'],$input['customer_note']??null,$receiptPath,'pending',$now,$now]);
        $full=$this->payments->findPublic($payment['public_id']);$this->webhooks->emit($full,'payment.manual_review');return$id;
    }
    public function approve(int $reviewId,int $adminId,?int $smsId,?int $actualAmount,?string $note):void
    {
        $review=$this->db->fetch('SELECT * FROM '.$this->db->table('manual_review_requests').' WHERE id=? AND status=?',[$reviewId,'pending']);if(!$review)throw new DomainException('review_not_found','Manual review was not found.',404);
        $payment=$this->db->fetch('SELECT * FROM '.$this->db->table('payments').' WHERE id=?',[$review['payment_id']]);$this->states->assert($payment['status'],'paid');$now=gmdate('Y-m-d H:i:s');
        $this->db->transaction(function()use($reviewId,$adminId,$smsId,$actualAmount,$note,$payment,$now):void{
            if(!$this->payments->transition((int)$payment['id'],$payment['status'],'paid',['paid_at'=>$now,'matched_sms_id'=>$smsId]))throw new DomainException('payment_already_paid','Payment changed concurrently.',409);
            $this->db->execute('UPDATE '.$this->db->table('manual_review_requests')." SET status='approved',incoming_sms_id=?,actual_amount=?,internal_note=?,reviewed_by=?,reviewed_at=?,updated_at=? WHERE id=? AND status='pending'",[$smsId,$actualAmount,$note,$adminId,$now,$now,$reviewId]);
            if($smsId){$sms=$this->db->fetch('SELECT * FROM '.$this->db->table('incoming_sms').' WHERE id=? AND bank_card_id=?',[$smsId,$payment['bank_card_id']]);if(!$sms)throw new DomainException('invalid_sms','SMS does not belong to the payment card.',422);$this->db->execute('UPDATE '.$this->db->table('incoming_sms')." SET match_status='matched',matched_payment_id=?,used_at=? WHERE id=?",[$payment['id'],$now,$smsId]);$this->db->insert('INSERT INTO '.$this->db->table('payment_matches').' (payment_id,incoming_sms_id,match_type,confidence,decided_by,notes,created_at) VALUES (?,?,?,?,?,?,?)',[$payment['id'],$smsId,'manual','manual',$adminId,$note,$now]);}
        });
        $this->tokens->cooldown((int)$payment['id'],(int)$this->config->get('payment.token_cooldown_minutes',10));$full=$this->payments->findPublic($payment['public_id']);$this->webhooks->emit($full,'payment.paid');$this->webhooks->processDue(1);
    }
    public function reject(int $reviewId,int $adminId,?string $note):void
    {
        $review=$this->db->fetch('SELECT * FROM '.$this->db->table('manual_review_requests').' WHERE id=? AND status=?',[$reviewId,'pending']);if(!$review)throw new DomainException('review_not_found','Manual review was not found.',404);$payment=$this->db->fetch('SELECT * FROM '.$this->db->table('payments').' WHERE id=?',[$review['payment_id']]);$this->states->assert($payment['status'],'rejected');$now=gmdate('Y-m-d H:i:s');$this->payments->transition((int)$payment['id'],$payment['status'],'rejected');$this->db->execute('UPDATE '.$this->db->table('manual_review_requests')." SET status='rejected',internal_note=?,reviewed_by=?,reviewed_at=?,updated_at=? WHERE id=?",[$note,$adminId,$now,$now,$reviewId]);$this->tokens->cooldown((int)$payment['id'],(int)$this->config->get('payment.token_cooldown_minutes',10));$full=$this->payments->findPublic($payment['public_id']);$this->webhooks->emit($full,'payment.rejected');
    }
}

<?php
declare(strict_types=1);

namespace CardPay\Services;

use CardPay\Core\Config;
use CardPay\Core\Database;
use CardPay\Core\DomainException;
use CardPay\Repositories\DeviceRepository;
use CardPay\Repositories\PaymentRepository;
use DateTimeImmutable;
use DateTimeZone;
use PDOException;

final class SmsService
{
    public function __construct(
        private readonly Database $db, private readonly DeviceRepository $devices, private readonly PaymentRepository $payments,
        private readonly SmsParserService $parser, private readonly PaymentStateMachine $states, private readonly TokenAllocator $tokens,
        private readonly WebhookService $webhooks, private readonly ExpirationService $expiration, private readonly Config $config,
    ) {}

    public function receive(array $device,array $input,string $ip):array
    {
        foreach(['message_id','raw_sms','received_at'] as $field)if(!isset($input[$field])||trim((string)$input[$field])==='')throw new DomainException('validation_failed',"{$field} is required.",422,[$field=>['required']]);
        if(strlen((string)$input['message_id'])>190||strlen((string)$input['raw_sms'])>10000)throw new DomainException('validation_failed','SMS payload is too large.',422);
        try{$received=(new DateTimeImmutable((string)$input['received_at']))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');}catch(\Throwable){throw new DomainException('validation_failed','received_at must be an ISO 8601 date.',422);}
        $this->expiration->expireDue(20);$table=$this->db->table('incoming_sms');
        $duplicate=$this->db->fetch("SELECT * FROM {$table} WHERE device_id=? AND message_id=?",[$device['id'],$input['message_id']]);
        if($duplicate)return $this->duplicateResponse($duplicate);
        $now=gmdate('Y-m-d H:i:s');
        try{$smsId=$this->db->insert("INSERT INTO {$table} (device_id,bank_card_id,message_id,sender,raw_sms,received_at,server_received_at,source_ip,parse_status,match_status,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)",[$device['id'],$device['bank_card_id'],$input['message_id'],$input['sender']??null,$input['raw_sms'],$received,$now,$ip,'pending','unmatched',$now]);}
        catch(PDOException){$row=$this->db->fetch("SELECT * FROM {$table} WHERE device_id=? AND message_id=?",[$device['id'],$input['message_id']]);return $this->duplicateResponse($row);}
        $this->devices->touch((int)$device['id'],$ip);
        $parser=$this->db->fetch('SELECT p.* FROM '.$this->db->table('bank_cards').' b LEFT JOIN '.$this->db->table('sms_parsers').' p ON p.id=b.sms_parser_id AND p.is_active=1 WHERE b.id=?',[$device['bank_card_id']]);
        if(!$parser||empty($parser['id'])){$this->db->execute("UPDATE {$table} SET parse_status='failed',parse_error=? WHERE id=?",['parser_not_configured',$smsId]);return ['sms_id'=>$smsId,'duplicate'=>false,'parse_status'=>'failed','match_status'=>'unmatched','error'=>'parser_not_configured'];}
        if(!empty($parser['sender_pattern'])&&@preg_match((string)$parser['sender_pattern'],(string)($input['sender']??''))!==1){$this->db->execute("UPDATE {$table} SET parse_status='ignored',parse_error=? WHERE id=?",['sender_mismatch',$smsId]);return ['sms_id'=>$smsId,'duplicate'=>false,'parse_status'=>'ignored','match_status'=>'unmatched'];}
        $parsed=$this->parser->parse((string)$input['raw_sms'],$parser,$received);
        if(!$parsed['success']){$this->db->execute("UPDATE {$table} SET parse_status=?,parse_error=? WHERE id=?",[$parsed['type']==='debit'?'ignored':'failed',$parsed['error'],$smsId]);return ['sms_id'=>$smsId,'duplicate'=>false,'parse_status'=>$parsed['type']==='debit'?'ignored':'failed','match_status'=>'unmatched','error'=>$parsed['error']];}
        $this->db->execute("UPDATE {$table} SET parse_status='parsed',parsed_amount=?,parsed_transaction_at=? WHERE id=?",[$parsed['amount'],$parsed['transaction_at'],$smsId]);
        return $this->match($smsId,(int)$device['bank_card_id'],(int)$parsed['amount'],$received);
    }

    private function match(int $smsId,int $cardId,int $amount,string $receivedAt):array
    {
        $paymentTable=$this->db->table('payments');
        $matches=$this->db->fetchAll("SELECT * FROM {$paymentTable} WHERE bank_card_id=? AND payable_amount=? AND status='pending' AND created_at<=? AND expires_at>=? ORDER BY id",[$cardId,$amount,$receivedAt,$receivedAt]);
        if(count($matches)===0)return ['sms_id'=>$smsId,'duplicate'=>false,'parse_status'=>'parsed','match_status'=>'unmatched','payment_id'=>null];
        if(count($matches)>1){
            foreach($matches as $payment){if($this->payments->transition((int)$payment['id'],'pending','manual_review')){$now=gmdate('Y-m-d H:i:s');$this->db->insert('INSERT INTO '.$this->db->table('manual_review_requests').' (payment_id,incoming_sms_id,reported_amount,approximate_paid_at,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?)',[$payment['id'],$smsId,$amount,$receivedAt,'pending',$now,$now]);$full=$this->payments->findPublic($payment['public_id']);$this->webhooks->emit($full,'payment.manual_review');}}
            $this->db->execute("UPDATE ".$this->db->table('incoming_sms')." SET match_status='ambiguous' WHERE id=?",[$smsId]);
            return ['sms_id'=>$smsId,'duplicate'=>false,'parse_status'=>'parsed','match_status'=>'ambiguous','payment_id'=>null];
        }
        $payment=$matches[0];$paidAt=gmdate('Y-m-d H:i:s');
        $paid=$this->db->transaction(function()use($payment,$smsId,$paidAt):bool{
            if(!$this->payments->transition((int)$payment['id'],'pending','paid',['paid_at'=>$paidAt,'matched_sms_id'=>$smsId]))return false;
            $this->db->execute("UPDATE ".$this->db->table('incoming_sms')." SET match_status='matched',matched_payment_id=?,used_at=? WHERE id=?",[$payment['id'],$paidAt,$smsId]);
            $this->db->insert('INSERT INTO '.$this->db->table('payment_matches').' (payment_id,incoming_sms_id,match_type,confidence,created_at) VALUES (?,?,?,?,?)',[$payment['id'],$smsId,'automatic','exact',$paidAt]);return true;
        });
        if(!$paid)return ['sms_id'=>$smsId,'duplicate'=>false,'parse_status'=>'parsed','match_status'=>'manual_review','payment_id'=>null];
        $this->tokens->cooldown((int)$payment['id'],(int)$this->config->get('payment.token_cooldown_minutes',10));$full=$this->payments->findPublic($payment['public_id']);$this->webhooks->emit($full,'payment.paid');$this->webhooks->processDue(1);
        return ['sms_id'=>$smsId,'duplicate'=>false,'parse_status'=>'parsed','match_status'=>'matched','payment_id'=>$payment['public_id']];
    }

    private function duplicateResponse(array $sms):array
    {
        $paymentId=null;
        if(!empty($sms['matched_payment_id'])){
            $payment=$this->db->fetch('SELECT public_id FROM '.$this->db->table('payments').' WHERE id=?',[$sms['matched_payment_id']]);
            $paymentId=$payment['public_id']??null;
        }
        return ['sms_id'=>(int)$sms['id'],'duplicate'=>true,'parse_status'=>$sms['parse_status'],'match_status'=>$sms['match_status'],'payment_id'=>$paymentId];
    }
}

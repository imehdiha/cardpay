<?php
declare(strict_types=1);
namespace CardPay\Tests\Integration;
use CardPay\Tests\TestCase;
final class PaymentFlowTest extends TestCase
{
    public function test_create_is_idempotent_and_verify_returns_same_payment():void{$first=$this->createPayment();$second=$this->createPayment();self::assertSame($first['payment_id'],$second['payment_id']);self::assertFalse($first['idempotent_replay']);self::assertTrue($second['idempotent_replay']);$verified=$this->payments->verify($this->application,$first['payment_id']);self::assertSame('pending',$verified['status']);}
    public function test_create_rejects_callback_url_outside_allowed_domains():void
    {
        $this->db->execute('UPDATE cp_applications SET allowed_domains=? WHERE id=?',['merchant.test',$this->application['id']]);
        $this->application=$this->apps->find((int)$this->application['id']);
        $this->expectException(\CardPay\Core\DomainException::class);
        $this->payments->create($this->application,['amount'=>1000000,'callback_url'=>'http://127.0.0.1/internal'],'idem-callback-domain-01');
    }
    public function test_sms_auto_matches_exactly_once():void{$payment=$this->createPayment();$input=['message_id'=>'sms-unique-1','sender'=>'BANK','raw_sms'=>'واریز: '.number_format($payment['payable_amount']).' ریال','received_at'=>gmdate('c')];$result=$this->sms->receive($this->device,$input,'127.0.0.1');self::assertSame('matched',$result['match_status']);self::assertSame($payment['payment_id'],$result['payment_id']);$duplicate=$this->sms->receive($this->device,$input,'127.0.0.1');self::assertTrue($duplicate['duplicate']);self::assertSame($payment['payment_id'],$duplicate['payment_id']);self::assertSame(1,(int)$this->db->fetch('SELECT COUNT(*) value FROM cp_payment_matches')['value']);self::assertSame('paid',$this->payments->verify($this->application,$payment['payment_id'])['status']);}
    public function test_expired_payment_cannot_be_auto_matched():void{$payment=$this->createPayment();$this->db->execute("UPDATE cp_payments SET expires_at=? WHERE public_id=?",[gmdate('Y-m-d H:i:s',time()-60),$payment['payment_id']]);$this->expiration->expireDue();$input=['message_id'=>'sms-expired-1','sender'=>'BANK','raw_sms'=>'واریز: '.number_format($payment['payable_amount']).' ریال','received_at'=>gmdate('c')];$result=$this->sms->receive($this->device,$input,'127.0.0.1');self::assertSame('unmatched',$result['match_status']);self::assertSame('expired',$this->payments->verify($this->application,$payment['payment_id'])['status']);}
    public function test_manual_review_approval_marks_payment_paid():void{$result=$this->createPayment();$payment=$this->paymentRepo->findPublic($result['payment_id']);$reviewId=$this->reviews->submit($payment,['reported_amount'=>$result['payable_amount'],'contact_mobile'=>'09120000000','customer_note'=>'رسید بررسی شود']);$this->db->insert('INSERT INTO cp_users (name,username,password_hash,role,is_active,created_at,updated_at) VALUES (?,?,?,?,?,?,?)',['Admin','admin',password_hash('very-secure-pass',PASSWORD_DEFAULT),'admin',1,gmdate('Y-m-d H:i:s'),gmdate('Y-m-d H:i:s')]);$this->reviews->approve($reviewId,1,null,$result['payable_amount'],'confirmed');self::assertSame('paid',$this->payments->verify($this->application,$result['payment_id'])['status']);self::assertSame('approved',$this->db->fetch('SELECT status FROM cp_manual_review_requests WHERE id=?',[$reviewId])['status']);}
}

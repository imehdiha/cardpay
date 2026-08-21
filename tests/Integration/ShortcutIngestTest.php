<?php
declare(strict_types=1);

namespace CardPay\Tests\Integration;

use CardPay\Controllers\Api\DeviceApiController;
use CardPay\Core\Request;
use CardPay\Services\DeviceAuthService;
use CardPay\Services\MaintenanceService;
use CardPay\Services\RateLimiter;
use CardPay\Tests\TestCase;

final class ShortcutIngestTest extends TestCase
{
    public function test_shortcut_endpoint_generates_metadata_and_matches_payment(): void
    {
        $this->db->execute('UPDATE cp_sms_parsers SET sender_pattern=NULL');
        $payment=$this->createPayment();
        $body=json_encode(['device_key'=>$this->device['device_key'],'device_secret'=>$this->deviceSecret,'raw_sms'=>'واریز: '.number_format($payment['payable_amount']).' ریال'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $request=new Request('POST','/api/v1/devices/shortcut-sms',$body,[],[],[],['content-type'=>'application/json'],'127.0.0.1','');
        $controller=new DeviceApiController(new DeviceAuthService($this->db,$this->devices,$this->crypto,$this->config),$this->sms,new MaintenanceService($this->db,$this->expiration,$this->tokens,$this->webhooks),new RateLimiter($this->db),$this->config);

        $response=$controller->shortcutIncoming($request);
        $payload=json_decode($response->body,true,512,JSON_THROW_ON_ERROR);
        $sms=$this->db->fetch('SELECT message_id,received_at,sender,parsed_amount,parse_status,match_status,parse_error FROM cp_incoming_sms LIMIT 1');

        self::assertSame(201,$response->status);
        self::assertTrue($payload['success']);
        self::assertSame($payment['payable_amount'],(int)$sms['parsed_amount'],json_encode($sms,JSON_UNESCAPED_UNICODE));
        self::assertSame('matched',$payload['data']['match_status']);
        self::assertSame($payment['payment_id'],$payload['data']['payment_id']);
        self::assertSame('paid',$this->payments->verify($this->application,$payment['payment_id'])['status']);
        self::assertStringStartsWith('ios_',$sms['message_id']);
        self::assertNotSame('',$sms['received_at']);
        self::assertSame('iOS Shortcut',$sms['sender']);
    }
}

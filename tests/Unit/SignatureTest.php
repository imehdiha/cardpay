<?php
declare(strict_types=1);
namespace CardPay\Tests\Unit;
use CardPay\Core\Request;use CardPay\Services\ApiAuthService;use CardPay\Services\WebhookService;use PHPUnit\Framework\TestCase;
final class SignatureTest extends TestCase
{
    public function test_api_canonical_request_is_stable():void{$r=new Request('POST','/api/v1/payments','{"amount":1}',[],[],[],[],'127.0.0.1','a=1');self::assertSame("POST\n/api/v1/payments\na=1\n".hash('sha256','{"amount":1}')."\n1700000000\nnonce-12345678",ApiAuthService::canonical($r,'1700000000','nonce-12345678'));}
    public function test_webhook_signature_uses_sha256_hmac():void{self::assertSame(hash_hmac('sha256','payload','secret'),WebhookService::signature('payload','secret'));}
}

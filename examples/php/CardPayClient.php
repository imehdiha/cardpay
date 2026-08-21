<?php
declare(strict_types=1);

final class CardPayClient
{
    public function __construct(private string $baseUrl,private string $publicKey,private string $secret){}
    public function createPayment(array$data,string$idempotencyKey):array{return$this->request('POST','/api/v1/payments',$data,['Idempotency-Key: '.$idempotencyKey]);}
    public function verify(string$paymentId):array{return$this->request('POST','/api/v1/payments/'.rawurlencode($paymentId).'/verify',[]);}
    private function request(string$method,string$path,array$data,array$extraHeaders=[]):array
    {
        $body=$data===[]?'':json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);$timestamp=(string)time();$nonce=bin2hex(random_bytes(16));$canonical=implode("\n",[$method,$path,'',hash('sha256',$body),$timestamp,$nonce]);$signature=hash_hmac('sha256',$canonical,$this->secret);
        $ch=curl_init(rtrim($this->baseUrl,'/').$path);curl_setopt_array($ch,[CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_POSTFIELDS=>$body,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15,CURLOPT_HTTPHEADER=>array_merge(['Content-Type: application/json','Accept: application/json','X-CardPay-Key: '.$this->publicKey,'X-CardPay-Timestamp: '.$timestamp,'X-CardPay-Nonce: '.$nonce,'X-CardPay-Signature: '.$signature],$extraHeaders)]);$raw=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$error=curl_error($ch);curl_close($ch);if($raw===false)throw new RuntimeException($error);$decoded=json_decode($raw,true,512,JSON_THROW_ON_ERROR);if($status>=400)throw new RuntimeException(($decoded['error']['code']??'api_error').': '.($decoded['error']['message']??'Unknown error'));return$decoded;
    }
}

<?php
declare(strict_types=1);

namespace CardPay\Controllers\Api;

use CardPay\Core\Config;
use CardPay\Core\Request;
use CardPay\Core\Response;
use CardPay\Services\DeviceAuthService;
use CardPay\Services\MaintenanceService;
use CardPay\Services\RateLimiter;
use CardPay\Services\SmsService;

final class DeviceApiController
{
    public function __construct(private readonly DeviceAuthService$auth,private readonly SmsService$sms,private readonly MaintenanceService$maintenance,private readonly RateLimiter$limiter,private readonly Config$config){}
    public function incoming(Request$request):Response{$this->limiter->hit('device_auth',$request->ip,60,60);$device=$this->auth->authenticate($request);$this->limiter->hit('device',(string)$device['id'],(int)$this->config->get('security.device_rate_limit',60),60);$this->maintenance->runBudgeted();$result=$this->sms->receive($device,$request->json(),$request->ip);return Response::success($result,$result['duplicate']?200:201);}
    public function shortcutIncoming(Request$request):Response
    {
        $this->limiter->hit('device_auth',$request->ip,60,60);$device=$this->auth->authenticateShortcut($request);$this->limiter->hit('device',(string)$device['id'],(int)$this->config->get('security.device_rate_limit',60),60);$this->maintenance->runBudgeted();$input=$request->json();$raw=trim((string)($input['raw_sms']??''));
        if($raw!==''){$receivedAt=trim((string)($input['received_at']??''))?:gmdate('c');$input['received_at']=$receivedAt;$input['message_id']=trim((string)($input['message_id']??''))?:'ios_'.hash('sha256',$raw);$input['sender']=trim((string)($input['sender']??''))?:'iOS Shortcut';}
        $result=$this->sms->receive($device,$input,$request->ip);return Response::success($result,$result['duplicate']?200:201);
    }
}

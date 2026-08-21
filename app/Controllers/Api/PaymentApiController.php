<?php
declare(strict_types=1);

namespace CardPay\Controllers\Api;

use CardPay\Core\Config;
use CardPay\Core\Request;
use CardPay\Core\Response;
use CardPay\Services\ApiAuthService;
use CardPay\Services\MaintenanceService;
use CardPay\Services\PaymentService;
use CardPay\Services\RateLimiter;

final class PaymentApiController
{
    public function __construct(private readonly ApiAuthService$auth,private readonly PaymentService$payments,private readonly MaintenanceService$maintenance,private readonly RateLimiter$limiter,private readonly Config$config){}
    public function create(Request$request):Response{$app=$this->guard($request);$data=$this->payments->create($app,$request->json(),$request->header('idempotency-key'));return Response::success($data,$data['idempotent_replay']?200:201);}
    public function show(Request$request,array$params):Response{$app=$this->guard($request);return Response::success($this->payments->status($app,$params['payment_id']));}
    public function verify(Request$request,array$params):Response{$app=$this->guard($request);return Response::success($this->payments->verify($app,$params['payment_id']));}
    public function cancel(Request$request,array$params):Response{$app=$this->guard($request);return Response::success($this->payments->cancel($app,$params['payment_id']));}
    private function guard(Request$request):array{$app=$this->auth->authenticate($request);$this->limiter->hit('api',(string)$app['id'],(int)$this->config->get('security.api_rate_limit',120),60);$this->maintenance->runBudgeted();return$app;}
}

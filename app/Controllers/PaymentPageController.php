<?php
declare(strict_types=1);

namespace CardPay\Controllers;

use CardPay\Core\Config;
use CardPay\Core\Crypto;
use CardPay\Core\Csrf;
use CardPay\Core\Database;
use CardPay\Core\DomainException;
use CardPay\Core\Request;
use CardPay\Core\Response;
use CardPay\Core\Time;
use CardPay\Core\Url;
use CardPay\Core\View;
use CardPay\Repositories\PaymentRepository;
use CardPay\Repositories\SettingsRepository;
use CardPay\Services\MaintenanceService;
use CardPay\Services\ManualReviewService;
use CardPay\Services\RateLimiter;

final class PaymentPageController
{
    public function __construct(private readonly PaymentRepository$payments,private readonly SettingsRepository$settings,private readonly Crypto$crypto,private readonly View$view,private readonly Csrf$csrf,private readonly MaintenanceService$maintenance,private readonly ManualReviewService$reviews,private readonly RateLimiter$limiter,private readonly Config$config,private readonly string$root){}
    public function show(Request$request,array$params):Response
    {
        $this->maintenance->runBudgeted();$payment=$this->payments->findPublic($params['public_id']);if(!$payment)throw new DomainException('payment_not_found','پرداخت پیدا نشد.',404);$payment['card_number']=$this->crypto->decrypt($payment['card_number_encrypted']);$settings=$this->settings->all();
        return new Response($this->view->render('payment/show',['title'=>$settings['payment_title']??'پرداخت امن','payment'=>$payment,'settings'=>$settings,'csrf'=>$this->csrf->token()],'layouts/payment'),200,['Content-Type'=>'text/html; charset=utf-8','Cache-Control'=>'no-store, private']);
    }
    public function status(Request$request,array$params):Response
    {
        $this->limiter->hit('public_status',$request->ip.'|'.$params['public_id'],60,60);$this->maintenance->runBudgeted();$payment=$this->payments->findPublic($params['public_id']);if(!$payment)throw new DomainException('payment_not_found','Payment was not found.',404);
        return Response::success(['payment_id'=>$payment['public_id'],'status'=>$payment['status'],'payable_amount'=>(int)$payment['payable_amount'],'expires_at'=>gmdate('c',strtotime($payment['expires_at'].' UTC')),'paid_at'=>$payment['paid_at']?gmdate('c',strtotime($payment['paid_at'].' UTC')):null,'return_url'=>$payment['return_url']],200,['Cache-Control'=>'no-store, max-age=0']);
    }
    public function report(Request$request,array$params):Response
    {
        if(!$this->csrf->valid((string)$request->input('_csrf')))throw new DomainException('csrf_mismatch','نشست فرم منقضی شده است.',419);$this->limiter->hit('manual_report',$request->ip.'|'.$params['public_id'],5,3600);$payment=$this->payments->findPublic($params['public_id']);if(!$payment)throw new DomainException('payment_not_found','پرداخت پیدا نشد.',404);$input=$request->post;$localTime=trim((string)($input['approximate_paid_at']??''));if($localTime!==''){$input['approximate_paid_at']=Time::localInputToUtc($localTime,(string)$this->settings->get('timezone','Asia/Tehran'));if($input['approximate_paid_at']===null)throw new DomainException('validation_failed','زمان تقریبی واریز معتبر نیست.',422);}$receipt=$this->storeReceipt($request);$this->reviews->submit($payment,$input,$receipt);return Response::redirect(Url::to('/p/'.$payment['public_id'].'?review=submitted'));
    }
    private function storeReceipt(Request$request):?string
    {
        $file=$request->files['receipt']??null;if(!is_array($file)||($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return null;if(($file['error']??1)!==UPLOAD_ERR_OK)throw new DomainException('upload_failed','آپلود رسید انجام نشد.',422);$max=(int)$this->config->get('upload.max_mb',5)*1024*1024;if((int)$file['size']>$max)throw new DomainException('upload_too_large','حجم رسید بیش از حد مجاز است.',422);
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$allowed=['image/jpeg'=>'jpg','image/png'=>'png','application/pdf'=>'pdf'];if(!isset($allowed[$mime]))throw new DomainException('invalid_upload','فقط JPG، PNG یا PDF مجاز است.',422);$name=bin2hex(random_bytes(20)).'.'.$allowed[$mime];$target=$this->root.'/storage/uploads/'.$name;if(!move_uploaded_file($file['tmp_name'],$target))throw new DomainException('upload_failed','ذخیره رسید انجام نشد.',500);return$name;
    }
}

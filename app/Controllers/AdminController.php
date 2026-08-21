<?php
declare(strict_types=1);

namespace CardPay\Controllers;

use CardPay\Core\Config;
use CardPay\Core\Csrf;
use CardPay\Core\DomainException;
use CardPay\Core\Request;
use CardPay\Core\Response;
use CardPay\Core\Session;
use CardPay\Core\Url;
use CardPay\Core\View;
use CardPay\Repositories\SettingsRepository;
use CardPay\Services\AdminService;
use CardPay\Services\AuditService;
use CardPay\Services\MaintenanceService;
use CardPay\Services\ManualReviewService;
use CardPay\Services\PaymentService;
use CardPay\Services\SmsParserService;
use CardPay\Services\WebhookService;
use CardPay\Services\UploadService;

final class AdminController
{
    public function __construct(private readonly AdminService$admin,private readonly SettingsRepository$settings,private readonly View$view,private readonly Session$session,private readonly Csrf$csrf,private readonly MaintenanceService$maintenance,private readonly ManualReviewService$reviews,private readonly WebhookService$webhooks,private readonly AuditService$audit,private readonly UploadService$uploads,private readonly PaymentService$payments,private readonly Config$config,private readonly string$root){}
    public function dashboard(Request$r):Response{$this->maintenance->runBudgeted();return$this->page('admin/dashboard','داشبورد','dashboard',$this->admin->dashboard());}
    public function setup(Request$r):Response{return$this->page('admin/setup','راه‌اندازی فروشگاه','setup',$this->admin->siteOverview());}
    public function createTestPayment(Request$r):Response
    {
        $this->check($r);$overview=$this->admin->siteOverview();$site=$overview['site'];$card=$overview['card'];
        if(!$site||!$card)throw new DomainException('setup_incomplete','ابتدا اتصال سایت و کارت مقصد را کامل کنید.',422);
        $amount=filter_var($r->input('amount'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1000]]);
        if($amount===false)throw new DomainException('validation_failed','مبلغ آزمایشی باید حداقل ۱٬۰۰۰ ریال باشد.',422);
        $result=$this->payments->create($site,['amount'=>(int)$amount,'bank_card_id'=>(int)$card['id'],'external_order_id'=>'TEST-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(2)),'description'=>'پرداخت آزمایشی از پنل','customer'=>['name'=>trim((string)$r->input('customer_name'))?:'مشتری آزمایشی']], 'admin-test-'.bin2hex(random_bytes(16)));
        return Response::redirect(Url::to('/p/'.$result['payment_id']));
    }
    public function connection(Request$r):Response{$credentials=$this->session->get('one_time_credentials');$this->session->forget('one_time_credentials');return$this->page('admin/connection','اتصال به سایت','connection',$this->admin->siteOverview()+['credentials'=>$credentials]);}
    public function createConnection(Request$r):Response{$this->check($r);$result=$this->admin->createPrimaryApplication($r->post);$this->session->put('one_time_credentials',['type'=>'application','public_key'=>$result['application']['public_key'],'secret'=>$result['secret']]);$this->flash('success','اتصال اصلی سایت ساخته شد. Secret را همین حالا ذخیره کنید.');return Response::redirect(Url::to('/admin/connection'));}
    public function saveConnection(Request$r):Response{$this->check($r);$this->admin->updatePrimaryApplication($r->post);$this->flash('success','تنظیمات اتصال سایت ذخیره شد.');return Response::redirect(Url::to('/admin/connection'));}
    public function rotateConnection(Request$r):Response{$this->check($r);$site=$this->admin->primaryApplication();if(!$site)throw new DomainException('not_found','اتصال سایت هنوز ساخته نشده است.',404);$credentials=$this->admin->rotateApplication((int)$site['id']);$this->session->put('one_time_credentials',['type'=>'application']+$credentials);$this->flash('success','کلید اتصال تازه ساخته شد. Secret قبلی از این لحظه معتبر نیست.');return Response::redirect(Url::to('/admin/connection'));}
    public function applications(Request$r):Response{return$this->resource('پروژه‌ها','applications',$this->admin->applications(),['name'=>'نام','slug'=>'Slug','public_key'=>'Public key','is_active'=>'وضعیت'],Url::to('/admin/applications/create'),'application');}
    public function applicationForm(Request$r,array$p=[]):Response{$app=isset($p['id'])?$this->admin->application((int)$p['id']):null;return$this->page('admin/application_form',$app?'ویرایش پروژه':'پروژه جدید','applications',['application'=>$app,'cards'=>$this->admin->cards()]);}
    public function saveApplication(Request$r,array$p=[]):Response{$this->check($r);if(isset($p['id'])){$this->admin->updateApplication((int)$p['id'],$r->post);$id=(int)$p['id'];$this->flash('success','پروژه به‌روزرسانی شد.');}else{$result=$this->admin->createApplication($r->post);$id=(int)$result['application']['id'];$this->session->put('one_time_credentials',['type'=>'application','public_key'=>$result['application']['public_key'],'secret'=>$result['secret']]);}return Response::redirect(Url::to('/admin/applications/'.$id.'/credentials'));}
    public function applicationCredentials(Request$r,array$p):Response{$app=$this->admin->application((int)$p['id']);if(!$app)throw new DomainException('not_found','پروژه پیدا نشد.',404);$credentials=$this->session->get('one_time_credentials');$this->session->forget('one_time_credentials');return$this->page('admin/credentials','کلیدهای API','applications',['entity'=>$app,'credentials'=>$credentials,'kind'=>'application']);}
    public function rotateApplication(Request$r,array$p):Response{$this->check($r);$app=$this->admin->application((int)$p['id']);$credentials=$this->admin->rotateApplication((int)$p['id']);$this->session->put('one_time_credentials',['type'=>'application']+$credentials);return Response::redirect(Url::to('/admin/applications/'.$p['id'].'/credentials'));}
    public function cards(Request$r):Response{return$this->resource('کارت‌های بانکی','cards',$this->admin->cards(),['title'=>'عنوان','bank_name'=>'بانک','card_number_last_four'=>'چهار رقم آخر','is_active'=>'وضعیت'],Url::to('/admin/cards/create'),'card');}
    public function cardForm(Request$r,array$p=[]):Response{$card=isset($p['id'])?$this->admin->card((int)$p['id']):null;return$this->page('admin/card_form',$card?'ویرایش کارت':'کارت جدید','cards',['card'=>$card,'parsers'=>$this->admin->parsers()]);}
    public function saveCard(Request$r,array$p=[]):Response{$this->check($r);if(isset($p['id'])){$this->admin->updateCard((int)$p['id'],$r->post);$this->flash('success','کارت به‌روزرسانی شد.');}else{$this->admin->createCard($r->post);$this->flash('success','کارت بانکی افزوده شد.');}return Response::redirect(Url::to('/admin/cards'));}
    public function devices(Request$r):Response{return$this->resource('دستگاه‌ها','devices',$this->admin->devices(),['name'=>'نام','platform'=>'سیستم','card_title'=>'کارت','last_seen_at'=>'آخرین اتصال','sms_count'=>'SMS','is_active'=>'وضعیت'],Url::to('/admin/devices/create'),'device');}
    public function deviceForm(Request$r):Response{return$this->page('admin/device_form','دستگاه جدید','devices',['cards'=>$this->admin->cards()]);}
    public function saveDevice(Request$r):Response{$this->check($r);$result=$this->admin->createDevice($r->post);$this->session->put('one_time_credentials',['type'=>'device','public_key'=>$result['device']['device_key'],'secret'=>$result['secret']]);return Response::redirect(Url::to('/admin/devices/'.$result['device']['id'].'/setup'));}
    public function deviceSetup(Request$r,array$p):Response{$all=$this->admin->devices();$device=null;foreach($all as$item)if((int)$item['id']===(int)$p['id'])$device=$item;if(!$device)throw new DomainException('not_found','Device پیدا نشد.',404);$credentials=$this->session->get('one_time_credentials');$this->session->forget('one_time_credentials');return$this->page('admin/credentials','راه‌اندازی Device','devices',['entity'=>$device,'credentials'=>$credentials,'kind'=>'device']);}
    public function rotateDevice(Request$r,array$p):Response{$this->check($r);$result=$this->admin->rotateDevice((int)$p['id']);$this->session->put('one_time_credentials',['type'=>'device','public_key'=>$result['device']['device_key'],'secret'=>$result['secret']]);return Response::redirect(Url::to('/admin/devices/'.$p['id'].'/setup'));}
    public function revokeDevice(Request$r,array$p):Response{$this->check($r);$this->admin->revokeDevice((int)$p['id']);$this->flash('success','Device غیرفعال شد.');return Response::redirect(Url::to('/admin/devices'));}
    public function parsers(Request$r):Response{return$this->resource('Parserهای پیامک','parsers',$this->admin->parsers(),['name'=>'نام','bank_name'=>'بانک','sender_pattern'=>'Sender','is_active'=>'وضعیت'],Url::to('/admin/parsers/create'),'parser');}
    public function parserForm(Request$r,array$p=[]):Response{$parser=isset($p['id'])?$this->admin->parser((int)$p['id']):null;return$this->page('admin/parser_form',$parser?'ویرایش Parser':'Parser جدید','parsers',['parser'=>$parser,'testResult'=>null]);}
    public function saveParser(Request$r,array$p=[]):Response{$this->check($r);$this->admin->saveParser($r->post,isset($p['id'])?(int)$p['id']:null);$this->flash('success','Parser ذخیره شد.');return Response::redirect(Url::to('/admin/parsers'));}
    public function testParser(Request$r):Response{$this->check($r);return$this->page('admin/parser_form','آزمایش Parser','parsers',['parser'=>$r->post,'testResult'=>$this->admin->testParser($r->post)]);}
    public function payments(Request$r):Response{return$this->page('admin/payments','پرداخت‌ها','payments',['rows'=>$this->admin->paymentList($r->query),'filters'=>$r->query]);}
    public function payment(Request$r,array$p):Response{return$this->page('admin/payment_detail','جزئیات پرداخت','payments',$this->admin->paymentDetail($p['id']));}
    public function sms(Request$r):Response{return$this->page('admin/sms','پیامک‌ها و بررسی','sms',['rows'=>$this->admin->smsList(false),'unmatched'=>false]);}
    public function unmatched(Request$r):Response{return$this->page('admin/sms','پیامک‌ها و بررسی','sms',['rows'=>$this->admin->smsList(true),'unmatched'=>true]);}
    public function smsDetail(Request$r,array$p):Response{$sms=$this->admin->smsDetail((int)$p['id']);if(!$sms)throw new DomainException('not_found','پیامک پیدا نشد.',404);return$this->page('admin/sms_detail','جزئیات پیامک','sms',['sms'=>$sms]);}
    public function reviewList(Request$r):Response{return$this->page('admin/reviews','پیامک‌ها و بررسی','sms',['rows'=>$this->admin->reviews()]);}
    public function review(Request$r,array$p):Response{$review=$this->admin->review((int)$p['id']);if(!$review)throw new DomainException('not_found','درخواست پیدا نشد.',404);return$this->page('admin/review_detail','بررسی پرداخت','sms',['review'=>$review,'smsRows'=>$this->admin->unmatchedSmsForCard((int)$review['bank_card_id'])]);}
    public function approveReview(Request$r,array$p):Response{$this->check($r);$this->reviews->approve((int)$p['id'],(int)$this->session->get('user_id'),(int)$r->input('sms_id')?:null,(int)$r->input('actual_amount')?:null,(string)$r->input('internal_note'));$this->audit->record('manual_review.approved','manual_review',$p['id'],[],[],(int)$this->session->get('user_id'));$this->flash('success','پرداخت به‌صورت دستی تأیید شد.');return Response::redirect(Url::to('/admin/reviews/'.$p['id']));}
    public function rejectReview(Request$r,array$p):Response{$this->check($r);$this->reviews->reject((int)$p['id'],(int)$this->session->get('user_id'),(string)$r->input('internal_note'));$this->audit->record('manual_review.rejected','manual_review',$p['id'],[],[],(int)$this->session->get('user_id'));$this->flash('success','درخواست رد شد.');return Response::redirect(Url::to('/admin/reviews/'.$p['id']));}
    public function webhookList(Request$r):Response{return$this->page('admin/webhooks','Webhookها','webhooks',['rows'=>$this->admin->webhooks()]);}
    public function webhook(Request$r,array$p):Response{$delivery=$this->admin->webhook((int)$p['id']);if(!$delivery)throw new DomainException('not_found','Delivery پیدا نشد.',404);return$this->page('admin/webhook_detail','جزئیات Webhook','webhooks',['delivery'=>$delivery]);}
    public function retryWebhook(Request$r,array$p):Response{$this->check($r);$this->webhooks->retry((int)$p['id']);$this->audit->record('webhook.retried','webhook_delivery',$p['id'],[],[],(int)$this->session->get('user_id'));$this->flash('success','ارسال مجدد انجام شد.');return Response::redirect(Url::to('/admin/webhooks/'.$p['id']));}
    public function settings(Request$r,array$p=[]):Response{$section=$p['section']??'general';return$this->page('admin/settings','تنظیمات','setup',['section'=>$section,'values'=>$this->settings->all()]);}
    public function saveSettings(Request$r,array$p=[]):Response{$this->check($r);$section=$p['section']??'general';$data=$r->post;$allowed=match($section){'branding'=>['app_name'=>'string','primary_color'=>'string','accent_color'=>'string','payment_title'=>'string','payment_help'=>'string','success_text'=>'string','expired_text'=>'string','footer'=>'string','support_contact'=>'string','logo_path'=>'string','favicon_path'=>'string'],'security'=>['session_lifetime'=>'int','log_retention'=>'int','upload_max_mb'=>'int'],'general'=>['currency'=>'string','timezone'=>'string','default_token_digits'=>'int','default_expiration_minutes'=>'int','token_cooldown_minutes'=>'int'],default=>[]};if($section==='branding'){foreach(['logo'=>'logo_path','favicon'=>'favicon_path']as$fileKey=>$settingKey){$file=$r->files[$fileKey]??null;if(is_array($file)&&($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)$data[$settingKey]=$this->uploads->branding($file,$fileKey);}}$this->admin->saveSettings($data,$allowed);$this->flash('success','تنظیمات ذخیره شد.');return Response::redirect(Url::to('/admin/settings/'.$section));}
    public function reports(Request$r):Response{return$this->page('admin/reports','گزارش‌ها','reports',$this->admin->report($r->query['from']??null,$r->query['to']??null));}
    public function reportCsv(Request$r):Response{$report=$this->admin->report($r->query['from']??null,$r->query['to']??null);$timezone=(string)$this->settings->get('timezone','Asia/Tehran');$fp=fopen('php://temp','r+');fwrite($fp,"\xEF\xBB\xBF");fputcsv($fp,['شناسه','پروژه','کارت','مبلغ','وضعیت','تاریخ']);foreach($report['rows'] as$row)fputcsv($fp,[$row['public_id'],$row['application_name'],$row['card_title'],$row['payable_amount'],$row['status'],View::localDate($row['created_at'],$timezone)]);rewind($fp);$body=stream_get_contents($fp);fclose($fp);return new Response($body,200,['Content-Type'=>'text/csv; charset=utf-8','Content-Disposition'=>'attachment; filename="cardpay-report.csv"']);}
    public function logs(Request$r):Response{return$this->page('admin/logs','گزارش رویدادها','logs',['rows'=>$this->admin->auditLogs()]);}
    public function system(Request$r):Response{return$this->page('admin/system','اطلاعات سیستم','system',['info'=>['نسخه CardPay'=>'1.0.4','نسخه PHP'=>PHP_VERSION,'درایور دیتابیس'=>(string)$this->config->get('db.connection'),'محیط'=>(string)$this->config->get('app.env'),'Timezone'=>(string)$this->settings->get('timezone',$this->config->get('app.timezone')),'cURL'=>extension_loaded('curl')?'فعال':'غیرفعال','OpenSSL'=>OPENSSL_VERSION_TEXT,'فضای آزاد'=>number_format((float)disk_free_space($this->root)/1073741824,1).' GB']]);}
    public function profile(Request$r):Response{return$this->page('admin/profile','حساب کاربری','profile',['profile'=>$this->admin->profile((int)$this->session->get('user_id'))]);}
    public function changePassword(Request$r):Response{$this->check($r);$this->admin->changePassword((int)$this->session->get('user_id'),(string)$r->input('current_password'),(string)$r->input('new_password'),(string)$r->input('new_password_confirmation'));$this->flash('success','رمز عبور تغییر کرد.');return Response::redirect(Url::to('/admin/profile'));}
    private function resource(string$title,string$active,array$rows,array$columns,string$create,string$kind):Response{return$this->page('admin/resource_list',$title,$active,compact('rows','columns','create','kind'));}
    private function page(string$template,string$title,string$active,array$data=[]):Response{$data+=['title'=>$title,'active'=>$active,'settings'=>$this->settings->all(),'userName'=>$this->session->get('user_name'),'csrf'=>$this->csrf->token(),'flash'=>$this->session->pullFlash()];return new Response($this->view->render($template,$data,'layouts/admin'),200,['Content-Type'=>'text/html; charset=utf-8','Cache-Control'=>'no-store, private']);}
    private function check(Request$r):void{if(!$this->csrf->valid((string)$r->input('_csrf')))throw new DomainException('csrf_mismatch','نشست فرم منقضی شده است.',419);}
    private function flash(string$type,string$message):void{$this->session->flash($type,$message);}
}

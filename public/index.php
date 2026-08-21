<?php
declare(strict_types=1);

$root=dirname(__DIR__);$vendor=$root.'/vendor/autoload.php';require is_file($vendor)?$vendor:$root.'/app/autoload.php';

use CardPay\Controllers\InstallerController;
use CardPay\Core\Csrf;
use CardPay\Core\DomainException;
use CardPay\Core\Env;
use CardPay\Core\Logger;
use CardPay\Core\Request;
use CardPay\Core\Response;
use CardPay\Core\Router;
use CardPay\Core\SecurityHeaders;
use CardPay\Core\Session;
use CardPay\Core\Url;
use CardPay\Core\View;
use CardPay\Core\ValidationException;

Env::load($root.'/.env');SecurityHeaders::apply();if(is_dir($root.'/storage/sessions')&&is_writable($root.'/storage/sessions'))session_save_path($root.'/storage/sessions');$session=new Session();$session->start((int)Env::get('SESSION_LIFETIME',120));$request=Request::capture();
$installed=is_file($root.'/storage/installed.lock')&&(bool)Env::get('APP_INSTALLED',false);
if(!$installed){$view=new View($root.'/app/Views');$controller=new InstallerController($root,$view,$session,new Csrf($session));if($request->path==='/install'&&$request->method==='POST')$controller->install($request)->send();if($request->path==='/install')$controller->show($request)->send();Response::redirect(Url::to('/install'))->send();}

try{$container=require $root.'/app/bootstrap.php';$router=new Router();require $root.'/app/routes.php';$router->dispatch($request)->send();}
catch(DomainException$e){$response=str_starts_with($request->path,'/api/')?Response::error($e->errorCode,$e->getMessage(),$e->httpStatus,$e->details):new Response('<main class="error-page"><h1>درخواست انجام نشد</h1><p>'.View::e($e->getMessage()).'</p><a href="'.View::e(Url::to('/')).'">بازگشت</a></main>',$e->httpStatus,['Content-Type'=>'text/html; charset=utf-8']);$response->send();}
catch(ValidationException$e){Response::error('validation_failed',$e->getMessage(),422,$e->errors)->send();}
catch(Throwable$e){$logger=isset($container)?$container->get(Logger::class):(new Logger($root.'/storage/logs/cardpay.log'));$logger->log('error','Unhandled exception',['type'=>$e::class,'message'=>$e->getMessage()]);$debug=(bool)Env::get('APP_DEBUG',false);if(str_starts_with($request->path,'/api/'))Response::error('internal_error',$debug?$e->getMessage():'An unexpected error occurred.',500)->send();(new Response('<main class="error-page"><h1>خطای غیرمنتظره</h1><p>'.($debug?View::e($e->getMessage()):'لطفاً چند لحظه دیگر دوباره تلاش کنید.').'</p></main>',500,['Content-Type'=>'text/html; charset=utf-8']))->send();}

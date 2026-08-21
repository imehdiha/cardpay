<?php
declare(strict_types=1);

namespace CardPay\Controllers;

use CardPay\Core\Config;
use CardPay\Core\Csrf;
use CardPay\Core\Crypto;
use CardPay\Core\Database;
use CardPay\Core\Migrator;
use CardPay\Core\Request;
use CardPay\Core\Response;
use CardPay\Core\Session;
use CardPay\Core\View;
use CardPay\Repositories\ApplicationRepository;
use CardPay\Repositories\SettingsRepository;
use PDO;
use Throwable;

final class InstallerController
{
    public function __construct(private readonly string $root,private readonly View $view,private readonly Session $session,private readonly Csrf $csrf){}
    public function show(Request $request):Response
    {
        if($this->installed())return new Response($this->view->render('installer/locked',['title'=>'نصب قفل است'],'layouts/guest'),409,['Content-Type'=>'text/html; charset=utf-8']);
        return new Response($this->view->render('installer/index',['title'=>'نصب CardPay','checks'=>$this->requirements(),'csrf'=>$this->csrf->token(),'error'=>$this->session->get('install_error')],'layouts/guest'),200,['Content-Type'=>'text/html; charset=utf-8']);
    }
    public function install(Request $request):Response
    {
        if($this->installed())return Response::redirect('/install');
        if(!$this->csrf->valid((string)$request->input('_csrf')))return new Response('CSRF token mismatch.',419);
        $checks=$this->requirements();foreach($checks as $check)if($check['required']&&!$check['ok'])return $this->fail('حداقل یکی از نیازمندی‌های ضروری سرور فراهم نیست.');
        $data=$request->post;$prefix=(string)($data['db_prefix']??'cp_');
        if(!preg_match('/^[a-zA-Z0-9_]{0,20}$/',$prefix))return $this->fail('Prefix جدول فقط می‌تواند شامل حروف، عدد و زیرخط باشد.');
        if(trim((string)($data['admin_name']??''))===''||trim((string)($data['admin_username']??''))==='')return $this->fail('نام و نام کاربری مدیر الزامی است.');
        if(strlen((string)($data['admin_password']??''))<10||!hash_equals((string)($data['admin_password']??''),(string)($data['admin_password_confirmation']??'')))return $this->fail('رمز مدیر باید حداقل ۱۰ نویسه باشد و تکرار آن تطبیق کند.');
        $timezone=(string)($data['timezone']??'Asia/Tehran');if(!in_array($timezone,timezone_identifiers_list(),true))return $this->fail('منطقه زمانی معتبر نیست.');
        $url=rtrim((string)($data['app_url']??''),'/');if(filter_var($url,FILTER_VALIDATE_URL)===false)return $this->fail('URL اصلی معتبر نیست.');
        $host=(string)($data['db_host']??'127.0.0.1');$port=(int)($data['db_port']??3306);$name=(string)($data['db_name']??'');$user=(string)($data['db_username']??'');$password=(string)($data['db_password']??'');
        try{
            $pdo=new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",$user,$password,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);$db=new Database($pdo,$prefix);
            (new Migrator($db,$this->root.'/database/migrations'))->migrate();$now=gmdate('Y-m-d H:i:s');
            if(!$db->fetch('SELECT id FROM '.$db->table('users').' LIMIT 1'))$db->insert('INSERT INTO '.$db->table('users').' (name,username,mobile,password_hash,role,is_active,created_at,updated_at) VALUES (?,?,?,?,?,1,?,?)',[trim((string)$data['admin_name']),mb_strtolower(trim((string)$data['admin_username'])),trim((string)($data['admin_mobile']??''))?:null,password_hash((string)$data['admin_password'],PASSWORD_DEFAULT),'admin',$now,$now]);
            $settings=new SettingsRepository($db);foreach(['app_name'=>(string)($data['app_name']??'CardPay'),'app_url'=>$url,'currency'=>(string)($data['currency']??'IRR'),'timezone'=>$timezone,'locale'=>(string)($data['locale']??'fa'),'default_token_digits'=>(int)($data['token_digits']??3),'default_expiration_minutes'=>(int)($data['expiration_minutes']??30),'token_cooldown_minutes'=>10,'primary_color'=>'#155EEF','accent_color'=>'#12B76A','payment_title'=>'پرداخت امن کارت‌به‌کارت','payment_help'=>'مبلغ را دقیق و بدون کم‌وزیاد واریز کنید.','success_text'=>'پرداخت شما با موفقیت تأیید شد.','expired_text'=>'زمان این پرداخت به پایان رسیده است.'] as $key=>$value)$settings->set($key,$value,is_int($value)?'int':'string',in_array($key,['app_name','app_url','currency','primary_color','accent_color','payment_title','payment_help','success_text','expired_text'],true));
            $appKey=Crypto::generateKey();$siteCredentials=null;
            if(!$db->fetch('SELECT id FROM '.$db->table('applications').' LIMIT 1')){
                $siteCredentials=(new ApplicationRepository($db,new Crypto($appKey)))->create(['name'=>(string)($data['app_name']??'فروشگاه من'),'slug'=>'store','description'=>'اتصال اصلی فروشگاه','webhook_url'=>'','callback_url'=>'','allowed_domains'=>'','token_digits'=>(int)($data['token_digits']??3),'payment_expiration_minutes'=>(int)($data['expiration_minutes']??30),'default_bank_card_id'=>0]);
            }
            $env=$this->envContent(['APP_NAME'=>(string)($data['app_name']??'CardPay'),'APP_ENV'=>'production','APP_DEBUG'=>'false','APP_URL'=>$url,'APP_KEY'=>$appKey,'APP_TIMEZONE'=>$timezone,'APP_LOCALE'=>(string)($data['locale']??'fa'),'APP_INSTALLED'=>'true','DB_CONNECTION'=>'mysql','DB_HOST'=>$host,'DB_PORT'=>(string)$port,'DB_DATABASE'=>$name,'DB_USERNAME'=>$user,'DB_PASSWORD'=>$password,'DB_PREFIX'=>$prefix,'SESSION_LIFETIME'=>'120','API_TIMESTAMP_TOLERANCE'=>'300','API_RATE_LIMIT'=>'120','DEVICE_RATE_LIMIT'=>'60','LOGIN_RATE_LIMIT'=>'5','UPLOAD_MAX_MB'=>'5','LOG_LEVEL'=>'warning']);
            $tmp=$this->root.'/.env.tmp';if(file_put_contents($tmp,$env,LOCK_EX)===false||!rename($tmp,$this->root.'/.env'))throw new \RuntimeException('Could not write environment file.');@chmod($this->root.'/.env',0600);
            if(file_put_contents($this->root.'/storage/installed.lock',gmdate('c').PHP_EOL,LOCK_EX)===false)throw new \RuntimeException('Could not create install lock.');
            $this->session->forget('install_error');return new Response($this->view->render('installer/success',['title'=>'نصب کامل شد','appUrl'=>$url,'siteCredentials'=>$siteCredentials],'layouts/guest'),200,['Content-Type'=>'text/html; charset=utf-8']);
        }catch(Throwable $e){return $this->fail('اتصال یا نصب دیتابیس انجام نشد. نام دیتابیس، دسترسی کاربر و امکان ساخت جدول را بررسی کنید.');}
    }
    private function fail(string $message):Response{$this->session->put('install_error',$message);return Response::redirect(\CardPay\Core\Url::to('/install'));}
    private function installed():bool{return is_file($this->root.'/storage/installed.lock');}
    private function requirements():array
    {
        $checks=[['label'=>'PHP 8.2 یا بالاتر','ok'=>version_compare(PHP_VERSION,'8.2.0','>='),'required'=>true]];
        foreach(['pdo_mysql','openssl','mbstring','json','curl','fileinfo'] as $ext)$checks[]=['label'=>'افزونه '.$ext,'ok'=>extension_loaded($ext),'required'=>true];
        foreach(['storage','storage/logs','storage/cache','storage/uploads','public/uploads'] as $dir)$checks[]=['label'=>'قابل نوشتن: '.$dir,'ok'=>is_writable($this->root.'/'.$dir),'required'=>true];
        $checks[]=['label'=>'Composer autoload (در بسته نهایی)','ok'=>is_file($this->root.'/vendor/autoload.php'),'required'=>true];
        $checks[]=['label'=>'Apache rewrite یا front controller جایگزین','ok'=>function_exists('apache_get_modules')?in_array('mod_rewrite',apache_get_modules(),true):true,'required'=>false];
        return$checks;
    }
    private function envContent(array $values):string{$lines=[];foreach($values as$key=>$value)$lines[]=$key.'="'.addcslashes((string)$value,"\\\"\n\r").'"';return implode(PHP_EOL,$lines).PHP_EOL;}
}

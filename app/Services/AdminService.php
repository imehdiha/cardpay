<?php
declare(strict_types=1);

namespace CardPay\Services;

use CardPay\Core\Crypto;
use CardPay\Core\Database;
use CardPay\Core\DomainException;
use CardPay\Core\Id;
use CardPay\Core\Time;
use CardPay\Repositories\ApplicationRepository;
use CardPay\Repositories\CardRepository;
use CardPay\Repositories\DeviceRepository;
use CardPay\Repositories\PaymentRepository;
use CardPay\Repositories\SettingsRepository;

final class AdminService
{
    public function __construct(private readonly Database$db,private readonly ApplicationRepository$apps,private readonly CardRepository$cards,private readonly DeviceRepository$devices,private readonly PaymentRepository$payments,private readonly SettingsRepository$settings,private readonly Crypto$crypto,private readonly SmsParserService$parser,private readonly AuditService$audit){}
    public function dashboard():array
    {
        $p=$this->db->table('payments');$s=$this->db->table('incoming_sms');$w=$this->db->table('webhook_deliveries');$d=$this->db->table('devices');$timezone=(string)$this->settings->get('timezone','Asia/Tehran');[$today,$tomorrow]=Time::localDayBoundsUtc(null,$timezone);
        $one=fn(string$sql,array$params=[])=>(int)($this->db->fetch($sql,$params)['value']??0);
        $stats=['today_count'=>$one("SELECT COUNT(*) value FROM {$p} WHERE created_at>=? AND created_at<?",[$today,$tomorrow]),'today_paid_amount'=>$one("SELECT COALESCE(SUM(payable_amount),0) value FROM {$p} WHERE status='paid' AND paid_at>=? AND paid_at<?",[$today,$tomorrow]),'pending'=>$one("SELECT COUNT(*) value FROM {$p} WHERE status='pending'"),'manual_review'=>$one("SELECT COUNT(*) value FROM {$p} WHERE status='manual_review'"),'unmatched'=>$one("SELECT COUNT(*) value FROM {$s} WHERE match_status='unmatched'"),'failed_webhooks'=>$one("SELECT COUNT(*) value FROM {$w} WHERE status IN ('failed','exhausted')"),'inactive_devices'=>$one("SELECT COUNT(*) value FROM {$d} WHERE is_active=0")];
        $chart=$this->db->fetchAll("SELECT SUBSTR(created_at,1,10) day,COUNT(*) total,SUM(CASE WHEN status='paid' THEN payable_amount ELSE 0 END) amount FROM {$p} WHERE created_at>=? GROUP BY SUBSTR(created_at,1,10) ORDER BY day",[gmdate('Y-m-d H:i:s',time()-6*86400)]);
        return['stats'=>$stats,'chart'=>$chart,'last_payment'=>$this->db->fetch("SELECT * FROM {$p} WHERE status='paid' ORDER BY paid_at DESC LIMIT 1"),'last_sms'=>$this->db->fetch("SELECT * FROM {$s} ORDER BY id DESC LIMIT 1"),'store'=>$this->siteOverview()];
    }
    public function primaryApplication():?array{return$this->db->fetch('SELECT * FROM '.$this->db->table('applications').' ORDER BY id ASC LIMIT 1');}
    public function siteOverview():array
    {
        $site=$this->primaryApplication();$cards=$this->cards->all();$card=null;
        if($site&&!empty($site['default_bank_card_id']))$card=$this->cards->find((int)$site['default_bank_card_id']);
        if(!$card)foreach($cards as$item)if(!empty($item['is_active'])){$card=$item;break;}
        $device=$card?$this->db->fetch('SELECT * FROM '.$this->db->table('devices').' WHERE bank_card_id=? ORDER BY is_active DESC,id DESC LIMIT 1',[$card['id']]):null;
        $parser=$card&&!empty($card['sms_parser_id'])?$this->parser((int)$card['sms_parser_id']):null;
        return['site'=>$site,'cards'=>$cards,'card'=>$card,'device'=>$device,'parser'=>$parser,'parsers'=>$this->parsers(),'ready'=>(bool)($site&&$card&&$device&&$parser)];
    }
    public function createPrimaryApplication(array$data):array
    {
        if($this->primaryApplication())throw new DomainException('site_already_connected','اتصال سایت قبلاً ساخته شده است.',409);
        $siteUrl=trim((string)($data['site_url']??''));$this->assertUrl($siteUrl?:null);
        $domain=$siteUrl?(string)parse_url($siteUrl,PHP_URL_HOST):'';
        $cards=$this->cards->all();$cardId=(int)($data['default_bank_card_id']??($cards[0]['id']??0));
        return$this->createApplication(['name'=>trim((string)($data['name']??$this->settings->get('app_name','فروشگاه من'))),'slug'=>'store','description'=>'اتصال اصلی فروشگاه','webhook_url'=>trim((string)($data['webhook_url']??'')),'callback_url'=>trim((string)($data['callback_url']??'')),'allowed_domains'=>$domain,'token_digits'=>(int)$this->settings->get('default_token_digits',3),'payment_expiration_minutes'=>(int)$this->settings->get('default_expiration_minutes',30),'default_bank_card_id'=>$cardId]);
    }
    public function updatePrimaryApplication(array$data):void
    {
        $site=$this->primaryApplication();if(!$site)throw new DomainException('not_found','اتصال سایت هنوز ساخته نشده است.',404);
        $siteUrl=trim((string)($data['site_url']??''));$this->assertUrl($siteUrl?:null);
        $domain=$siteUrl?(string)parse_url($siteUrl,PHP_URL_HOST):'';
        $this->updateApplication((int)$site['id'],['name'=>trim((string)($data['name']??$site['name'])),'description'=>'اتصال اصلی فروشگاه','webhook_url'=>trim((string)($data['webhook_url']??'')),'callback_url'=>trim((string)($data['callback_url']??'')),'allowed_domains'=>$domain,'token_digits'=>(int)($data['token_digits']??$site['token_digits']),'payment_expiration_minutes'=>(int)($data['payment_expiration_minutes']??$site['payment_expiration_minutes']),'default_bank_card_id'=>(int)($data['default_bank_card_id']??0),'is_active'=>1]);
    }
    public function applications():array{return$this->apps->all();}
    public function application(int$id):?array{return$this->apps->find($id);}
    public function createApplication(array$data):array
    {
        $name=trim((string)($data['name']??''));$slug=trim((string)($data['slug']??''));if($name===''||!preg_match('/^[a-z0-9][a-z0-9_-]{1,79}$/',$slug))throw new DomainException('validation_failed','نام و slug معتبر الزامی است.',422);$digits=(int)($data['token_digits']??3);$expiry=(int)($data['payment_expiration_minutes']??30);if(!in_array($digits,[2,3],true)||$expiry<1||$expiry>10080)throw new DomainException('validation_failed','تنظیمات توکن یا انقضا معتبر نیست.',422);$this->assertUrl($data['webhook_url']??null);$this->assertUrl($data['callback_url']??null);
        $result=$this->apps->create(['name'=>$name,'slug'=>$slug,'description'=>trim((string)($data['description']??'')),'webhook_url'=>trim((string)($data['webhook_url']??'')),'callback_url'=>trim((string)($data['callback_url']??'')),'allowed_domains'=>trim((string)($data['allowed_domains']??'')),'token_digits'=>$digits,'payment_expiration_minutes'=>$expiry,'default_bank_card_id'=>(int)($data['default_bank_card_id']??0)]);$this->audit->record('application.created','application',$result['application']['id'],[],['name'=>$name]);return$result;
    }
    public function updateApplication(int$id,array$data):void
    {
        $old=$this->apps->find($id);if(!$old)throw new DomainException('not_found','Application پیدا نشد.',404);$name=trim((string)($data['name']??''));$expiry=(int)($data['payment_expiration_minutes']??30);$digits=(int)($data['token_digits']??3);if($name===''||!in_array($digits,[2,3],true)||$expiry<1||$expiry>10080)throw new DomainException('validation_failed','اطلاعات Application معتبر نیست.',422);$this->assertUrl($data['webhook_url']??null);$this->assertUrl($data['callback_url']??null);
        $this->db->execute('UPDATE '.$this->db->table('applications').' SET name=?,description=?,webhook_url=?,callback_url=?,allowed_domains=?,is_active=?,token_digits=?,payment_expiration_minutes=?,default_bank_card_id=?,updated_at=? WHERE id=?',[$name,trim((string)($data['description']??''))?:null,trim((string)($data['webhook_url']??''))?:null,trim((string)($data['callback_url']??''))?:null,trim((string)($data['allowed_domains']??''))?:null,isset($data['is_active'])?1:0,$digits,$expiry,(int)($data['default_bank_card_id']??0)?:null,gmdate('Y-m-d H:i:s'),$id]);$this->audit->record('application.updated','application',$id,$old,['name'=>$name,'is_active'=>isset($data['is_active'])]);
    }
    public function rotateApplication(int$id):array{$credentials=$this->apps->rotate($id);$this->audit->record('api_key.rotated','application',$id);return$credentials;}
    public function cards():array{return$this->cards->all();}
    public function card(int$id):?array{return$this->cards->find($id);}
    public function createCard(array$data):int
    {
        $number=preg_replace('/\D/','',(string)($data['card_number']??''));if(strlen($number)!==16||trim((string)($data['title']??''))===''||trim((string)($data['card_holder_name']??''))==='')throw new DomainException('validation_failed','شماره کارت ۱۶ رقمی و اطلاعات مالک الزامی است.',422);$id=$this->cards->create($data);$this->audit->record('bank_card.created','bank_card',$id,[],['title'=>$data['title'],'last_four'=>substr($number,-4)]);return$id;
    }
    public function updateCard(int$id,array$data):void
    {
        $old=$this->cards->find($id);if(!$old)throw new DomainException('not_found','کارت پیدا نشد.',404);$sets=['title=?','bank_name=?','card_holder_name=?','description=?','sms_parser_id=?','is_active=?','updated_at=?'];$params=[trim((string)$data['title']),trim((string)$data['bank_name']),trim((string)$data['card_holder_name']),trim((string)($data['description']??''))?:null,(int)($data['sms_parser_id']??0)?:null,isset($data['is_active'])?1:0,gmdate('Y-m-d H:i:s')];$number=preg_replace('/\D/','',(string)($data['card_number']??''));if($number!==''){if(strlen($number)!==16)throw new DomainException('validation_failed','شماره کارت باید ۱۶ رقم باشد.',422);$sets[]='card_number_encrypted=?';$sets[]='card_number_last_four=?';$params[]=$this->crypto->encrypt($number);$params[]=substr($number,-4);}$params[]=$id;$this->db->execute('UPDATE '.$this->db->table('bank_cards').' SET '.implode(',',$sets).' WHERE id=?',$params);$this->audit->record('bank_card.updated','bank_card',$id,$old,['title'=>$data['title'],'is_active'=>isset($data['is_active'])]);
    }
    public function devices():array{return$this->devices->all();}
    public function createDevice(array$data):array{if(trim((string)($data['name']??''))===''||!in_array($data['platform']??'',['ios','android','other'],true)||empty($data['bank_card_id']))throw new DomainException('validation_failed','اطلاعات Device کامل نیست.',422);$result=$this->devices->create($data);$this->audit->record('device.created','device',$result['device']['id'],[],['name'=>$data['name']]);return$result;}
    public function revokeDevice(int$id):void{$this->db->execute('UPDATE '.$this->db->table('devices').' SET is_active=0,revoked_at=? WHERE id=?',[gmdate('Y-m-d H:i:s'),$id]);$this->audit->record('device.revoked','device',$id);}
    public function rotateDevice(int$id):array{$secret='dsk_'.Id::secret(32);$this->db->execute('UPDATE '.$this->db->table('devices').' SET device_secret_encrypted=?,secret_fingerprint=?,is_active=1,revoked_at=NULL WHERE id=?',[$this->crypto->encrypt($secret),hash('sha256',$secret),$id]);$device=$this->db->fetch('SELECT * FROM '.$this->db->table('devices').' WHERE id=?',[$id]);if(!$device)throw new DomainException('not_found','Device پیدا نشد.',404);$this->audit->record('device.secret_rotated','device',$id);return['device'=>$device,'secret'=>$secret];}
    public function parsers():array{return$this->db->fetchAll('SELECT * FROM '.$this->db->table('sms_parsers').' ORDER BY id DESC');}
    public function parser(int$id):?array{return$this->db->fetch('SELECT * FROM '.$this->db->table('sms_parsers').' WHERE id=?',[$id]);}
    public function saveParser(array$data,?int$id=null):int
    {
        $name=trim((string)($data['name']??''));$regex=trim((string)($data['amount_regex']??''));if($name===''||$regex===''||@preg_match($regex,'')===false)throw new DomainException('validation_failed','نام و Regex مبلغ معتبر الزامی است.',422);foreach(['sender_pattern','date_regex','time_regex','transaction_type_regex'] as$key)if(!empty($data[$key])&&@preg_match((string)$data[$key],'')===false)throw new DomainException('validation_failed',"Regex {$key} معتبر نیست.",422);$now=gmdate('Y-m-d H:i:s');$values=[$name,trim((string)($data['bank_name']??'')),($data['sender_pattern']??'')?:null,$regex,($data['date_regex']??'')?:null,($data['time_regex']??'')?:null,($data['transaction_type_regex']??'')?:null,$this->keywordJson((string)($data['positive_keywords']??'')),$this->keywordJson((string)($data['negative_keywords']??'')),($data['sample_sms']??'')?:null,isset($data['is_active'])?1:0,$now];
        if($id){$values[]=$id;$this->db->execute('UPDATE '.$this->db->table('sms_parsers').' SET name=?,bank_name=?,sender_pattern=?,amount_regex=?,date_regex=?,time_regex=?,transaction_type_regex=?,positive_keywords=?,negative_keywords=?,sample_sms=?,is_active=?,updated_at=? WHERE id=?',$values);}else{$values[]=$now;$id=$this->db->insert('INSERT INTO '.$this->db->table('sms_parsers').' (name,bank_name,sender_pattern,amount_regex,date_regex,time_regex,transaction_type_regex,positive_keywords,negative_keywords,sample_sms,is_active,updated_at,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',$values);}$this->audit->record($id?'sms_parser.saved':'sms_parser.created','sms_parser',$id);return$id;
    }
    public function testParser(array$data):array{return$this->parser->parse((string)($data['sample_sms']??''),['amount_regex'=>$data['amount_regex']??'','positive_keywords'=>$this->keywordJson((string)($data['positive_keywords']??'')),'negative_keywords'=>$this->keywordJson((string)($data['negative_keywords']??''))],gmdate('Y-m-d H:i:s'));}
    public function paymentList(array$filters):array{return$this->payments->all($filters,min(100,max(10,(int)($filters['limit']??50))),max(0,((int)($filters['page']??1)-1)*50));}
    public function paymentDetail(string$id):array{$payment=$this->payments->findPublic($id);if(!$payment)throw new DomainException('not_found','پرداخت پیدا نشد.',404);return['payment'=>$payment,'matches'=>$this->db->fetchAll('SELECT m.*,s.sender,s.parsed_amount FROM '.$this->db->table('payment_matches').' m JOIN '.$this->db->table('incoming_sms').' s ON s.id=m.incoming_sms_id WHERE m.payment_id=? ORDER BY m.id DESC',[$payment['id']]),'reviews'=>$this->db->fetchAll('SELECT * FROM '.$this->db->table('manual_review_requests').' WHERE payment_id=? ORDER BY id DESC',[$payment['id']]),'events'=>$this->db->fetchAll('SELECT * FROM '.$this->db->table('webhook_events').' WHERE payment_id=? ORDER BY id DESC',[$payment['id']])];}
    public function smsList(bool$unmatched=false):array{$where=$unmatched?"WHERE s.match_status IN ('unmatched','ambiguous')":'';return$this->db->fetchAll('SELECT s.*,d.name AS device_name,b.title AS card_title FROM '.$this->db->table('incoming_sms').' s JOIN '.$this->db->table('devices').' d ON d.id=s.device_id JOIN '.$this->db->table('bank_cards')." b ON b.id=s.bank_card_id {$where} ORDER BY s.id DESC LIMIT 100");}
    public function smsDetail(int$id):?array{return$this->db->fetch('SELECT s.*,d.name AS device_name,b.title AS card_title FROM '.$this->db->table('incoming_sms').' s JOIN '.$this->db->table('devices').' d ON d.id=s.device_id JOIN '.$this->db->table('bank_cards').' b ON b.id=s.bank_card_id WHERE s.id=?',[$id]);}
    public function reviews():array{return$this->db->fetchAll('SELECT r.*,p.public_id,p.status AS payment_status,a.name AS application_name FROM '.$this->db->table('manual_review_requests').' r JOIN '.$this->db->table('payments').' p ON p.id=r.payment_id JOIN '.$this->db->table('applications')." a ON a.id=p.application_id ORDER BY (r.status='pending') DESC,r.id DESC LIMIT 100");}
    public function review(int$id):?array{return$this->db->fetch('SELECT r.*,p.public_id,p.status AS payment_status,p.payable_amount,p.bank_card_id,p.customer_name,s.raw_sms FROM '.$this->db->table('manual_review_requests').' r JOIN '.$this->db->table('payments').' p ON p.id=r.payment_id LEFT JOIN '.$this->db->table('incoming_sms').' s ON s.id=r.incoming_sms_id WHERE r.id=?',[$id]);}
    public function unmatchedSmsForCard(int$cardId):array{return$this->db->fetchAll('SELECT * FROM '.$this->db->table('incoming_sms')." WHERE bank_card_id=? AND match_status IN ('unmatched','ambiguous') ORDER BY id DESC LIMIT 50",[$cardId]);}
    public function webhooks():array{return$this->db->fetchAll('SELECT d.*,e.event_id,e.event_type,p.public_id FROM '.$this->db->table('webhook_deliveries').' d JOIN '.$this->db->table('webhook_events').' e ON e.id=d.webhook_event_id JOIN '.$this->db->table('payments').' p ON p.id=e.payment_id ORDER BY d.id DESC LIMIT 100');}
    public function webhook(int$id):?array{return$this->db->fetch('SELECT d.*,e.event_id,e.event_type,e.payload_json FROM '.$this->db->table('webhook_deliveries').' d JOIN '.$this->db->table('webhook_events').' e ON e.id=d.webhook_event_id WHERE d.id=?',[$id]);}
    public function auditLogs():array{return$this->db->fetchAll('SELECT * FROM '.$this->db->table('audit_logs').' ORDER BY id DESC LIMIT 200');}
    public function report(?string$from,?string$to):array{$timezone=(string)$this->settings->get('timezone','Asia/Tehran');$fromDate=$from&&preg_match('/^\d{4}-\d{2}-\d{2}$/',$from)?$from:Time::localDate($timezone,'first day of this month');$toDate=$to&&preg_match('/^\d{4}-\d{2}-\d{2}$/',$to)?$to:Time::localDate($timezone);[$fromUtc]=Time::localDayBoundsUtc($fromDate,$timezone);[, $toUtc]=Time::localDayBoundsUtc($toDate,$timezone);$rows=$this->db->fetchAll('SELECT p.*,a.name AS application_name,b.title AS card_title FROM '.$this->db->table('payments').' p JOIN '.$this->db->table('applications').' a ON a.id=p.application_id JOIN '.$this->db->table('bank_cards').' b ON b.id=p.bank_card_id WHERE p.created_at>=? AND p.created_at<? ORDER BY p.id DESC',[$fromUtc,$toUtc]);$summary=['count'=>count($rows),'paid_count'=>0,'paid_amount'=>0];foreach($rows as$row)if($row['status']==='paid'){$summary['paid_count']++;$summary['paid_amount']+=(int)$row['payable_amount'];}$from=$fromDate;$to=$toDate;return compact('rows','summary','from','to');}
    public function saveSettings(array$data,array$allowed):void
    {
        foreach($allowed as$key=>$type)if(array_key_exists($key,$data)){
            $value=$type==='int'?(int)$data[$key]:trim((string)$data[$key]);
            if(in_array($key,['primary_color','accent_color'],true)&&!preg_match('/^#[0-9a-fA-F]{6}$/',(string)$value))throw new DomainException('validation_failed','رنگ برندینگ معتبر نیست.',422);
            if($key==='timezone'&&!in_array($value,timezone_identifiers_list(),true))throw new DomainException('validation_failed','منطقه زمانی معتبر نیست.',422);
            if($key==='default_token_digits'&&!in_array($value,[2,3],true))throw new DomainException('validation_failed','تعداد رقم توکن باید ۲ یا ۳ باشد.',422);
            $ranges=['default_expiration_minutes'=>[1,10080],'token_cooldown_minutes'=>[1,1440],'session_lifetime'=>[5,1440],'upload_max_mb'=>[1,20],'log_retention'=>[1,365]];
            if(isset($ranges[$key])&&($value<$ranges[$key][0]||$value>$ranges[$key][1]))throw new DomainException('validation_failed','مقدار عددی تنظیمات خارج از بازه مجاز است.',422);
            $this->settings->set($key,$value,$type,true);
        }
        $this->audit->record('settings.updated','settings',null,[],array_intersect_key($data,$allowed));
    }
    public function profile(int$userId):?array{return$this->db->fetch('SELECT id,name,username,mobile,role,last_login_at,last_login_ip,created_at FROM '.$this->db->table('users').' WHERE id=?',[$userId]);}
    public function changePassword(int$userId,string$current,string$new,string$confirmation):void{$user=$this->db->fetch('SELECT * FROM '.$this->db->table('users').' WHERE id=?',[$userId]);if(!$user||!password_verify($current,$user['password_hash']))throw new DomainException('invalid_password','رمز فعلی صحیح نیست.',422);if(strlen($new)<10||!hash_equals($new,$confirmation))throw new DomainException('validation_failed','رمز جدید باید حداقل ۱۰ نویسه باشد و تکرار آن تطبیق کند.',422);$this->db->execute('UPDATE '.$this->db->table('users').' SET password_hash=?,updated_at=? WHERE id=?',[password_hash($new,PASSWORD_DEFAULT),gmdate('Y-m-d H:i:s'),$userId]);$this->audit->record('profile.password_changed','user',$userId,[],[],$userId);}
    private function keywordJson(string$value):string{return json_encode(array_values(array_filter(array_map('trim',preg_split('/[,\n]+/u',$value)?:[]))),JSON_UNESCAPED_UNICODE);}
    private function assertUrl(mixed$url):void{if($url===null||trim((string)$url)==='')return;if(filter_var($url,FILTER_VALIDATE_URL)===false||!in_array(parse_url((string)$url,PHP_URL_SCHEME),['http','https'],true))throw new DomainException('validation_failed','URL باید معتبر و از نوع http/https باشد.',422);}
}

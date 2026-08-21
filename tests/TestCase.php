<?php
declare(strict_types=1);

namespace CardPay\Tests;

use CardPay\Core\Config;
use CardPay\Core\Crypto;
use CardPay\Core\Database;
use CardPay\Core\Migrator;
use CardPay\Repositories\ApplicationRepository;
use CardPay\Repositories\CardRepository;
use CardPay\Repositories\DeviceRepository;
use CardPay\Repositories\PaymentRepository;
use CardPay\Services\AmountNormalizer;
use CardPay\Services\ApiAuthService;
use CardPay\Services\ExpirationService;
use CardPay\Services\ManualReviewService;
use CardPay\Services\PaymentService;
use CardPay\Services\PaymentStateMachine;
use CardPay\Services\SmsParserService;
use CardPay\Services\SmsService;
use CardPay\Services\TokenAllocator;
use CardPay\Services\WebhookService;
use PDO;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;

abstract class TestCase extends PhpUnitTestCase
{
    protected Database$db;protected Config$config;protected Crypto$crypto;protected ApplicationRepository$apps;protected CardRepository$cards;protected DeviceRepository$devices;protected PaymentRepository$paymentRepo;protected TokenAllocator$tokens;protected WebhookService$webhooks;protected ExpirationService$expiration;protected PaymentService$payments;protected SmsService$sms;protected ManualReviewService$reviews;protected array$application;protected string$appSecret;protected array$device;protected string$deviceSecret;protected int$cardId;
    protected function setUp():void
    {
        parent::setUp();$this->db=new Database(new PDO('sqlite::memory:'),'cp_');(new Migrator($this->db,dirname(__DIR__).'/database/migrations'))->migrate();$this->config=new Config(['app'=>['url'=>'https://pay.test','currency'=>'IRR'],'security'=>['api_timestamp_tolerance'=>300],'payment'=>['token_cooldown_minutes'=>10]]);$this->crypto=new Crypto(Crypto::generateKey());$this->apps=new ApplicationRepository($this->db,$this->crypto);$this->cards=new CardRepository($this->db,$this->crypto);$this->devices=new DeviceRepository($this->db,$this->crypto);$this->paymentRepo=new PaymentRepository($this->db);$this->tokens=new TokenAllocator($this->db);$this->webhooks=new WebhookService($this->db,$this->crypto);$this->expiration=new ExpirationService($this->db,$this->paymentRepo,$this->tokens,$this->webhooks,$this->config);$state=new PaymentStateMachine();$this->payments=new PaymentService($this->db,$this->cards,$this->paymentRepo,$this->tokens,$state,$this->webhooks,$this->config);
        $parserId=$this->db->insert('INSERT INTO cp_sms_parsers (name,bank_name,sender_pattern,amount_regex,positive_keywords,negative_keywords,is_active,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?)',['Test bank','Bank','/^BANK$/i','/واریز[:\s]+(?<amount>[0-9۰-۹,٬ ]+)/u',json_encode(['واریز'],JSON_UNESCAPED_UNICODE),json_encode(['برداشت','مانده'],JSON_UNESCAPED_UNICODE),1,gmdate('Y-m-d H:i:s'),gmdate('Y-m-d H:i:s')]);
        $this->cardId=$this->cards->create(['title'=>'کارت تست','bank_name'=>'بانک تست','card_number'=>'6037999999999999','card_holder_name'=>'کاربر تست','iban'=>'','description'=>'','sms_parser_id'=>$parserId]);$created=$this->apps->create(['name'=>'Test App','slug'=>'test-app','description'=>'','webhook_url'=>'','callback_url'=>'','allowed_domains'=>'','token_digits'=>3,'payment_expiration_minutes'=>30,'default_bank_card_id'=>$this->cardId]);$this->application=$created['application'];$this->appSecret=$created['secret'];$device=$this->devices->create(['name'=>'Test phone','platform'=>'ios','bank_card_id'=>$this->cardId]);$this->device=$device['device'];$this->deviceSecret=$device['secret'];
        $parserService=new SmsParserService(new AmountNormalizer());$this->sms=new SmsService($this->db,$this->devices,$this->paymentRepo,$parserService,$state,$this->tokens,$this->webhooks,$this->expiration,$this->config);$this->reviews=new ManualReviewService($this->db,$this->paymentRepo,$state,$this->tokens,$this->webhooks,$this->config);
    }
    protected function createPayment(int$amount=1000000,string$key='idem-test-0001'):array{return$this->payments->create($this->application,['amount'=>$amount,'external_order_id'=>'ORDER-1','description'=>'Test','customer'=>['name'=>'Tester','mobile'=>'09120000000'],'return_url'=>'https://merchant.test/result','metadata'=>['plan_id'=>4]],$key);}
}

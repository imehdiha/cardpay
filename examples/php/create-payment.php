<?php
declare(strict_types=1);
require __DIR__.'/CardPayClient.php';

$client=new CardPayClient('https://pay.example.com','cp_pk_REPLACE_ME','cp_sk_REPLACE_ME');
$result=$client->createPayment(['amount'=>80000000,'external_order_id'=>'ORDER-123','description'=>'اشتراک نمونه','customer'=>['name'=>'کاربر نمونه','mobile'=>'09120000000'],'return_url'=>'https://merchant.example/payment/result','metadata'=>['plan_id'=>4]],'order-123-first-attempt');
header('Location: '.$result['data']['payment_url']);

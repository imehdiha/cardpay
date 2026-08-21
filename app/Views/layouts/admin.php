<?php
$appName=$settings['app_name']??'CardPay';
$activeGroup=match($active){
    'applications'=>'connection',
    'cards','devices','parsers','settings','logs','system','reports','webhooks'=>'setup',
    'unmatched','reviews'=>'sms',
    default=>$active,
};
$nav=[
    ['dashboard','/admin','⌂','خانه'],
    ['payments','/admin/payments','↔','پرداخت‌ها'],
    ['sms','/admin/sms','✉','پیامک‌ها'],
    ['connection','/admin/connection','⌁','اتصال سایت'],
    ['setup','/admin/setup','⚙','راه‌اندازی'],
];
?>
<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= \CardPay\Core\View::e($title) ?> — <?= \CardPay\Core\View::e($appName) ?></title><?php if(!empty($settings['favicon_path'])):?><link rel="icon" href="<?= \CardPay\Core\View::e(\CardPay\Core\Url::to('/uploads/'.basename($settings['favicon_path']))) ?>"><?php endif;?><link rel="stylesheet" href="<?= \CardPay\Core\View::e(\CardPay\Core\Url::to('/assets/app.css')) ?>"><style>:root{--primary:<?= \CardPay\Core\View::e($settings['primary_color']??'#155EEF') ?>;--accent:<?= \CardPay\Core\View::e($settings['accent_color']??'#12B76A') ?>}</style></head><body class="admin-body"><div class="admin-grid">
<aside class="sidebar"><a class="admin-brand" href="<?= \CardPay\Core\View::e(\CardPay\Core\Url::to('/admin')) ?>"><?php if(!empty($settings['logo_path'])):?><span class="brand-image"><img src="<?= \CardPay\Core\View::e(\CardPay\Core\Url::to('/uploads/'.basename($settings['logo_path']))) ?>" alt=""><?php else:?><span>C<?php endif;?></span><div><strong><?= \CardPay\Core\View::e($appName) ?></strong><small>پرداخت کارت‌به‌کارت</small></div></a><nav><?php foreach($nav as[$key,$href,$icon,$label]):?><a class="<?= $activeGroup===$key?'active':'' ?>" href="<?= \CardPay\Core\View::e(\CardPay\Core\Url::to($href)) ?>"><i><?= $icon ?></i><span><?= $label ?></span></a><?php endforeach;?></nav><div class="sidebar-foot"><a href="<?= \CardPay\Core\View::e(\CardPay\Core\Url::to('/admin/profile')) ?>"><span class="avatar"><?= \CardPay\Core\View::e(mb_substr((string)$userName,0,1)) ?></span><span><strong><?= \CardPay\Core\View::e($userName) ?></strong><small>حساب من</small></span></a><form method="post" action="<?= \CardPay\Core\View::e(\CardPay\Core\Url::to('/logout')) ?>"><input type="hidden" name="_csrf" value="<?= \CardPay\Core\View::e($csrf) ?>"><button title="خروج">↪</button></form></div></aside>
<main class="admin-main"><header class="topbar"><div><p class="eyebrow">مدیریت فروشگاه</p><h1><?= \CardPay\Core\View::e($title) ?></h1></div><a class="support-link" href="<?= \CardPay\Core\View::e(\CardPay\Core\Url::to('/admin/settings')) ?>">تنظیمات ظاهری <span>⚙</span></a></header><?php if($flash):?><div class="alert <?= \CardPay\Core\View::e($flash['type']) ?>"><?= \CardPay\Core\View::e($flash['message']) ?></div><?php endif;?><?= $content ?></main>
</div><script src="<?= \CardPay\Core\View::e(\CardPay\Core\Url::to('/assets/app.js')) ?>" defer></script></body></html>

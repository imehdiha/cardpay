<?php
$isApp=$kind==='application';$id=(int)$entity['id'];$public=$credentials['public_key']??($isApp?$entity['public_key']:$entity['device_key']);
?>
<?php if($isApp):?>
<section class="credential-grid"><article class="panel credential-card"><div class="panel-head"><div><h2>اطلاعات اتصال API</h2><p><?= \CardPay\Core\View::e($entity['name']) ?></p></div><span class="badge warning">فقط یک‌بار</span></div><?php if($credentials):?><div class="alert warning">Secret را همین حالا در محل امن ذخیره کنید؛ پس از خروج از این صفحه دیگر نمایش داده نمی‌شود.</div><label>Public Key<div class="copy-field"><code dir="ltr"><?= \CardPay\Core\View::e($public) ?></code><button type="button" data-copy="<?= \CardPay\Core\View::e($public) ?>">کپی</button></div></label><label>Secret<div class="copy-field secret"><code dir="ltr"><?= \CardPay\Core\View::e($credentials['secret']) ?></code><button type="button" data-copy="<?= \CardPay\Core\View::e($credentials['secret']) ?>">کپی</button></div></label><?php else:?><div class="empty">Secret خام قابل نمایش مجدد نیست. اگر آن را ندارید، کلید تازه بسازید.</div><label>Public Key<div class="copy-field"><code dir="ltr"><?= \CardPay\Core\View::e($public) ?></code><button type="button" data-copy="<?= \CardPay\Core\View::e($public) ?>">کپی</button></div></label><?php endif;?><form method="post" action="<?= \CardPay\Core\View::e(\CardPay\Core\Url::to('/admin/applications/'.$id.'/rotate')) ?>" class="form-actions"><input type="hidden" name="_csrf" value="<?= \CardPay\Core\View::e($csrf) ?>"><button class="btn danger" type="submit">ساخت Secret جدید</button></form></article><article class="panel"><div class="panel-head"><div><h2>قرارداد HMAC</h2><p>اطلاعات موردنیاز سایت PHP</p></div></div><pre class="code-block" dir="ltr">X-CardPay-Key: <?= \CardPay\Core\View::e($public) ?>
X-CardPay-Timestamp: {unix_seconds}
X-CardPay-Nonce: {random_16+}
X-CardPay-Signature: HMAC_SHA256(canonical, secret)
Idempotency-Key: {unique_order_key}</pre><p class="hint">Canonical request در docs/api-authentication.md تعریف شده است.</p></article></section>
<?php else:?>
<?php $baseUrl=rtrim((string)($settings['app_url']??''),'/');$endpoint=$baseUrl.\CardPay\Core\Url::to('/api/v1/devices/shortcut-sms');$secret=$credentials['secret']??null;?>
<section class="iphone-setup-hero"><div><span class="soft-badge">اتصال آیفون</span><h2><?= \CardPay\Core\View::e($entity['name']) ?></h2><p>هر پیام دریافتی که عبارت «مبلغ» داشته باشد، به CardPay فرستاده و با پرداخت منتظر تطبیق داده می‌شود.</p></div><div class="iphone-status <?= !empty($entity['last_seen_at'])?'online':'' ?>"><span></span><strong><?= !empty($entity['last_seen_at'])?'متصل شده':'منتظر اولین پیام' ?></strong><small><?= !empty($entity['last_seen_at'])?\CardPay\Core\View::e(\CardPay\Core\View::localDate($entity['last_seen_at'],(string)($settings['timezone']??'Asia/Tehran'))):'هنوز پیامی دریافت نشده' ?></small></div></section>

<?php if($secret):?>
<section class="secret-reveal iphone-secret"><div><span>کلید آیفون آماده شد</span><h2>این سه مقدار را در Shortcut وارد کنید</h2><p>Secret فقط همین یک‌بار نمایش داده می‌شود. آن را برای شخص دیگری نفرستید.</p></div><label>آدرس ارسال<div class="copy-field"><code dir="ltr"><?= \CardPay\Core\View::e($endpoint) ?></code><button type="button" data-copy="<?= \CardPay\Core\View::e($endpoint) ?>">کپی</button></div></label><label>Device Key<div class="copy-field"><code dir="ltr"><?= \CardPay\Core\View::e($public) ?></code><button type="button" data-copy="<?= \CardPay\Core\View::e($public) ?>">کپی</button></div></label><label>Device Secret<div class="copy-field secret"><code dir="ltr"><?= \CardPay\Core\View::e($secret) ?></code><button type="button" data-copy="<?= \CardPay\Core\View::e($secret) ?>">کپی</button></div></label><div class="alert warning">روی لوکال، آیفون و مک باید در یک شبکه باشند. روی هاست واقعی حتماً از آدرس HTTPS استفاده کنید.</div></section>
<?php else:?>
<section class="panel missing-secret"><div><span class="setup-number">!</span><div><h2>برای ساخت Shortcut به Secret نیاز داریم</h2><p>Secret قبلی به‌دلایل امنیتی قابل نمایش نیست. با ساخت Secret تازه، کلید قبلی غیرفعال می‌شود؛ اگر Shortcut فعالی ندارید مشکلی ایجاد نمی‌شود.</p></div></div><form method="post" action="<?= \CardPay\Core\View::e(\CardPay\Core\Url::to('/admin/devices/'.$id.'/rotate')) ?>"><input type="hidden" name="_csrf" value="<?= \CardPay\Core\View::e($csrf) ?>"><button class="btn primary" type="submit">ساخت Secret و ادامه</button></form></section>
<?php endif;?>

<section class="panel shortcut-guide"><div class="panel-head"><div><h2>ساخت Automation در آیفون</h2><p>همه مراحل داخل برنامه Shortcuts انجام می‌شود.</p></div><span class="soft-badge">حدود ۳ دقیقه</span></div><ol class="shortcut-steps">
  <li><span>۱</span><div><strong>Automation جدید بسازید</strong><p>Shortcuts را باز کنید ← پایین صفحه <b>Automation</b> ← علامت <b>+</b> ← گزینه <b>Message</b>.</p></div></li>
  <li><span>۲</span><div><strong>پیام بانکی را فیلتر کنید</strong><p>در <b>Message Contains</b> فقط کلمه <code>مبلغ</code> را بنویسید، سپس <b>Run Immediately</b> را انتخاب کنید.</p></div></li>
  <li><span>۳</span><div><strong>متن پیام را آماده کنید</strong><p>یک Action با نام <b>Text</b> اضافه کنید. داخل کادر Text روی <b>Select Variable</b> بزنید و <b>Shortcut Input</b> را انتخاب کنید. اگر جزئیات نمایش داده شد، <b>Content</b> یا نوع <b>Text</b> را انتخاب کنید.</p></div></li>
  <li><span>۴</span><div><strong>درخواست اینترنتی اضافه کنید</strong><p>Action بعدی <b>Get Contents of URL</b> باشد. آدرس بالا را وارد و Method را <b>POST</b> انتخاب کنید. بخش Headers را خالی بگذارید.</p></div></li>
  <li><span>۵</span><div><strong>بدنه JSON را تنظیم کنید</strong><p>Request Body را روی <b>JSON</b> بگذارید و سه فیلد بسازید:</p><pre class="shortcut-json" dir="ltr">device_key     =  Device Key
device_secret  =  Device Secret
raw_sms        =  Text</pre><small>دو مقدار اول را از بالای همین صفحه کپی کنید. Text همان خروجی آبیِ Action مرحله ۳ است.</small></div></li>
  <li><span>۶</span><div><strong>ذخیره کنید</strong><p>Automation را Done کنید. برای تست اولیه می‌توانید بعد از درخواست، Action «Show Notification» با متن «CardPay دریافت کرد» بگذارید.</p></div></li>
</ol></section>

<details class="panel advanced-tools iphone-advanced"><summary>روش پیشرفته HMAC</summary><p>برای اپ‌های اختصاصی یا ابزارهایی که HMAC دارند، Endpoint قبلی نیز فعال است.</p><pre class="code-block" dir="ltr">POST <?= \CardPay\Core\View::e($baseUrl.\CardPay\Core\Url::to('/api/v1/devices/incoming-sms')) ?>
X-Device-Key: <?= \CardPay\Core\View::e($public) ?>
X-Device-Timestamp: {unix_seconds}
X-Device-Nonce: {random_16+}
X-Device-Signature: {hmac}</pre></details>
<?php endif;?>
<div class="toast" data-toast>کپی شد ✓</div>

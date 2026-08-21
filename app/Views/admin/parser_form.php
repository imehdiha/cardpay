<?php
$edit=!empty($parser['id']);
$positive=$parser['positive_keywords']??'';$negative=$parser['negative_keywords']??'';
if(is_string($positive)&&str_starts_with(trim($positive),'['))$positive=implode("\n",json_decode($positive,true)?:[]);
if(is_string($negative)&&str_starts_with(trim($negative),'['))$negative=implode("\n",json_decode($negative,true)?:[]);
$defaultRegex='/(?<amount>[0-9۰-۹][0-9۰-۹,٬ ]+)/u';
?>
<div class="editor-grid">
  <form class="panel form-panel" data-parser-editor method="post" action="<?= \CardPay\Core\View::e(\CardPay\Core\Url::to($edit?'/admin/parsers/'.$parser['id'].'/edit':'/admin/parsers/create')) ?>">
    <input type="hidden" name="_csrf" value="<?= \CardPay\Core\View::e($csrf) ?>">
    <div class="panel-head"><div><h2>تنظیم Parser</h2><p>Capture group با نام amount یا گروه اول استفاده می‌شود.</p></div></div>
    <div class="fields">
      <label>نام Parser<input name="name" value="<?= \CardPay\Core\View::e($parser['name']??'') ?>" required></label>
      <label>نام بانک<input name="bank_name" value="<?= \CardPay\Core\View::e($parser['bank_name']??'') ?>" required></label>
      <label>الگوی Sender<input name="sender_pattern" value="<?= \CardPay\Core\View::e($parser['sender_pattern']??'') ?>" placeholder="/^BANK$/i" dir="ltr"></label>
      <label>Regex مبلغ<input name="amount_regex" value="<?= \CardPay\Core\View::e($parser['amount_regex']??$defaultRegex) ?>" required dir="ltr"></label>
      <details class="advanced"><summary>تنظیمات پیشرفته</summary><label>Regex تاریخ<input name="date_regex" value="<?= \CardPay\Core\View::e($parser['date_regex']??'') ?>" dir="ltr"></label><label>Regex زمان<input name="time_regex" value="<?= \CardPay\Core\View::e($parser['time_regex']??'') ?>" dir="ltr"></label><label>Regex نوع تراکنش<input name="transaction_type_regex" value="<?= \CardPay\Core\View::e($parser['transaction_type_regex']??'') ?>" dir="ltr"></label></details>
      <label>کلیدواژه‌های واریز (هر خط یکی)<textarea name="positive_keywords" rows="3"><?= \CardPay\Core\View::e($positive) ?></textarea></label>
      <label>کلیدواژه‌های برداشت/مانده<textarea name="negative_keywords" rows="3"><?= \CardPay\Core\View::e($negative) ?></textarea></label>
      <label class="switch"><input type="checkbox" name="is_active" value="1" <?= !isset($parser['is_active'])||$parser['is_active']?'checked':'' ?>><span>Parser فعال باشد</span></label>
    </div>
    <div class="form-actions"><a class="btn secondary" href="<?= \CardPay\Core\View::e(\CardPay\Core\Url::to('/admin/parsers')) ?>">انصراف</a><button class="btn primary">ذخیره Parser</button></div>
  </form>
  <form class="panel form-panel test-panel" data-parser-test method="post" action="<?= \CardPay\Core\View::e(\CardPay\Core\Url::to('/admin/parsers/test')) ?>">
    <input type="hidden" name="_csrf" value="<?= \CardPay\Core\View::e($csrf) ?>"><input type="hidden" name="amount_regex" value="<?= \CardPay\Core\View::e($parser['amount_regex']??$defaultRegex) ?>"><input type="hidden" name="positive_keywords" value="<?= \CardPay\Core\View::e($positive) ?>"><input type="hidden" name="negative_keywords" value="<?= \CardPay\Core\View::e($negative) ?>">
    <div class="panel-head"><div><h2>Test Parser</h2><p>قبل از ذخیره، خروجی را با نمونه واقعی بانک بسنجید.</p></div></div>
    <label>پیامک نمونه<textarea name="sample_sms" rows="8" required><?= \CardPay\Core\View::e($parser['sample_sms']??'') ?></textarea></label><button class="btn secondary wide">اجرای آزمایش</button>
    <?php if($testResult):?><div class="parse-result <?= $testResult['success']?'success':'danger' ?>"><strong><?= $testResult['success']?'مبلغ با موفقیت استخراج شد':'Parser نتیجه قطعی نداد' ?></strong><span><?= $testResult['success']?number_format((int)$testResult['amount']).' ریال':\CardPay\Core\View::e($testResult['error']) ?></span></div><?php endif;?>
  </form>
</div>

<?php $edit=(bool)$card;$action=$edit?'/admin/cards/'.$card['id'].'/edit':'/admin/cards/create';?>
<form class="panel form-panel" method="post" action="<?= \CardPay\Core\View::e(\CardPay\Core\Url::to($action)) ?>">
  <input type="hidden" name="_csrf" value="<?= \CardPay\Core\View::e($csrf) ?>">
  <div class="panel-head"><div><h2><?= $edit?'ویرایش کارت':'کارت بانکی جدید' ?></h2><p>این همان کارتی است که مشتری مبلغ را به آن واریز می‌کند.</p></div></div>
  <div class="fields two">
    <label>یک نام ساده برای کارت<input name="title" value="<?= \CardPay\Core\View::e($card['title']??'کارت فروشگاه') ?>" required></label>
    <label>نام بانک<input name="bank_name" list="bank-names" value="<?= \CardPay\Core\View::e($card['bank_name']??'بانک سامان') ?>" required><datalist id="bank-names"><option value="بانک سامان"></datalist></label>
    <label>شماره کارت<input name="card_number" inputmode="numeric" pattern="[0-9۰-۹ -]{16,23}" placeholder="<?= $edit?'برای حفظ مقدار خالی بگذارید':'6037 0000 0000 0000' ?>" <?= $edit?'':'required' ?> dir="ltr"></label>
    <label>نام دارنده کارت<input name="card_holder_name" value="<?= \CardPay\Core\View::e($card['card_holder_name']??'') ?>" required></label>
    <label class="full">الگوی پیامک بانک<select name="sms_parser_id"><option value="">انتخاب کنید</option><?php foreach($parsers as$p):?><option value="<?= (int)$p['id'] ?>" <?= (int)($card['sms_parser_id']??0)===(int)$p['id']?'selected':'' ?>><?= \CardPay\Core\View::e($p['name']) ?> — <?= \CardPay\Core\View::e($p['bank_name']) ?></option><?php endforeach;?></select><small>برای بانک سامان، گزینه «واریز بانک سامان» را انتخاب کنید.</small></label>
    <details class="advanced full"><summary>اطلاعات اختیاری</summary><label>شماره شبا<input name="iban" dir="ltr"></label><label>توضیح<textarea name="description" rows="2"><?= \CardPay\Core\View::e($card['description']??'') ?></textarea></label></details>
    <?php if($edit):?><label class="switch"><input name="is_active" type="checkbox" value="1" <?= $card['is_active']?'checked':'' ?>><span>این کارت فعال باشد</span></label><?php endif;?>
  </div>
  <div class="form-actions"><a class="btn secondary" href="<?= \CardPay\Core\View::e(\CardPay\Core\Url::to('/admin/setup')) ?>">بازگشت</a><button class="btn primary">ذخیره کارت</button></div>
</form>

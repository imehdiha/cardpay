<?php
$status=$payment['status'];
$digits=preg_replace('/\D/','',$payment['card_number']);
$card=implode(' ',str_split($digits,4));
$appName=$settings['app_name']??'CardPay';
$payable=(int)$payment['payable_amount'];
$original=(int)$payment['original_amount'];
$token=(int)$payment['token'];
?>
<main class="pay-shell pay-v2" data-payment-page data-status-url="<?= \CardPay\Core\View::e(\CardPay\Core\Url::to('/api/v1/public/payments/'.$payment['public_id'].'/status')) ?>" data-expires="<?= \CardPay\Core\View::e(gmdate('c',strtotime($payment['expires_at'].' UTC'))) ?>" data-status="<?= \CardPay\Core\View::e($status) ?>">
  <header class="pay-v2-header">
    <div class="pay-v2-brand">
      <?php if(!empty($settings['logo_path'])):?><span class="pay-v2-logo brand-image"><img src="<?= \CardPay\Core\View::e(\CardPay\Core\Url::to('/uploads/'.basename($settings['logo_path']))) ?>" alt=""><?php else:?><span class="pay-v2-logo">C<?php endif;?></span>
      <span><strong><?= \CardPay\Core\View::e($appName) ?></strong><small>پرداخت امن کارت‌به‌کارت</small></span>
    </div>
    <span class="pay-v2-secure"><i></i> امن</span>
  </header>

  <section class="pay-frame pay-v2-frame">
    <?php if(isset($_GET['review'])):?><div class="alert success pay-alert">درخواست بررسی شما ثبت شد.</div><?php endif;?>

    <div class="status-view status-paid <?= $status==='paid'?'active':'' ?>" data-state="paid"><div class="success-orb">✓</div><span class="state-kicker success">پرداخت کامل شد</span><h1>واریز شما تأیید شد</h1><p><?= \CardPay\Core\View::e($settings['success_text']??'پرداخت شما با موفقیت تأیید شد.') ?></p><div class="final-amount"><span>مبلغ پرداخت‌شده</span><strong><?= number_format($payable) ?> ریال</strong></div><?php if($payment['return_url']):?><a class="btn primary wide return-button" href="<?= \CardPay\Core\View::e($payment['return_url']) ?>">بازگشت به فروشگاه</a><?php endif;?></div>

    <div class="status-view status-expired <?= in_array($status,['expired','canceled','rejected'],true)?'active':'' ?>" data-state="expired"><div class="expired-orb">×</div><span class="state-kicker danger">پرداخت انجام نشد</span><h1><?= $status==='canceled'?'پرداخت لغو شد':'زمان پرداخت به پایان رسید' ?></h1><p><?= \CardPay\Core\View::e($settings['expired_text']??'این مبلغ دیگر برای تأیید خودکار معتبر نیست. یک پرداخت تازه بسازید.') ?></p><?php if($payment['return_url']):?><a class="btn secondary wide return-button" href="<?= \CardPay\Core\View::e($payment['return_url']) ?>">بازگشت به فروشگاه</a><?php endif;?></div>

    <div class="status-view status-review <?= $status==='manual_review'?'active':'' ?>" data-state="manual_review"><div class="review-orb">…</div><span class="state-kicker review">در صف بررسی</span><h1>در حال بررسی واریز</h1><p>اطلاعات شما ثبت شده است و نتیجه در همین صفحه نمایش داده می‌شود.</p></div>

    <div class="status-view status-pending <?= $status==='pending'?'active':'' ?>" data-state="pending">
      <div class="pay-v2-order">
        <span><small>پرداخت به</small><strong><?= \CardPay\Core\View::e($payment['application_name']) ?></strong></span>
        <span class="pay-v2-timer"><small>زمان باقی‌مانده</small><strong data-countdown>--:--</strong></span>
      </div>

      <div class="pay-v2-intro"><h1>پرداخت کارت‌به‌کارت</h1><p>مبلغ و شماره کارت را کپی و در اپ بانکی خود وارد کنید.</p></div>

      <section class="pay-v2-amount" aria-label="مبلغ قابل پرداخت">
        <span>مبلغ دقیق پرداخت</span>
        <div><strong><?= number_format($payable) ?></strong><small>ریال</small></div>
        <p>این مبلغ را کم، زیاد یا گرد نکنید.</p>
        <button class="pay-v2-copy primary" type="button" data-copy="<?= $payable ?>" data-copy-label="کپی مبلغ">کپی مبلغ</button>
      </section>

      <section class="pay-v2-destination" aria-label="کارت مقصد">
        <div class="pay-v2-section-head"><span><small>کارت مقصد</small><strong><?= \CardPay\Core\View::e($payment['bank_name']) ?></strong></span><em>•••• <?= \CardPay\Core\View::e(substr($digits,-4)) ?></em></div>
        <div class="pay-v2-card-number" dir="ltr"><?= \CardPay\Core\View::e($card) ?></div>
        <div class="pay-v2-holder"><small>به نام</small><strong><?= \CardPay\Core\View::e($payment['card_holder_name']) ?></strong></div>
        <button type="button" class="pay-v2-copy secondary" data-copy="<?= \CardPay\Core\View::e($digits) ?>" data-copy-label="کپی شماره کارت">کپی شماره کارت</button>
      </section>

      <div class="pay-v2-wait"><span class="pulse-dot"></span><div><strong>پس از واریز همین صفحه را باز نگه دارید</strong><small>تأیید پرداخت به‌صورت خودکار انجام می‌شود؛ نیازی به ارسال رسید نیست.</small></div></div>

      <details class="pay-v2-details"><summary>جزئیات پرداخت</summary><dl><div><dt>مبلغ سفارش</dt><dd><?= number_format($original) ?> ریال</dd></div><div><dt>شناسه تشخیص خودکار</dt><dd>+<?= number_format($token) ?></dd></div><div><dt>شناسه پرداخت</dt><dd dir="ltr"><?= \CardPay\Core\View::e($payment['public_id']) ?></dd></div><?php if($payment['description']):?><div><dt>بابت</dt><dd><?= \CardPay\Core\View::e($payment['description']) ?></dd></div><?php endif;?></dl></details>

      <details class="review-form pay-v2-review"><summary>پرداخت انجام شد ولی تأیید نشد؟</summary><form method="post" enctype="multipart/form-data" action="<?= \CardPay\Core\View::e(\CardPay\Core\Url::to('/p/'.$payment['public_id'].'/manual-review')) ?>"><input type="hidden" name="_csrf" value="<?= \CardPay\Core\View::e($csrf) ?>"><label>مبلغی که واریز کردید<input name="reported_amount" type="number" min="1" required inputmode="numeric"></label><label>زمان تقریبی واریز<input name="approximate_paid_at" type="datetime-local"></label><label>شماره تماس<input name="contact_mobile" value="<?= \CardPay\Core\View::e($payment['customer_mobile']) ?>" inputmode="tel"></label><label>توضیح کوتاه<textarea name="customer_note" rows="3"></textarea></label><label>تصویر رسید، اختیاری<input name="receipt" type="file" accept="image/jpeg,image/png,application/pdf"></label><button class="btn secondary wide" type="submit">ارسال برای بررسی</button></form></details>
    </div>
  </section>

  <footer class="pay-v2-footer"><span>تأیید فقط پس از دریافت پیامک واریز بانکی انجام می‌شود.</span><?php if(!empty($settings['support_contact'])):?><span><?= \CardPay\Core\View::e($settings['support_contact']) ?></span><?php endif;?></footer>
</main>
<div class="toast" data-toast role="status" aria-live="polite">کپی شد ✓</div>

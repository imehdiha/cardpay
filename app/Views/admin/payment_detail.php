<div class="detail-grid">
  <section class="panel">
    <div class="panel-head"><div><h2><?= \CardPay\Core\View::e($payment['public_id']) ?></h2><p><?= \CardPay\Core\View::e($payment['application_name']) ?></p></div><span class="badge <?= $payment['status']==='paid'?'success':($payment['status']==='pending'?'warning':'neutral') ?>"><?= \CardPay\Core\View::e($payment['status']) ?></span></div>
    <dl class="detail-list">
      <div><dt>مبلغ اصلی</dt><dd><?= number_format((int)$payment['original_amount']) ?> ریال</dd></div>
      <div><dt>توکن</dt><dd><?= (int)$payment['token'] ?></dd></div>
      <div class="highlight"><dt>مبلغ قابل پرداخت</dt><dd><?= number_format((int)$payment['payable_amount']) ?> ریال</dd></div>
      <div><dt>کارت مقصد</dt><dd><?= \CardPay\Core\View::e($payment['card_title']) ?> — <?= \CardPay\Core\View::e($payment['bank_name']) ?></dd></div>
      <div><dt>شناسه سفارش</dt><dd><?= \CardPay\Core\View::e($payment['external_order_id']??'—') ?></dd></div>
      <div><dt>مشتری</dt><dd><?= \CardPay\Core\View::e($payment['customer_name']??'—') ?> · <?= \CardPay\Core\View::e($payment['customer_mobile']??'') ?></dd></div>
      <div><dt>ایجاد</dt><dd><?= \CardPay\Core\View::e(\CardPay\Core\View::localDate($payment['created_at'],(string)($settings['timezone']??'Asia/Tehran'))) ?></dd></div>
      <div><dt>انقضا</dt><dd><?= \CardPay\Core\View::e(\CardPay\Core\View::localDate($payment['expires_at'],(string)($settings['timezone']??'Asia/Tehran'))) ?></dd></div>
      <div><dt>پرداخت</dt><dd><?= \CardPay\Core\View::e(\CardPay\Core\View::localDate($payment['paid_at']??null,(string)($settings['timezone']??'Asia/Tehran'))) ?></dd></div>
    </dl>
    <a class="btn secondary wide" target="_blank" rel="noopener" href="<?= \CardPay\Core\View::e(\CardPay\Core\Url::to('/p/'.$payment['public_id'])) ?>">بازکردن صفحه پرداخت</a>
  </section>
  <aside class="stack-panels">
    <section class="panel"><div class="panel-head"><h2>تطبیق‌ها</h2></div><?php if(!$matches):?><div class="empty">تطبیقی ثبت نشده است.</div><?php else:foreach($matches as$row):?><div class="timeline-row"><span>✓</span><div><strong><?= \CardPay\Core\View::e($row['match_type']) ?></strong><p><?= number_format((int)$row['parsed_amount']) ?> ریال — <?= \CardPay\Core\View::e($row['sender']) ?></p></div><time><?= \CardPay\Core\View::e(\CardPay\Core\View::localDate($row['created_at'],(string)($settings['timezone']??'Asia/Tehran'))) ?></time></div><?php endforeach;endif;?></section>
    <section class="panel"><div class="panel-head"><h2>رویدادهای Webhook</h2></div><?php if(!$events):?><div class="empty">رویدادی ایجاد نشده است.</div><?php else:foreach($events as$row):?><div class="timeline-row"><span>⇢</span><div><strong><?= \CardPay\Core\View::e($row['event_type']) ?></strong><code><?= \CardPay\Core\View::e($row['event_id']) ?></code></div><time><?= \CardPay\Core\View::e(\CardPay\Core\View::localDate($row['created_at'],(string)($settings['timezone']??'Asia/Tehran'))) ?></time></div><?php endforeach;endif;?></section>
  </aside>
</div>

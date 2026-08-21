<section class="panel">
  <form class="filter-bar" method="get">
    <label>از<input name="from" type="date" value="<?= \CardPay\Core\View::e($from) ?>"></label>
    <label>تا<input name="to" type="date" value="<?= \CardPay\Core\View::e($to) ?>"></label>
    <button class="btn secondary small">اعمال بازه</button>
    <a class="btn primary small" href="<?= \CardPay\Core\View::e(\CardPay\Core\Url::to('/admin/reports.csv?from='.urlencode($from).'&to='.urlencode($to))) ?>">خروجی CSV</a>
  </form>
  <div class="summary-strip"><div><small>تعداد کل</small><strong><?= number_format($summary['count']) ?></strong></div><div><small>موفق</small><strong><?= number_format($summary['paid_count']) ?></strong></div><div><small>مجموع موفق</small><strong><?= number_format($summary['paid_amount']) ?> <em>ریال</em></strong></div></div>
  <div class="table-wrap"><table><thead><tr><th>شناسه</th><th>پروژه</th><th>کارت</th><th>مبلغ</th><th>وضعیت</th><th>تاریخ</th></tr></thead><tbody>
    <?php if(!$rows):?><tr><td colspan="6"><div class="empty">داده‌ای در این بازه نیست.</div></td></tr><?php else:foreach($rows as$row):?>
      <tr><td><a href="<?= \CardPay\Core\View::e(\CardPay\Core\Url::to('/admin/payments/'.$row['public_id'])) ?>"><code><?= \CardPay\Core\View::e($row['public_id']) ?></code></a></td><td><?= \CardPay\Core\View::e($row['application_name']) ?></td><td><?= \CardPay\Core\View::e($row['card_title']) ?></td><td><?= number_format((int)$row['payable_amount']) ?></td><td><span class="badge <?= $row['status']==='paid'?'success':'neutral' ?>"><?= \CardPay\Core\View::e($row['status']) ?></span></td><td><?= \CardPay\Core\View::e(\CardPay\Core\View::localDate($row['created_at'],(string)($settings['timezone']??'Asia/Tehran'))) ?></td></tr>
    <?php endforeach;endif;?>
  </tbody></table></div>
</section>

<section class="login-card">
  <div class="brand-mark">C</div><p class="eyebrow">CARDPAY CONSOLE</p><h1>خوش آمدید</h1><p class="muted">برای مدیریت پرداخت‌ها وارد حساب مدیر شوید.</p>
  <?php if($flash):?><div class="alert <?= \CardPay\Core\View::e($flash['type']) ?>"><?= \CardPay\Core\View::e($flash['message']) ?></div><?php endif;?>
  <form method="post" action="<?= \CardPay\Core\View::e(\CardPay\Core\Url::to('/login')) ?>" class="stack"><input type="hidden" name="_csrf" value="<?= \CardPay\Core\View::e($csrf) ?>"><label>ایمیل یا نام کاربری<input name="username" required autofocus autocomplete="username"></label><label>رمز عبور<input name="password" type="password" required autocomplete="current-password"></label><button class="btn primary wide" type="submit">ورود امن</button></form>
  <p class="tiny">نشست شما با Cookie امن و HttpOnly محافظت می‌شود.</p>
</section>

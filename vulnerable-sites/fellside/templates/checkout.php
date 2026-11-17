<?php
$field = function (string $name, string $label, string $type = 'text', string $auto = '', string $hint = '') use ($form, $errors) {
    $err = $errors[$name] ?? null; ?>
    <div class="field<?= $err ? ' has-error' : '' ?>">
      <label for="<?= $name ?>"><?= $label ?></label>
      <?php if ($hint): ?><p class="field-hint" id="<?= $name ?>-hint"><?= $hint ?></p><?php endif; ?>
      <?php if ($err): ?><p class="field-error" id="<?= $name ?>-err"><?= e($err) ?></p><?php endif; ?>
      <?php if ($type === 'textarea'): ?>
        <textarea id="<?= $name ?>" name="<?= $name ?>" rows="3" autocomplete="<?= $auto ?>"><?= e($form[$name] ?? '') ?></textarea>
      <?php else: ?>
        <input id="<?= $name ?>" name="<?= $name ?>" type="<?= $type ?>" autocomplete="<?= $auto ?>" value="<?= $name === 'card' ? '' : e($form[$name] ?? '') ?>"<?= $err ? ' aria-invalid="true" aria-describedby="' . $name . '-err"' : '' ?>>
      <?php endif; ?>
    </div>
<?php }; ?>
<div class="wrap page">
  <header class="page-head"><h1>Checkout</h1>
    <?php if (!$u): ?><p>Have an account? <a href="/login?next=/checkout">Sign in</a> to fill in your details and keep track of this order.</p><?php endif; ?>
  </header>

  <div class="basket-layout">
    <form method="post" action="/checkout" class="checkout-form" novalidate>
      <?= csrf_field() ?>
      <?php if ($errors): ?><p class="flash flash-error" role="alert">Check the <?= count($errors) === 1 ? 'field' : count($errors) . ' fields' ?> marked below.</p><?php endif; ?>
      <fieldset>
        <legend>Delivery</legend>
        <?php $field('full_name', 'Full name', 'text', 'name'); ?>
        <?php $field('email', 'Email', 'email', 'email', 'We send the receipt and tracking link here.'); ?>
        <?php $field('address', 'Address', 'textarea', 'street-address'); ?>
        <?php $field('postcode', 'Postcode', 'text', 'postal-code'); ?>
      </fieldset>
      <fieldset>
        <legend>Payment</legend>
        <?php $field('card', 'Card number', 'text', 'cc-number', 'This is a test shop. Any 16 digits will do; nothing is charged.'); ?>
        <div class="field-row">
          <div class="field"><label for="exp">Expiry</label><input id="exp" name="exp" placeholder="MM/YY" autocomplete="cc-exp"></div>
          <div class="field"><label for="cvc">Security code</label><input id="cvc" name="cvc" inputmode="numeric" autocomplete="cc-csc"></div>
        </div>
      </fieldset>
      <button class="btn btn-primary btn-block" type="submit">Pay <?= money($t['total']) ?></button>
    </form>

    <aside class="summary" aria-labelledby="sum-h">
      <h2 id="sum-h">Your order</h2>
      <ul class="mini-lines">
        <?php foreach ($lines as $l): ?>
        <li><span><?= (int) $l['qty'] ?> × <?= e($l['name']) ?></span><span><?= money((float) $l['line_total']) ?></span></li>
        <?php endforeach; ?>
      </ul>
      <dl class="totals">
        <div><dt>Subtotal</dt><dd><?= money($t['subtotal']) ?></dd></div>
        <?php if ($t['discount'] > 0): ?><div><dt>Code <?= e($t['coupon']['code']) ?></dt><dd>−<?= money($t['discount']) ?></dd></div><?php endif; ?>
        <div><dt>Delivery</dt><dd><?= $t['delivery'] > 0 ? money($t['delivery']) : 'Free' ?></dd></div>
        <div class="grand"><dt>Total</dt><dd><?= money($t['total']) ?></dd></div>
      </dl>
      <a href="/basket">Change basket</a>
    </aside>
  </div>
</div>

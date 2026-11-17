<?php $err = fn($k) => isset($errors[$k]) ? '<p class="field-error">' . e($errors[$k]) . '</p>' : ''; ?>
<div class="wrap page contact-layout">
  <div>
    <h1>Contact us</h1>
    <p>Questions about sizing, an order or a repair. We reply within one working day.</p>
    <dl class="spec">
      <div><dt>Shop</dt><dd>14 Kirkgate, Otley LS21 3HJ</dd></div>
      <div><dt>Hours</dt><dd>Tuesday to Saturday, 9.30 to 5</dd></div>
      <div><dt>Email</dt><dd><a href="mailto:<?= e(config('shop.support_email')) ?>"><?= e(config('shop.support_email')) ?></a></dd></div>
    </dl>
  </div>

  <div class="panel">
  <?php if ($sent): ?>
    <h2>Message sent</h2>
    <p>Thanks, <?= e($form['name']) ?>. We will reply to <?= e($form['email']) ?> within one working day.</p>
    <p><a href="/shop">Back to the shop</a></p>
  <?php else: ?>
    <form method="post" action="/contact" novalidate>
      <?= csrf_field() ?>
      <div class="field<?= isset($errors['name']) ? ' has-error' : '' ?>"><label for="name">Name</label><?= $err('name') ?><input id="name" name="name" autocomplete="name" value="<?= e($form['name']) ?>"></div>
      <div class="field<?= isset($errors['email']) ? ' has-error' : '' ?>"><label for="email">Email</label><?= $err('email') ?><input id="email" name="email" type="email" autocomplete="email" value="<?= e($form['email']) ?>"></div>
      <div class="field"><label for="order_ref">Order number</label><p class="field-hint">Optional. It starts with FS-.</p><input id="order_ref" name="order_ref" value="<?= e($form['order_ref']) ?>"></div>
      <div class="field<?= isset($errors['body']) ? ' has-error' : '' ?>"><label for="body">Message</label><?= $err('body') ?><textarea id="body" name="body" rows="6"><?= e($form['body']) ?></textarea></div>
      <button class="btn btn-primary" type="submit">Send message</button>
    </form>
  <?php endif; ?>
  </div>
</div>

<div class="wrap page narrow-wide">
  <header class="page-head"><h1>Your basket</h1></header>

  <?php if (!$lines): ?>
    <div class="empty">
      <p>Your basket is empty. Start with <a href="/shop?category=jackets-layers">jackets and layers</a>, or <a href="/shop">browse all kit</a>.</p>
    </div>
  <?php else: ?>
  <div class="basket-layout">
    <form method="post" action="/basket/update" class="basket-lines">
      <?= csrf_field() ?>
      <table class="lines">
        <thead><tr><th scope="col">Item</th><th scope="col">Quantity</th><th scope="col" class="num">Price</th><th scope="col"><span class="sr">Remove</span></th></tr></thead>
        <tbody>
        <?php foreach ($lines as $l): ?>
          <tr>
            <td class="line-item">
              <?= product_image($l, 'pimg pimg-sm') ?>
              <span><a href="/product?id=<?= (int) $l['id'] ?>"><?= e($l['name']) ?></a><br><span class="muted"><?= e($l['colour']) ?>, <?= money((float) $l['price']) ?> each</span></span>
            </td>
            <td><label class="sr" for="q<?= (int) $l['id'] ?>">Quantity of <?= e($l['name']) ?></label>
              <input id="q<?= (int) $l['id'] ?>" class="qty" type="number" name="qty[<?= (int) $l['id'] ?>]" value="<?= (int) $l['qty'] ?>" min="0" max="20" data-autosubmit></td>
            <td class="num"><?= money((float) $l['line_total']) ?></td>
            <td><button class="link-btn" name="remove" value="<?= (int) $l['id'] ?>">Remove</button></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <noscript><button class="btn btn-small" type="submit">Update quantities</button></noscript>
    </form>

    <aside class="summary" aria-labelledby="sum-h">
      <h2 id="sum-h">Order summary</h2>
      <dl class="totals">
        <div><dt>Subtotal</dt><dd><?= money($t['subtotal']) ?></dd></div>
        <?php if ($t['discount'] > 0): ?><div><dt>Code <?= e($t['coupon']['code']) ?></dt><dd>−<?= money($t['discount']) ?></dd></div><?php endif; ?>
        <div><dt>Delivery</dt><dd><?= $t['delivery'] > 0 ? money($t['delivery']) : 'Free' ?></dd></div>
        <div class="grand"><dt>Total</dt><dd><?= money($t['total']) ?></dd></div>
      </dl>
      <?php if ($t['to_free'] > 0): ?><p class="hint">Spend <?= money($t['to_free']) ?> more for free delivery.</p><?php endif; ?>

      <form method="post" action="/basket/coupon" class="coupon">
        <?= csrf_field() ?>
        <label for="code">Discount code</label>
        <div class="inline-field">
          <input id="code" name="code" value="<?= e($t['coupon']['code'] ?? '') ?>" autocomplete="off">
          <button class="btn btn-small" type="submit">Apply</button>
        </div>
      </form>
      <a class="btn btn-primary btn-block" href="/checkout">Go to checkout</a>
    </aside>
  </div>
  <?php endif; ?>
</div>

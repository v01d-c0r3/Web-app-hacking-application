<div class="wrap page narrow">
  <header class="page-head">
    <?php if ($placed): ?>
      <h1>Order placed</h1>
      <p>Thanks, <?= e(explode(' ', $o['full_name'])[0]) ?>. We have emailed a receipt to <?= e($o['email']) ?>. Your order number is <strong>FS-<?= str_pad((string) $o['id'], 5, '0', STR_PAD_LEFT) ?></strong>.</p>
    <?php else: ?>
      <h1>Order FS-<?= str_pad((string) $o['id'], 5, '0', STR_PAD_LEFT) ?></h1>
      <p>Placed <?= date('j F Y \a\t H:i', strtotime($o['created_at'])) ?>. Status: <span class="status status-<?= e(strtolower($o['status'])) ?>"><?= e($o['status']) ?></span></p>
    <?php endif; ?>
  </header>

  <table class="lines">
    <thead><tr><th scope="col">Item</th><th scope="col">Qty</th><th scope="col" class="num">Price</th></tr></thead>
    <tbody>
    <?php foreach ($items as $i): ?>
      <tr>
        <td class="line-item"><?= product_image($i, 'pimg pimg-sm') ?><a href="/product?id=<?= (int) $i['product_id'] ?>"><?= e($i['name']) ?></a></td>
        <td><?= (int) $i['quantity'] ?></td>
        <td class="num"><?= money($i['price'] * $i['quantity']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <div class="order-foot">
    <div>
      <h2>Delivering to</h2>
      <p><?= e($o['full_name']) ?><br><?= nl2br(e($o['address'])) ?><br><?= e($o['postcode']) ?></p>
      <p class="muted">Paid with card ending <?= e($o['card_last4']) ?></p>
    </div>
    <dl class="totals">
      <div><dt>Subtotal</dt><dd><?= money((float) $o['subtotal']) ?></dd></div>
      <?php if ($o['discount'] > 0): ?><div><dt>Code <?= e($o['coupon_code']) ?></dt><dd>−<?= money((float) $o['discount']) ?></dd></div><?php endif; ?>
      <div><dt>Delivery</dt><dd><?= $o['delivery'] > 0 ? money((float) $o['delivery']) : 'Free' ?></dd></div>
      <div class="grand"><dt>Total</dt><dd><?= money((float) $o['total']) ?></dd></div>
    </dl>
  </div>
  <p><a href="/shop">Keep shopping</a></p>
</div>

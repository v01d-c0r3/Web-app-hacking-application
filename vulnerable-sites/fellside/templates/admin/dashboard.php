<div class="wrap page admin">
  <h1>Shop admin</h1>
  <?php require __DIR__ . '/_nav.php'; ?>

  <dl class="stat-row">
    <div><dt>Orders</dt><dd><?= (int) $stats['orders'] ?></dd></div>
    <div><dt>Takings</dt><dd><?= money((float) $stats['revenue']) ?></dd></div>
    <div><dt>Customers</dt><dd><?= (int) $stats['customers'] ?></dd></div>
    <div><dt>Messages</dt><dd><?= (int) $stats['messages'] ?></dd></div>
  </dl>

  <div class="account-grid">
    <section>
      <h2>Recent orders</h2>
      <table class="lines">
        <thead><tr><th scope="col">Order</th><th scope="col">Customer</th><th scope="col">Status</th><th scope="col" class="num">Total</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $o): ?>
          <tr><td><a href="/order?id=<?= (int) $o['id'] ?>">FS-<?= str_pad((string) $o['id'], 5, '0', STR_PAD_LEFT) ?></a></td><td><?= e($o['full_name']) ?></td><td><span class="status status-<?= e(strtolower($o['status'])) ?>"><?= e($o['status']) ?></span></td><td class="num"><?= money((float) $o['total']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </section>
    <section class="panel">
      <h2>Running low</h2>
      <?php if (!$low): ?><p class="muted">Nothing is below 9 in stock.</p><?php endif; ?>
      <ul class="mini-lines">
        <?php foreach ($low as $p): ?><li><a href="/admin/product?id=<?= (int) $p['id'] ?>"><?= e($p['name']) ?></a><span><?= (int) $p['stock'] ?> left</span></li><?php endforeach; ?>
      </ul>
    </section>
  </div>
</div>

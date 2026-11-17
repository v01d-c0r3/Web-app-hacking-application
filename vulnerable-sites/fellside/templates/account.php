<div class="wrap page">
  <header class="page-head account-head">
    <h1>Hello, <?= e(explode(' ', $u['full_name'])[0]) ?></h1>
    <form method="post" action="/logout"><?= csrf_field() ?><button class="link-btn">Sign out</button></form>
  </header>

  <div class="account-grid">
    <section aria-labelledby="orders-h">
      <h2 id="orders-h">Your orders</h2>
      <?php if (!$orders): ?>
        <p class="muted">You have not ordered anything yet. <a href="/shop">Browse the shop</a>.</p>
      <?php else: ?>
      <table class="lines">
        <thead><tr><th scope="col">Order</th><th scope="col">Date</th><th scope="col">Status</th><th scope="col" class="num">Total</th></tr></thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td><a href="/order?id=<?= (int) $o['id'] ?>">FS-<?= str_pad((string) $o['id'], 5, '0', STR_PAD_LEFT) ?></a><br><span class="muted"><?= (int) $o['items'] ?> <?= $o['items'] == 1 ? 'item' : 'items' ?></span></td>
            <td><?= date('j M Y', strtotime($o['created_at'])) ?></td>
            <td><span class="status status-<?= e(strtolower($o['status'])) ?>"><?= e($o['status']) ?></span></td>
            <td class="num"><?= money((float) $o['total']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </section>

    <section aria-labelledby="details-h" class="panel">
      <h2 id="details-h">Your details</h2>
      <form method="post" action="/account">
        <?= csrf_field() ?>
        <div class="field"><label for="full_name">Full name</label><input id="full_name" name="full_name" value="<?= e($u['full_name']) ?>"></div>
        <div class="field"><label>Email</label><p class="static"><?= e($u['email']) ?></p></div>
        <div class="field"><label for="address">Address</label><textarea id="address" name="address" rows="3"><?= e($u['address']) ?></textarea></div>
        <div class="field"><label for="postcode">Postcode</label><input id="postcode" name="postcode" value="<?= e($u['postcode']) ?>"></div>
        <button class="btn" type="submit">Save details</button>
      </form>
    </section>
  </div>
</div>

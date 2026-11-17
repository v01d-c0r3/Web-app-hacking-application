<div class="wrap page admin">
  <h1>Orders</h1>
  <?php require __DIR__ . '/_nav.php'; ?>
  <table class="lines">
    <thead><tr><th scope="col">Order</th><th scope="col">Customer</th><th scope="col">Placed</th><th scope="col" class="num">Total</th><th scope="col">Status</th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td><a href="/order?id=<?= (int) $o['id'] ?>">FS-<?= str_pad((string) $o['id'], 5, '0', STR_PAD_LEFT) ?></a><br><span class="muted"><?= (int) $o['items'] ?> items</span></td>
        <td><?= e($o['full_name']) ?><br><span class="muted"><?= e($o['email']) ?></span></td>
        <td><?= date('j M Y, H:i', strtotime($o['created_at'])) ?></td>
        <td class="num"><?= money((float) $o['total']) ?></td>
        <td>
          <form method="post" class="inline-field">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
            <label class="sr" for="s<?= (int) $o['id'] ?>">Status</label>
            <select id="s<?= (int) $o['id'] ?>" name="status" data-autosubmit>
              <?php foreach (['Paid', 'Packed', 'Dispatched', 'Refunded'] as $s): ?><option <?= $o['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
            </select>
            <noscript><button class="btn btn-small">Update</button></noscript>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

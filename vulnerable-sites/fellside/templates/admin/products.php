<div class="wrap page admin">
  <div class="section-head"><h1>Products</h1><a class="btn btn-small" href="/admin/product">Add a product</a></div>
  <?php require __DIR__ . '/_nav.php'; ?>
  <table class="lines">
    <thead><tr><th scope="col">Product</th><th scope="col">Category</th><th scope="col" class="num">Price</th><th scope="col" class="num">Stock</th></tr></thead>
    <tbody>
    <?php foreach ($products as $p): ?>
      <tr>
        <td class="line-item"><?= product_image($p, 'pimg pimg-sm') ?><a href="/admin/product?id=<?= (int) $p['id'] ?>"><?= e($p['name']) ?></a><?= $p['featured'] ? ' <span class="tag">Featured</span>' : '' ?></td>
        <td><?= e($p['category']) ?></td>
        <td class="num"><?= money((float) $p['price']) ?></td>
        <td class="num"><?= (int) $p['stock'] ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

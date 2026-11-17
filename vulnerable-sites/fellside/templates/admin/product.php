<div class="wrap page admin">
  <h1><?= $p ? 'Edit ' . e($p['name']) : 'Add a product' ?></h1>
  <?php require __DIR__ . '/_nav.php'; ?>
  <form method="post" enctype="multipart/form-data" class="admin-form">
    <?= csrf_field() ?>
    <div class="admin-form-grid">
      <div>
        <div class="field"><label for="name">Name</label><input id="name" name="name" required value="<?= e($p['name'] ?? '') ?>"></div>
        <div class="field"><label for="summary">Summary</label><p class="field-hint">One line, shown under the product name.</p><input id="summary" name="summary" value="<?= e($p['summary'] ?? '') ?>"></div>
        <div class="field"><label for="description">Description</label><p class="field-hint">Leave a blank line between paragraphs.</p><textarea id="description" name="description" rows="8"><?= e($p['description'] ?? '') ?></textarea></div>
      </div>
      <div>
        <div class="field"><label for="category_id">Category</label>
          <select id="category_id" name="category_id">
            <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>" <?= ($p['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
          </select></div>
        <div class="field-row">
          <div class="field"><label for="price">Price (£)</label><input id="price" name="price" inputmode="decimal" value="<?= e($p['price'] ?? '') ?>"></div>
          <div class="field"><label for="stock">Stock</label><input id="stock" name="stock" type="number" min="0" value="<?= e($p['stock'] ?? 10) ?>"></div>
        </div>
        <div class="field"><label for="weight_g">Weight (grams)</label><input id="weight_g" name="weight_g" type="number" min="0" value="<?= e($p['weight_g'] ?? '') ?>"></div>
        <div class="field"><label class="check"><input type="checkbox" name="featured" <?= !empty($p['featured']) ? 'checked' : '' ?>> Show on the home page</label></div>
        <div class="field"><label for="image">Photo</label><p class="field-hint">JPEG, PNG or WebP. Replaces the illustration.</p><input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp"></div>
        <?php if ($p): ?><?= product_image($p, 'pimg pimg-sm') ?><?php endif; ?>
      </div>
    </div>
    <p class="form-actions"><button class="btn btn-primary" type="submit">Save product</button> <?php if ($p): ?><a href="/product?id=<?= (int) $p['id'] ?>">View in shop</a><?php endif; ?></p>
  </form>
</div>

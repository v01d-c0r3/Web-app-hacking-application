<article class="card">
  <a href="/product?id=<?= (int) $p['id'] ?>" class="card-link">
    <?= product_image($p) ?>
    <h3 class="card-name"><?= e($p['name']) ?></h3>
  </a>
  <p class="card-meta">
    <span class="price"><?= money((float) $p['price']) ?></span>
    <?php if ($p['weight_g']): ?><span class="weight"><?= grams((int) $p['weight_g']) ?></span><?php endif; ?>
  </p>
  <p class="card-colour"><span class="dot" style="background:<?= e($p['swatch']) ?>"></span><?= e($p['colour']) ?></p>
</article>

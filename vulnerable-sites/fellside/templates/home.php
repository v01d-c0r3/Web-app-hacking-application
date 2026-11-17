<section class="hero">
  <div class="hero-map"><?= file_get_contents(ROOT . '/templates/partials_contours.svg') ?></div>
  <div class="wrap hero-inner">
    <h1 class="hero-title">Kit for wet days on the tops</h1>
    <p class="hero-lede">Waterproofs, boots and packs from a small shop in Otley. Everything we sell has been carried up a Yorkshire hill in bad weather first.</p>
    <p class="hero-actions">
      <a class="btn btn-primary" href="/shop">Shop all kit</a>
      <a class="btn btn-plain" href="/about">How we test</a>
    </p>
  </div>
  <a class="hero-pin" href="/product?id=2" style="--x:68%;--y:34%">
    <span class="pin-dot" aria-hidden="true"></span>
    <span class="pin-label"><strong>Kinder 3-layer shell</strong> tested on Kinder Scout, SK 085 888</span>
  </a>
</section>

<section class="wrap cats" aria-labelledby="cats-h">
  <h2 id="cats-h" class="sr">Shop by category</h2>
  <ul class="cat-list">
    <?php foreach ($categories as $c): ?>
    <li>
      <a href="/shop?category=<?= e($c['slug']) ?>">
        <span class="cat-name"><?= e($c['name']) ?></span>
        <span class="cat-n"><?= (int) $c['n'] ?> items</span>
      </a>
      <p><?= e($c['blurb']) ?></p>
    </li>
    <?php endforeach; ?>
  </ul>
</section>

<section class="wrap section" aria-labelledby="feat-h">
  <div class="section-head">
    <h2 id="feat-h">What we reach for first</h2>
    <a href="/shop">See everything</a>
  </div>
  <div class="grid">
    <?php foreach ($featured as $p) require ROOT . '/templates/_card.php'; ?>
  </div>
</section>

<section class="promise">
  <div class="wrap promise-grid">
    <div>
      <h2>Tested where it rains sideways</h2>
      <p>Before anything goes on the shelf, one of us wears or carries it for a season on the Dales, the Moors and the Peak. If it leaks, rubs or breaks, we do not stock it.</p>
    </div>
    <dl class="facts">
      <div><dt>Delivery</dt><dd>Free over <?= money((float) config('shop.free_delivery_over')) ?>, otherwise <?= money((float) config('shop.delivery_cost')) ?></dd></div>
      <div><dt>Returns</dt><dd>60 days, unworn with tags</dd></div>
      <div><dt>Repairs</dt><dd>Zips, re-proofing and resoles at the shop</dd></div>
    </dl>
  </div>
</section>

<?php $avg = $reviews ? round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1) : null; ?>
<div class="wrap page">
  <nav class="crumbs" aria-label="Breadcrumb">
    <a href="/shop">Shop</a> <span aria-hidden="true">/</span>
    <a href="/shop?category=<?= e($p['category_slug']) ?>"><?= e($p['category']) ?></a>
  </nav>

  <div class="pdp">
    <div class="pdp-media">
      <?= product_image($p, 'pimg pimg-lg') ?>
      <?php if ($p['grid_ref']): ?>
      <p class="tested">
        <svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path d="M8 2 14 13H2Z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><circle cx="8" cy="9.3" r="1.4" fill="currentColor"/></svg>
        Tested at <?= e($p['tested_at']) ?>, <span class="gr"><?= e($p['grid_ref']) ?></span>
      </p>
      <?php endif; ?>
    </div>

    <div class="pdp-info">
      <h1><?= e($p['name']) ?></h1>
      <p class="pdp-summary"><?= e($p['summary']) ?></p>
      <p class="pdp-price"><?= money((float) $p['price']) ?></p>
      <?php if ($avg): ?><p class="pdp-rating"><?= stars((int) round($avg)) ?> <a href="#reviews"><?= $avg ?> from <?= count($reviews) ?> <?= count($reviews) === 1 ? 'review' : 'reviews' ?></a></p><?php endif; ?>

      <dl class="spec">
        <div><dt>Colour</dt><dd><span class="dot" style="background:<?= e($p['swatch']) ?>"></span><?= e($p['colour']) ?></dd></div>
        <?php if ($p['weight_g']): ?><div><dt>Weight</dt><dd><?= grams((int) $p['weight_g']) ?></dd></div><?php endif; ?>
        <div><dt>Stock</dt><dd><?= $p['stock'] > 5 ? 'In stock' : ($p['stock'] > 0 ? 'Only ' . (int) $p['stock'] . ' left' : 'Out of stock') ?></dd></div>
      </dl>

      <?php if ($p['stock'] > 0): ?>
      <form class="buy" method="post" action="/basket/add">
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
        <input type="hidden" name="back" value="/product?id=<?= (int) $p['id'] ?>">
        <label for="qty">Quantity</label>
        <div class="buy-row">
          <input id="qty" name="qty" type="number" min="1" max="<?= min(20, (int) $p['stock']) ?>" value="1" inputmode="numeric">
          <button class="btn btn-primary" type="submit">Add to basket</button>
        </div>
      </form>
      <?php endif; ?>

      <div class="prose pdp-desc">
        <?php foreach (preg_split('/\n\s*\n/', $p['description']) as $para): ?><p><?= e($para) ?></p><?php endforeach; ?>
      </div>
    </div>
  </div>

  <section id="reviews" class="reviews" aria-labelledby="rev-h">
    <h2 id="rev-h">Reviews</h2>
    <?php if (!$reviews): ?><p class="muted">No reviews yet. If you own one, tell other walkers how it held up.</p><?php endif; ?>
    <ol class="review-list">
      <?php foreach ($reviews as $r): ?>
      <li>
        <p class="review-head"><?= stars((int) $r['rating']) ?> <strong><?= e($r['author']) ?></strong> <time datetime="<?= e($r['created_at']) ?>"><?= date('j M Y', strtotime($r['created_at'])) ?></time></p>
        <p><?= e($r['body']) ?></p>
      </li>
      <?php endforeach; ?>
    </ol>

    <?php if (current_user()): ?>
    <form class="review-form" method="post" action="/product/review">
      <?= csrf_field() ?>
      <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
      <fieldset class="rating-pick">
        <legend>Your rating</legend>
        <?php for ($i = 5; $i >= 1; $i--): ?>
          <label><input type="radio" name="rating" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>> <?= $i ?></label>
        <?php endfor; ?>
      </fieldset>
      <label for="body">Your review</label>
      <textarea id="body" name="body" rows="4" placeholder="Where did you use it, and how did it hold up?"></textarea>
      <button class="btn" type="submit">Post review</button>
    </form>
    <?php else: ?>
    <p class="muted"><a href="/login?next=<?= urlencode('/product?id=' . $p['id'] . '#reviews') ?>">Sign in</a> to write a review.</p>
    <?php endif; ?>
  </section>

  <?php if ($related): ?>
  <section class="section" aria-labelledby="rel-h">
    <div class="section-head"><h2 id="rel-h">Also in <?= e($p['category']) ?></h2></div>
    <div class="grid">
      <?php foreach ($related as $p) require ROOT . '/templates/_card.php'; ?>
    </div>
  </section>
  <?php endif; ?>
</div>

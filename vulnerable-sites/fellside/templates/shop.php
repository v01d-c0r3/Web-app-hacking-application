<div class="wrap page">
  <header class="page-head">
    <?php if ($term !== ''): ?>
      <h1>Results for “<?= e($term) ?>”</h1>
      <p><?= count($products) ?> <?= count($products) === 1 ? 'item matches' : 'items match' ?> your search.</p>
    <?php elseif ($category): ?>
      <h1><?= e($category['name']) ?></h1>
      <p><?= e($category['blurb']) ?></p>
    <?php else: ?>
      <h1>All kit</h1>
      <p>Everything in the shop, from boots to bothy bags.</p>
    <?php endif; ?>
  </header>

  <div class="shop-layout">
    <nav class="filters" aria-label="Categories">
      <a href="/shop" <?= $cat === '' && $term === '' ? 'aria-current="page"' : '' ?>>All kit</a>
      <?php foreach ($categories as $c): ?>
        <a href="/shop?category=<?= e($c['slug']) ?>" <?= $cat === $c['slug'] ? 'aria-current="page"' : '' ?>><?= e($c['name']) ?></a>
      <?php endforeach; ?>
    </nav>

    <div>
      <form class="sort" method="get" action="/shop">
        <?php if ($cat !== ''): ?><input type="hidden" name="category" value="<?= e($cat) ?>"><?php endif; ?>
        <?php if ($term !== ''): ?><input type="hidden" name="q" value="<?= e($term) ?>"><?php endif; ?>
        <label for="sort">Sort by</label>
        <select id="sort" name="sort" data-autosubmit>
          <?php foreach (['featured' => 'Our picks first', 'price-low' => 'Price, low to high', 'price-high' => 'Price, high to low', 'weight' => 'Lightest first'] as $k => $v): ?>
            <option value="<?= $k ?>" <?= $sort === $k ? 'selected' : '' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
        <noscript><button class="btn btn-small">Sort</button></noscript>
      </form>

      <?php if (!$products): ?>
        <div class="empty">
          <p>Nothing matches “<?= e($term) ?>”. Try a broader word like <a href="/shop?q=jacket">jacket</a> or <a href="/shop?q=wool">wool</a>, or browse <a href="/shop">all kit</a>.</p>
        </div>
      <?php else: ?>
        <div class="grid grid-3">
          <?php foreach ($products as $p) require ROOT . '/templates/_card.php'; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

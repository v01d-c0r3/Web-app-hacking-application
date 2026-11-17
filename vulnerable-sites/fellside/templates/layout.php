<?php $user = current_user(); $f = flash(); $here = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>
<!doctype html>
<html lang="en-GB">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $pageTitle ? e($pageTitle) . ' | ' : '' ?><?= e(config('shop.name')) ?></title>
<meta name="description" content="<?= e(config('shop.tagline')) ?>">
<link rel="icon" href="/assets/img/trig.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/css/fonts.css">
<link rel="stylesheet" href="/assets/css/site.css">
</head>
<body>
<a class="skip" href="#main">Skip to content</a>

<p class="strip">Free UK delivery over <?= money((float) config('shop.free_delivery_over')) ?>. Order by 2pm and it leaves Otley the same day.</p>

<header class="site-head">
  <div class="wrap head-row">
    <a class="brand" href="/" aria-label="<?= e(config('shop.name')) ?> home">
      <?= file_get_contents(ROOT . '/public/assets/img/trig.svg') ?>
      <span><?= e(config('shop.name')) ?></span>
    </a>

    <nav class="main-nav" aria-label="Main">
      <a href="/shop" <?= $here === '/shop' ? 'aria-current="page"' : '' ?>>Shop all kit</a>
      <a href="/shop?category=jackets-layers">Jackets</a>
      <a href="/shop?category=footwear">Footwear</a>
      <a href="/about" <?= $here === '/about' ? 'aria-current="page"' : '' ?>>About</a>
    </nav>

    <form class="search" action="/shop" method="get" role="search">
      <label class="sr" for="q">Search kit</label>
      <input id="q" name="q" type="search" placeholder="Search kit" value="<?= e($_GET['q'] ?? '') ?>">
      <button type="submit" aria-label="Search"><svg viewBox="0 0 20 20" width="18" height="18" aria-hidden="true"><circle cx="8.5" cy="8.5" r="5.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M13 13l4.5 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></button>
    </form>

    <div class="head-actions">
      <?php if ($user): ?>
        <?php if ($user['role'] === 'admin'): ?><a href="/admin">Admin</a><?php endif; ?>
        <a href="/account">Account</a>
      <?php else: ?>
        <a href="/login">Sign in</a>
      <?php endif; ?>
      <a class="basket-link" href="/basket">Basket <span class="count" aria-label="<?= basket_count() ?> items"><?= basket_count() ?></span></a>
    </div>
  </div>
</header>

<?php if ($f): ?>
<div class="wrap"><p class="flash flash-<?= e($f[1]) ?>" role="status"><?= e($f[0]) ?></p></div>
<?php endif; ?>

<main id="main">
<?= $content ?>
</main>

<footer class="site-foot">
  <div class="wrap foot-grid">
    <div class="foot-shop">
      <p class="foot-brand"><?= e(config('shop.name')) ?></p>
      <p>14 Kirkgate, Otley, West Yorkshire LS21 3HJ<br>Shop open Tuesday to Saturday, 9.30 to 5.</p>
      <p><a href="mailto:<?= e(config('shop.support_email')) ?>"><?= e(config('shop.support_email')) ?></a></p>
    </div>
    <nav aria-label="Shop">
      <p class="foot-h">Shop</p>
      <a href="/shop?category=jackets-layers">Jackets &amp; layers</a>
      <a href="/shop?category=footwear">Footwear</a>
      <a href="/shop?category=packs">Packs</a>
      <a href="/shop?category=accessories">Accessories</a>
      <a href="/shop?category=camp-navigation">Camp &amp; navigation</a>
    </nav>
    <nav aria-label="Help">
      <p class="foot-h">Help</p>
      <a href="/contact">Contact us</a>
      <a href="/about#delivery">Delivery and returns</a>
      <a href="/account">Your orders</a>
    </nav>
    <p class="foot-note">Prices include VAT. Returns accepted for 60 days, unworn and with tags.</p>
  </div>
</footer>
<script src="/assets/js/site.js" defer></script>
</body>
</html>

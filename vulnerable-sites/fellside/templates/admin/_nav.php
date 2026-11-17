<?php $here = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>
<nav class="admin-nav" aria-label="Admin">
  <?php foreach (['/admin' => 'Overview', '/admin/orders' => 'Orders', '/admin/products' => 'Products', '/admin/messages' => 'Messages'] as $href => $label): ?>
    <a href="<?= $href ?>" <?= $here === $href ? 'aria-current="page"' : '' ?>><?= $label ?></a>
  <?php endforeach; ?>
</nav>

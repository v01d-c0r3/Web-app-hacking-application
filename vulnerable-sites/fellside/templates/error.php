<div class="wrap page error-page">
  <p class="error-code" aria-hidden="true"><?= (int) $code ?></p>
  <h1><?= $code === 403 ? 'Staff only' : 'Off the map' ?></h1>
  <p><?= e($msg) ?></p>
  <p><a class="btn" href="/shop">Go to the shop</a></p>
</div>

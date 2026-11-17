<div class="wrap page admin">
  <h1>Messages</h1>
  <?php require __DIR__ . '/_nav.php'; ?>
  <?php if (!$messages): ?><p class="muted">No messages yet. They appear here when someone uses the contact form.</p><?php endif; ?>
  <ol class="review-list">
    <?php foreach ($messages as $m): ?>
    <li>
      <p class="review-head"><strong><?= e($m['name']) ?></strong> <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a> <?= $m['order_ref'] ? '<span class="tag">' . e($m['order_ref']) . '</span>' : '' ?> <time><?= date('j M Y, H:i', strtotime($m['created_at'])) ?></time></p>
      <p><?= nl2br(e($m['body'])) ?></p>
    </li>
    <?php endforeach; ?>
  </ol>
</div>

<div class="wrap page auth">
  <h1>Sign in</h1>
  <?php if ($error): ?><p class="flash flash-error" role="alert"><?= e($error) ?></p><?php endif; ?>
  <form method="post" action="/login">
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <div class="field"><label for="username">Username or email</label><input id="username" name="username" autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>" required></div>
    <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
    <button class="btn btn-primary btn-block" type="submit">Sign in</button>
  </form>
  <p class="auth-alt">New to Fellside? <a href="/register">Create an account</a></p>
</div>

<?php $err = fn($k) => isset($errors[$k]) ? '<p class="field-error" id="' . $k . '-err">' . e($errors[$k]) . '</p>' : ''; ?>
<div class="wrap page auth">
  <h1>Create an account</h1>
  <p>Track orders, save your address and write reviews.</p>
  <form method="post" action="/register" novalidate>
    <?= csrf_field() ?>
    <div class="field<?= isset($errors['full_name']) ? ' has-error' : '' ?>"><label for="full_name">Full name</label><?= $err('full_name') ?><input id="full_name" name="full_name" autocomplete="name" value="<?= e($form['full_name']) ?>"></div>
    <div class="field<?= isset($errors['email']) ? ' has-error' : '' ?>"><label for="email">Email</label><?= $err('email') ?><input id="email" name="email" type="email" autocomplete="email" value="<?= e($form['email']) ?>"></div>
    <div class="field<?= isset($errors['username']) ? ' has-error' : '' ?>"><label for="username">Username</label><?= $err('username') ?><input id="username" name="username" autocomplete="username" value="<?= e($form['username']) ?>"></div>
    <div class="field<?= isset($errors['password']) ? ' has-error' : '' ?>"><label for="password">Password</label><p class="field-hint">At least 8 characters.</p><?= $err('password') ?><input id="password" name="password" type="password" autocomplete="new-password"></div>
    <button class="btn btn-primary btn-block" type="submit">Create account</button>
  </form>
  <p class="auth-alt">Already have an account? <a href="/login">Sign in</a></p>
</div>

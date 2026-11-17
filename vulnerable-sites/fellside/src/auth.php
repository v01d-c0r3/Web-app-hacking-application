<?php

function current_user(): ?array
{
    static $u = false;
    if ($u !== false) return $u;
    $u = isset($_SESSION['uid']) ? q_one('SELECT * FROM users WHERE id = ?', [$_SESSION['uid']]) : null;
    return $u;
}

function login_user(string $username, string $password): ?array
{
    $u = q_one('SELECT * FROM users WHERE username = ? OR email = ?', [$username, $username]);
    if (!$u || !password_verify($password, $u['password_hash'])) return null;
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $u['id'];
    return $u;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        flash('Sign in to see your account.', 'info');
        redirect('/login?next=' . urlencode($_SERVER['REQUEST_URI']));
    }
    return $u;
}

function require_admin(): array
{
    $u = require_login();
    if ($u['role'] !== 'admin') {
        http_response_code(403);
        render('error', ['code' => 403, 'msg' => 'This page is for shop staff only.'], 'Staff only');
        exit;
    }
    return $u;
}

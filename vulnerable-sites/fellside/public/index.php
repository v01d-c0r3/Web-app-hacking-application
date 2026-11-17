<?php
// Front controller. Apache rewrites every non-file request here (see .htaccess).
// Local dev without Docker:  php -S 0.0.0.0:8080 -t public public/index.php

if (PHP_SAPI === 'cli-server') {
    $f = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($f)) return false;
}

require dirname(__DIR__) . '/src/bootstrap.php';
require ROOT . '/src/routes.php';

$path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';
$handler = $routes[$path] ?? null;

if (!$handler) {
    http_response_code(404);
    render('error', ['code' => 404, 'msg' => 'There is no page at this address. It may have moved, or the link may be mistyped.'], 'Page not found');
    exit;
}
$handler();

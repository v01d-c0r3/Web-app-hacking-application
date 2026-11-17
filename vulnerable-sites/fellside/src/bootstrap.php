<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));

$configFile = getenv('FELLSIDE_CONFIG') ?: ROOT . '/config/app.ini';
$GLOBALS['config'] = parse_ini_file($configFile, true, INI_SCANNER_TYPED);
if ($GLOBALS['config'] === false) {
    http_response_code(500);
    exit('Config file could not be read: ' . $configFile);
}

session_name('fellside_session');
session_start();

require ROOT . '/src/helpers.php';
require ROOT . '/src/db.php';
require ROOT . '/src/auth.php';
require ROOT . '/src/basket.php';

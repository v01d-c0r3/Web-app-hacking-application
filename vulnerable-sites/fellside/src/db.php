<?php
// Single place every query goes through, so vulnerable variants can be swapped in later.

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;

    $path = getenv('FELLSIDE_DB') ?: config('database.path');
    $fresh = !file_exists($path);
    if ($fresh && !is_dir(dirname($path))) mkdir(dirname($path), 0775, true);

    $pdo = new PDO('sqlite:' . $path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    if ($fresh) {
        $pdo->exec(file_get_contents(ROOT . '/db/schema.sql'));
        require ROOT . '/db/seed.php';
        seed_database($pdo, $GLOBALS['config']);
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function q_all(string $sql, array $params = []): array { return q($sql, $params)->fetchAll(); }
function q_one(string $sql, array $params = []): ?array { return q($sql, $params)->fetch() ?: null; }

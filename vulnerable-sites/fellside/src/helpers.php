<?php

function config(string $key, $default = null)
{
    $v = $GLOBALS['config'];
    foreach (explode('.', $key) as $k) {
        if (!is_array($v) || !array_key_exists($k, $v)) return $default;
        $v = $v[$k];
    }
    return $v;
}

function e($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

function money(float $n): string { return config('shop.currency_symbol', '£') . number_format($n, 2); }

function grams(?int $g): string
{
    if (!$g) return '';
    return $g >= 1000 ? rtrim(rtrim(number_format($g / 1000, 2), '0'), '.') . ' kg' : $g . ' g';
}

function redirect(string $to): never { header('Location: ' . $to); exit; }

function flash(?string $msg = null, string $type = 'ok')
{
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return null; }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }

function csrf_check(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Form expired. Go back, refresh the page and try again.');
    }
}

function is_post(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }

function render(string $template, array $vars = [], ?string $title = null): void
{
    extract($vars);
    ob_start();
    require ROOT . '/templates/' . $template . '.php';
    $content = ob_get_clean();
    $pageTitle = $title;
    require ROOT . '/templates/layout.php';
}

function icon(string $name): string
{
    $f = ROOT . '/public/assets/img/products/' . basename($name) . '.svg';
    return is_file($f) ? file_get_contents($f) : '';
}

function product_image(array $p, string $class = 'pimg'): string
{
    $style = '--swatch:' . e($p['swatch']);
    if (!empty($p['image'])) {
        return '<div class="' . $class . ' has-photo" style="' . $style . '"><img src="' . e($p['image']) . '" alt="' . e($p['name']) . '"></div>';
    }
    return '<div class="' . $class . '" style="' . $style . '" role="img" aria-label="' . e($p['name']) . '">' . icon($p['icon']) . '</div>';
}

function stars(int $n): string
{
    return '<span class="stars" aria-label="' . $n . ' out of 5">' . str_repeat('●', $n) . '<span class="off">' . str_repeat('●', 5 - $n) . '</span></span>';
}

function vuln(string $name): bool
{
    return ($GLOBALS['config']['vulnerabilities'][$name] ?? false) === true;
}

function vuln_setting(string $name, string $key, array $allowed): string
{
    $value = $GLOBALS['config']["vuln.$name"][$key] ?? null;
    if (!in_array($value, $allowed, true)) {
        http_response_code(500);
        exit("app.ini: [vuln.$name] $key must be one of: " . implode(', ', $allowed));
    }
    return $value;
}

function xss_filter(string $text, string $level): string
{
    return match ($level) {
        'none'         => $text,
        // One pass, lowercase only, and only the exact <script> tags.
        'strip_script' => str_replace(['<script>', '</script>'], '', $text),
    };
}
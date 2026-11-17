<?php
// Basket lives in the session as [product_id => quantity].

function basket(): array { return $_SESSION['basket'] ?? []; }

function basket_count(): int { return array_sum(basket()); }

function basket_set(int $pid, int $qty): void
{
    if ($qty <= 0) unset($_SESSION['basket'][$pid]);
    else $_SESSION['basket'][$pid] = min($qty, 20);
}

function basket_add(int $pid, int $qty = 1): void { basket_set($pid, (basket()[$pid] ?? 0) + $qty); }

function basket_clear(): void { unset($_SESSION['basket'], $_SESSION['coupon']); }

function basket_lines(): array
{
    $b = basket();
    if (!$b) return [];
    $ids = implode(',', array_map('intval', array_keys($b)));
    $lines = [];
    foreach (q_all("SELECT * FROM products WHERE id IN ($ids)") as $p) {
        $p['qty'] = $b[$p['id']];
        $p['line_total'] = $p['qty'] * $p['price'];
        $lines[] = $p;
    }
    return $lines;
}

function basket_totals(array $lines): array
{
    $sub = array_sum(array_column($lines, 'line_total'));
    $coupon = $_SESSION['coupon'] ?? null;
    $discount = $coupon ? round($sub * $coupon['percent'] / 100, 2) : 0;
    $threshold = (float) config('shop.free_delivery_over');
    $delivery = ($sub == 0 || $sub >= $threshold) ? 0 : (float) config('shop.delivery_cost');
    return [
        'subtotal' => $sub, 'discount' => $discount, 'delivery' => $delivery,
        'total' => $sub - $discount + $delivery, 'coupon' => $coupon,
        'to_free' => max(0, $threshold - $sub),
    ];
}

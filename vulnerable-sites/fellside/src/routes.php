<?php
// Route table: path => handler. Each handler is small on purpose so that
// individual endpoints can later be replaced with vulnerable variants.

$routes = [];

$routes['/'] = function () {
    render('home', [
        'featured'   => q_all('SELECT * FROM products WHERE featured = 1 ORDER BY id LIMIT 8'),
        'categories' => q_all('SELECT c.*, COUNT(p.id) AS n FROM categories c LEFT JOIN products p ON p.category_id = c.id GROUP BY c.id ORDER BY c.id'),
    ]);
};

$routes['/shop'] = function () {
    $cat  = $_GET['category'] ?? '';
    $term = trim($_GET['q'] ?? '');
    $sort = $_GET['sort'] ?? 'featured';
    $order = ['featured' => 'p.featured DESC, p.id', 'price-low' => 'p.price ASC', 'price-high' => 'p.price DESC', 'weight' => 'p.weight_g ASC'][$sort] ?? 'p.featured DESC, p.id';

    $where = []; $params = [];
    if ($cat !== '') { $where[] = 'c.slug = ?'; $params[] = $cat; }
    if ($term !== '') { $where[] = '(p.name LIKE ? OR p.summary LIKE ?)'; $params[] = "%$term%"; $params[] = "%$term%"; }
    $sql = 'SELECT p.*, c.name AS category FROM products p JOIN categories c ON c.id = p.category_id'
         . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY $order";

    $category = $cat !== '' ? q_one('SELECT * FROM categories WHERE slug = ?', [$cat]) : null;
    render('shop', [
        'products' => q_all($sql, $params),
        'categories' => q_all('SELECT * FROM categories ORDER BY id'),
        'category' => $category, 'term' => $term, 'sort' => $sort, 'cat' => $cat,
    ], $term !== '' ? "Search: $term" : ($category['name'] ?? 'All kit'));
};

$routes['/product'] = function () {
    $id = (int) ($_GET['id'] ?? 0);
    $p = q_one('SELECT p.*, c.name AS category, c.slug AS category_slug FROM products p JOIN categories c ON c.id = p.category_id WHERE p.id = ?', [$id]);
    if (!$p) {
        http_response_code(404);
        render('error', ['code' => 404, 'msg' => 'We could not find that product. It may have sold out for the season.'], 'Product not found');
        return;
    }
    $reviews = q_all('SELECT * FROM reviews WHERE product_id = ? ORDER BY created_at DESC, id DESC', [$id]);
    $related = q_all('SELECT * FROM products WHERE category_id = ? AND id != ? ORDER BY featured DESC LIMIT 4', [$p['category_id'], $id]);
    render('product', compact('p', 'reviews', 'related'), $p['name']);
};

$routes['/product/review'] = function () {
    if (!is_post()) redirect('/shop');
    csrf_check();
    $u = require_login();
    $pid = (int) ($_POST['product_id'] ?? 0);
    $rating = max(1, min(5, (int) ($_POST['rating'] ?? 5)));
    $body = trim($_POST['body'] ?? '');
    if ($body === '') {
        flash('Write a few words about the product before posting.', 'error');
    } else {
        $name = explode(' ', $u['full_name'])[0] . ' ' . substr(explode(' ', $u['full_name'])[1] ?? '', 0, 1);
        q('INSERT INTO reviews (product_id, user_id, author, rating, body) VALUES (?,?,?,?,?)', [$pid, $u['id'], trim($name), $rating, $body]);
        flash('Review posted. Thanks for taking the time.');
    }
    redirect('/product?id=' . $pid . '#reviews');
};

$routes['/basket'] = function () {
    $lines = basket_lines();
    render('basket', ['lines' => $lines, 't' => basket_totals($lines)], 'Your basket');
};

$routes['/basket/add'] = function () {
    if (!is_post()) redirect('/basket');
    csrf_check();
    $pid = (int) ($_POST['product_id'] ?? 0);
    $p = q_one('SELECT id, name FROM products WHERE id = ?', [$pid]);
    if ($p) {
        basket_add($pid, max(1, (int) ($_POST['qty'] ?? 1)));
        flash($p['name'] . ' added to your basket.');
    }
    redirect($_POST['back'] ?? '/basket');
};

$routes['/basket/update'] = function () {
    if (!is_post()) redirect('/basket');
    csrf_check();
    foreach ((array) ($_POST['qty'] ?? []) as $pid => $qty) basket_set((int) $pid, (int) $qty);
    if (isset($_POST['remove'])) basket_set((int) $_POST['remove'], 0);
    redirect('/basket');
};

$routes['/basket/coupon'] = function () {
    if (!is_post()) redirect('/basket');
    csrf_check();
    $code = strtoupper(trim($_POST['code'] ?? ''));
    if ($code === '') { unset($_SESSION['coupon']); redirect('/basket'); }
    $c = q_one('SELECT * FROM coupons WHERE code = ? AND active = 1', [$code]);
    if ($c) { $_SESSION['coupon'] = $c; flash("Code $code applied: {$c['percent']}% off."); }
    else flash("The code $code is not valid. Check the spelling, or it may have expired.", 'error');
    redirect('/basket');
};

$routes['/checkout'] = function () {
    $lines = basket_lines();
    if (!$lines) redirect('/basket');
    $t = basket_totals($lines);
    $u = current_user();
    $errors = [];
    $form = [
        'full_name' => $u['full_name'] ?? '', 'email' => $u['email'] ?? '',
        'address' => $u['address'] ?? '', 'postcode' => $u['postcode'] ?? '',
    ];

    if (is_post()) {
        csrf_check();
        foreach (['full_name', 'email', 'address', 'postcode'] as $k) $form[$k] = trim($_POST[$k] ?? '');
        $card = preg_replace('/\D/', '', $_POST['card'] ?? '');
        if ($form['full_name'] === '') $errors['full_name'] = 'Enter the name for delivery.';
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter an email address like name@example.com.';
        if ($form['address'] === '') $errors['address'] = 'Enter a delivery address.';
        if (!preg_match('/^[A-Z]{1,2}\d[A-Z\d]? ?\d[A-Z]{2}$/i', $form['postcode'])) $errors['postcode'] = 'Enter a UK postcode, like LS21 3HJ.';
        if (strlen($card) < 12 || strlen($card) > 19) $errors['card'] = 'Enter the long number on the front of your card.';

        if (!$errors) {
            $db = db();
            $db->beginTransaction();
            q('INSERT INTO orders (user_id, full_name, email, address, postcode, card_last4, subtotal, delivery, discount, total, coupon_code) VALUES (?,?,?,?,?,?,?,?,?,?,?)', [
                $u['id'] ?? null, $form['full_name'], $form['email'], $form['address'], strtoupper($form['postcode']),
                substr($card, -4), $t['subtotal'], $t['delivery'], $t['discount'], $t['total'], $t['coupon']['code'] ?? null,
            ]);
            $oid = (int) $db->lastInsertId();
            foreach ($lines as $l) {
                q('INSERT INTO order_items (order_id, product_id, name, price, quantity) VALUES (?,?,?,?,?)', [$oid, $l['id'], $l['name'], $l['price'], $l['qty']]);
                q('UPDATE products SET stock = MAX(stock - ?, 0) WHERE id = ?', [$l['qty'], $l['id']]);
            }
            $db->commit();
            basket_clear();
            $_SESSION['guest_orders'][] = $oid;
            redirect('/order?id=' . $oid . '&placed=1');
        }
    }
    render('checkout', compact('lines', 't', 'form', 'errors', 'u'), 'Checkout');
};

$routes['/order'] = function () {
    $id = (int) ($_GET['id'] ?? 0);
    $o = q_one('SELECT * FROM orders WHERE id = ?', [$id]);
    $u = current_user();
    $mine = $o && (($u && ($o['user_id'] == $u['id'] || $u['role'] === 'admin')) || in_array($id, $_SESSION['guest_orders'] ?? [], true));
    if (!$mine) {
        http_response_code(404);
        render('error', ['code' => 404, 'msg' => 'We could not find that order. Sign in with the account you ordered from to see it.'], 'Order not found');
        return;
    }
    $items = q_all('SELECT oi.*, p.swatch, p.icon, p.image FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE order_id = ?', [$id]);
    render('order', ['o' => $o, 'items' => $items, 'placed' => isset($_GET['placed'])], 'Order ' . $id);
};

$routes['/login'] = function () {
    $error = null;
    $next = $_GET['next'] ?? $_POST['next'] ?? '/account';
    if (!str_starts_with($next, '/') || str_starts_with($next, '//')) $next = '/account';
    if (is_post()) {
        csrf_check();
        if (login_user(trim($_POST['username'] ?? ''), $_POST['password'] ?? '')) redirect($next);
        $error = 'That username and password do not match. Check them and try again.';
    }
    render('login', ['error' => $error, 'next' => $next], 'Sign in');
};

$routes['/register'] = function () {
    $errors = []; $form = ['username' => '', 'email' => '', 'full_name' => ''];
    if (is_post()) {
        csrf_check();
        foreach ($form as $k => $_) $form[$k] = trim($_POST[$k] ?? '');
        $pw = $_POST['password'] ?? '';
        if (!preg_match('/^[a-z0-9._-]{3,30}$/i', $form['username'])) $errors['username'] = 'Use 3 to 30 letters, numbers, dots or dashes.';
        elseif (q_one('SELECT 1 FROM users WHERE username = ?', [$form['username']])) $errors['username'] = 'That username is taken. Try another.';
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter an email address like name@example.com.';
        elseif (q_one('SELECT 1 FROM users WHERE email = ?', [$form['email']])) $errors['email'] = 'An account already uses this email. Sign in instead.';
        if ($form['full_name'] === '') $errors['full_name'] = 'Enter your name.';
        if (strlen($pw) < 8) $errors['password'] = 'Use at least 8 characters.';
        if (!$errors) {
            q('INSERT INTO users (username, email, full_name, password_hash) VALUES (?,?,?,?)', [$form['username'], $form['email'], $form['full_name'], password_hash($pw, PASSWORD_DEFAULT)]);
            login_user($form['username'], $pw);
            flash('Account created. Welcome to Fellside.');
            redirect('/account');
        }
    }
    render('register', compact('errors', 'form'), 'Create an account');
};

$routes['/logout'] = function () {
    if (is_post()) { csrf_check(); session_destroy(); }
    redirect('/');
};

$routes['/account'] = function () {
    $u = require_login();
    if (is_post()) {
        csrf_check();
        q('UPDATE users SET full_name = ?, address = ?, postcode = ? WHERE id = ?', [trim($_POST['full_name'] ?? $u['full_name']), trim($_POST['address'] ?? ''), strtoupper(trim($_POST['postcode'] ?? '')), $u['id']]);
        flash('Details saved.');
        redirect('/account');
    }
    $orders = q_all('SELECT o.*, (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) AS items FROM orders o WHERE user_id = ? ORDER BY created_at DESC', [$u['id']]);
    render('account', compact('u', 'orders'), 'Your account');
};

$routes['/about'] = fn() => render('about', [], 'About the shop');

$routes['/contact'] = function () {
    $sent = false; $errors = []; $form = ['name' => '', 'email' => '', 'order_ref' => '', 'body' => ''];
    if ($u = current_user()) { $form['name'] = $u['full_name']; $form['email'] = $u['email']; }
    if (is_post()) {
        csrf_check();
        foreach ($form as $k => $_) $form[$k] = trim($_POST[$k] ?? '');
        if ($form['name'] === '') $errors['name'] = 'Enter your name.';
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter an email address so we can reply.';
        if ($form['body'] === '') $errors['body'] = 'Tell us what you need help with.';
        if (!$errors) {
            q('INSERT INTO messages (name, email, order_ref, body) VALUES (?,?,?,?)', array_values($form));
            $sent = true;
        }
    }
    render('contact', compact('sent', 'errors', 'form'), 'Contact us');
};

/* ---------- Staff area ---------- */

$routes['/admin'] = function () {
    require_admin();
    render('admin/dashboard', [
        'stats' => q_one("SELECT (SELECT COUNT(*) FROM orders) AS orders, (SELECT COALESCE(SUM(total),0) FROM orders) AS revenue, (SELECT COUNT(*) FROM users WHERE role='customer') AS customers, (SELECT COUNT(*) FROM messages) AS messages"),
        'recent' => q_all('SELECT * FROM orders ORDER BY created_at DESC LIMIT 6'),
        'low'    => q_all('SELECT * FROM products WHERE stock <= 8 ORDER BY stock'),
    ], 'Shop admin');
};

$routes['/admin/products'] = function () {
    require_admin();
    render('admin/products', ['products' => q_all('SELECT p.*, c.name AS category FROM products p JOIN categories c ON c.id = p.category_id ORDER BY c.id, p.name')], 'Products');
};

$routes['/admin/product'] = function () {
    require_admin();
    $id = (int) ($_GET['id'] ?? 0);
    $p = $id ? q_one('SELECT * FROM products WHERE id = ?', [$id]) : null;
    if ($id && !$p) redirect('/admin/products');
    if (is_post()) {
        csrf_check();
        $f = fn($k) => trim($_POST[$k] ?? '');
        $data = [$f('name'), $f('summary'), $f('description'), (float) $f('price'), (int) $f('weight_g'), (int) $f('stock'), isset($_POST['featured']) ? 1 : 0, (int) $f('category_id')];
        if ($p) {
            q('UPDATE products SET name=?, summary=?, description=?, price=?, weight_g=?, stock=?, featured=?, category_id=? WHERE id=?', [...$data, $id]);
        } else {
            $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($f('name'))), '-') . '-' . bin2hex(random_bytes(2));
            q('INSERT INTO products (name, summary, description, price, weight_g, stock, featured, category_id, slug, colour, swatch, icon) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)', [...$data, $slug, 'Moss', '#5d6b4a', 'pack']);
            $id = (int) db()->lastInsertId();
        }
        if (!empty($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
            $info = getimagesize($_FILES['image']['tmp_name']);
            $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$info['mime'] ?? ''] ?? null;
            if ($ext) {
                $name = 'p' . $id . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], ROOT . '/public/uploads/' . $name);
                q('UPDATE products SET image = ? WHERE id = ?', ['/uploads/' . $name, $id]);
            } else {
                flash('Saved, but the photo was not a JPEG, PNG or WebP, so it was not uploaded.', 'error');
                redirect('/admin/product?id=' . $id);
            }
        }
        flash('Product saved.');
        redirect('/admin/product?id=' . $id);
    }
    render('admin/product', ['p' => $p, 'categories' => q_all('SELECT * FROM categories ORDER BY id')], $p ? 'Edit ' . $p['name'] : 'New product');
};

$routes['/admin/orders'] = function () {
    require_admin();
    if (is_post()) {
        csrf_check();
        q('UPDATE orders SET status = ? WHERE id = ?', [$_POST['status'] ?? 'Paid', (int) ($_POST['id'] ?? 0)]);
        flash('Order status updated.');
        redirect('/admin/orders');
    }
    render('admin/orders', ['orders' => q_all('SELECT o.*, (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) AS items FROM orders o ORDER BY created_at DESC')], 'Orders');
};

$routes['/admin/messages'] = function () {
    require_admin();
    render('admin/messages', ['messages' => q_all('SELECT * FROM messages ORDER BY created_at DESC')], 'Messages');
};

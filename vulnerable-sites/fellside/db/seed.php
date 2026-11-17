<?php
// Seed data. Called by src/db.php when the database file does not exist yet.

function seed_database(PDO $db, array $cfg): void
{
    $categories = [
        ['jackets-layers', 'Jackets & layers', 'Waterproofs, wax cotton and fleece for weather that changes by the hour.'],
        ['footwear',       'Footwear',         'Boots and gaiters for bog, limestone pavement and everything between.'],
        ['packs',          'Packs',            'Daypacks and bags sized for a full day on the tops.'],
        ['accessories',    'Accessories',      'Wool socks, hats and gloves. The small things that decide whether it was a good day.'],
        ['camp-navigation','Camp & navigation','Maps, torches, flasks and the kit you hope you will not need.'],
    ];
    $ins = $db->prepare('INSERT INTO categories (slug, name, blurb) VALUES (?, ?, ?)');
    foreach ($categories as $c) $ins->execute($c);
    $cat = fn(string $slug) => (int) $db->query("SELECT id FROM categories WHERE slug = " . $db->quote($slug))->fetchColumn();

    // slug, category, name, summary, description, price, weight, colour, swatch, icon, tested_at, grid_ref, stock, featured
    $products = [
        ['swaledale-waxed-jacket', 'jackets-layers', 'Swaledale waxed jacket',
         'Heavyweight wax cotton with a cord collar and two hand-warmer pockets.',
         "Made from 8oz British wax cotton that softens with every wet walk. The cord collar turns up against sideways rain, the storm flap covers a two-way brass zip, and the game pocket at the back takes a map, a flask and a sandwich.\n\nRe-wax once a season. It will outlast you.",
         189.00, 1150, 'Moss', '#5d6b4a', 'jacket', 'Keld to Muker', 'NY 892 012', 8, 1],
        ['kinder-3l-shell', 'jackets-layers', 'Kinder 3-layer shell',
         'A light, fully taped waterproof that packs into its own hood.',
         "Three-layer fabric rated to 20,000mm hydrostatic head, with pit zips for the climb and a wired peak that keeps rain off your glasses. Packs down to the size of a large orange.\n\nCut long at the back so it covers your hips when you lean into the wind.",
         215.00, 390, 'Heather', '#6b4a6e', 'jacket', 'Kinder Scout plateau', 'SK 085 888', 12, 1],
        ['bowland-grid-fleece', 'jackets-layers', 'Bowland grid fleece',
         'A breathable mid layer with a grid back that dries fast.',
         "Grid fleece traps warm air without holding sweat. Thumb loops, a half zip and a chest pocket big enough for a phone in a dry bag.",
         68.00, 310, 'Slate', '#4d5a63', 'fleece', 'Trough of Bowland', 'SD 620 529', 20, 0],
        ['ribblehead-leather-boots', 'footwear', 'Ribblehead leather boots',
         'One-piece leather uppers on a stiff Vibram sole, resoleable.',
         "Full-grain leather from a single hide, so there are no seams to let water in across the toes. A stiff sole for limestone and scree, and a cobbler can resole them when the lugs wear down.\n\nAllow two or three short walks to break them in.",
         165.00, 1480, 'Peat', '#5a3f2c', 'boot', 'Whernside from Ribblehead', 'SD 765 793', 6, 1],
        ['peat-bog-gaiters', 'footwear', 'Peat bog gaiters',
         'Knee-length gaiters with a hook at the lace and a strap under the arch.',
         "Waterproof upper and a tough Cordura lower that shrugs off heather. The front zip is covered by a velcro flap, so you can get them on with cold hands.",
         38.00, 260, 'Lichen', '#7f8c72', 'gaiter', 'Bleaklow', 'SK 093 960', 15, 0],
        ['fellside-28-daypack', 'packs', 'Fellside 28 daypack',
         'A 28-litre pack with a ventilated back and a rain cover in the lid.',
         "Big enough for waterproofs, a warm layer, lunch and a flask. Hip-belt pockets take a compass and snacks, and the side stretch pockets hold a litre bottle each.\n\nThe rain cover lives in its own pocket in the base, so you can find it in a hurry.",
         95.00, 980, 'Gorse', '#c9a227', 'pack', 'Three Peaks loop', 'SD 812 727', 9, 1],
        ['malham-roll-top-bag', 'packs', 'Malham roll-top dry bag',
         'A 10-litre dry bag to keep spare layers dry inside your pack.',
         "Welded seams, a roll-top closure and a D-ring for clipping it in. Bright inside so you can see what is at the bottom.",
         18.00, 90, 'Tarn', '#3e6b7a', 'drybag', 'Malham Tarn', 'SD 894 666', 40, 0],
        ['merino-walking-socks', 'accessories', 'Merino walking socks, two pairs',
         'Mid-weight merino with a cushioned sole and a flat toe seam.',
         "Seventy per cent merino for warmth when wet and fewer smells on day three. Cushioned under the heel and ball of the foot, thinner over the top so boots still fit.",
         24.00, 140, 'Heather marl', '#8a7a8c', 'socks', 'Cleveland Way', 'NZ 573 103', 60, 1],
        ['wensleydale-wool-beanie', 'accessories', 'Wensleydale wool beanie',
         'A double-layer knitted hat in undyed wool from Dales sheep.',
         "Spun and knitted in Yorkshire from Wensleydale fleece. Double layer over the ears.",
         22.00, 80, 'Undyed', '#a59a85', 'beanie', 'Buttertubs Pass', 'SD 875 961', 25, 0],
        ['dry-stone-gloves', 'accessories', 'Dry stone wool gloves',
         'Boiled wool gloves with a leather palm for gripping cold rock.',
         "Boiled wool stays warm when damp. The leather palm gives you grip on gates, stiles and wet gritstone.",
         26.00, 95, 'Charcoal', '#3f4146', 'gloves', 'Almscliff Crag', 'SE 268 490', 18, 0],
        ['os-explorer-ol2', 'camp-navigation', 'OS Explorer OL2 map, weatherproof',
         'Yorkshire Dales south and western areas at 1:25,000, on laminated paper.',
         "Covers Ingleborough, Whernside, Pen-y-ghent, Malham and Settle. The laminated edition survives being folded the wrong way in a gale.",
         17.99, 160, 'Map orange', '#d2752e', 'map', 'Settle', 'SD 819 637', 30, 0],
        ['ridge-400-head-torch', 'camp-navigation', 'Ridge 400 head torch',
         'A 400 lumen rechargeable torch with a red light for map reading.',
         "Four brightness settings, a red mode that keeps your night vision, and a lock so it does not switch on in your pack. Charges over USB-C and lasts around 40 hours on the low setting.",
         39.00, 86, 'Black', '#2e3033', 'torch', 'Simon\'s Seat after dark', 'SE 078 598', 22, 1],
        ['steel-flask-750', 'camp-navigation', 'Stainless flask, 750ml',
         'Keeps tea hot for 12 hours. The cup holds a proper mugful.',
         "Double-walled steel with a pour-through stopper, so you do not have to unscrew the lid in the rain.",
         34.00, 520, 'Moss', '#5d6b4a', 'flask', 'Top Withens', 'SD 981 353', 14, 1],
        ['enamel-camp-mug', 'camp-navigation', 'Enamel camp mug',
         'A 350ml enamel mug with a rolled steel rim.',
         "It will chip. That is part of the deal.",
         12.00, 120, 'Speckled blue', '#3e5a7a', 'mug', 'Hardraw', 'SD 867 917', 50, 0],
        ['two-person-bothy-bag', 'camp-navigation', 'Two-person bothy bag',
         'An emergency shelter for lunch stops in sideways rain.',
         "Pull it over your heads, sit on the edges and it becomes a warm, windproof room. Has a window for map reading and weighs less than a sandwich box.",
         45.00, 340, 'Hi-vis orange', '#d2752e', 'shelter', 'Great Shunner Fell', 'SD 849 972', 11, 1],
        ['trekking-poles', 'camp-navigation', 'Folding trekking poles, pair',
         'Three-section aluminium poles with cork grips.',
         "Fold to 38cm and adjust from 110 to 130cm. Your knees will thank you on the way down from Ingleborough.",
         72.00, 470, 'Slate', '#4d5a63', 'poles', 'Ingleborough', 'SD 741 745', 10, 0],
    ];
    $ins = $db->prepare('INSERT INTO products (slug, category_id, name, summary, description, price, weight_g, colour, swatch, icon, tested_at, grid_ref, stock, featured) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    foreach ($products as $p) {
        $p[1] = $cat($p[1]);
        $ins->execute($p);
    }

    $users = [
        [$cfg['seed']['admin_username'], 'shop@fellside.test', 'Ruth Akroyd', $cfg['seed']['admin_password'], 'admin', '14 Kirkgate, Otley', 'LS21 3HJ'],
        ['tom.b',   'tom.barraclough@example.com', 'Tom Barraclough', $cfg['seed']['customer_password'], 'customer', '3 Mill Lane, Hebden Bridge', 'HX7 8EU'],
        ['priya_w', 'priya.walsh@example.com',     'Priya Walsh',     $cfg['seed']['customer_password'], 'customer', '27 Cardigan Road, Leeds',   'LS6 1LF'],
        ['jdobson', 'jen.dobson@example.com',      'Jen Dobson',      $cfg['seed']['customer_password'], 'customer', 'Low Farm, Askrigg',         'DL8 3HT'],
    ];
    $ins = $db->prepare('INSERT INTO users (username, email, full_name, password_hash, role, address, postcode) VALUES (?,?,?,?,?,?,?)');
    foreach ($users as $u) {
        $u[3] = password_hash($u[3], PASSWORD_DEFAULT);
        $ins->execute($u);
    }

    $reviews = [
        [1, 3, 'Priya W', 5, 'Wore it up Pen-y-ghent in properly horrible weather and stayed dry underneath. It is heavy, but that is the point.'],
        [1, 4, 'Jen D', 4, 'Lovely jacket. Sizing runs a little big, so I went down one.'],
        [2, 2, 'Tom B', 5, 'Packs tiny and the hood actually stays put in the wind. Pit zips are a must on the climb out of Edale.'],
        [4, 2, 'Tom B', 4, 'Took three walks to break in, then perfect. Grip on wet limestone is excellent.'],
        [6, 3, 'Priya W', 5, 'Hip belt pockets fit my phone. Rain cover saved my lunch on the Three Peaks.'],
        [8, 4, 'Jen D', 5, 'Best socks I own. Bought a second set.'],
        [12, 2, 'Tom B', 4, 'Bright enough for coming off Kinder in the dark. The red light is useful for not dazzling your mates.'],
        [15, 3, 'Priya W', 5, 'Ate lunch in this on Great Shunner Fell in a hailstorm. Felt like cheating.'],
    ];
    $ins = $db->prepare('INSERT INTO reviews (product_id, user_id, author, rating, body) VALUES (?,?,?,?,?)');
    foreach ($reviews as $r) $ins->execute($r);

    $db->exec("INSERT INTO coupons (code, percent, active) VALUES ('WELCOME10', 10, 1), ('DALES15', 15, 1), ('STAFF40', 40, 0)");

    // A few historic orders so accounts and the admin view are not empty
    $orders = [
        [2, 'Tom Barraclough', 'tom.barraclough@example.com', '3 Mill Lane, Hebden Bridge', 'HX7 8EU', '4417', [[2, 1], [8, 2]], '2026-09-14 10:22:00'],
        [3, 'Priya Walsh', 'priya.walsh@example.com', '27 Cardigan Road, Leeds', 'LS6 1LF', '0921', [[6, 1]], '2026-10-02 18:05:00'],
        [4, 'Jen Dobson', 'jen.dobson@example.com', 'Low Farm, Askrigg', 'DL8 3HT', '3380', [[1, 1], [9, 1], [10, 1]], '2026-10-29 08:47:00'],
    ];
    foreach ($orders as [$uid, $name, $email, $addr, $pc, $last4, $items, $when]) {
        $sub = 0;
        foreach ($items as [$pid, $q]) $sub += $q * (float) $db->query("SELECT price FROM products WHERE id = $pid")->fetchColumn();
        $del = $sub >= (float) $cfg['shop']['free_delivery_over'] ? 0 : (float) $cfg['shop']['delivery_cost'];
        $db->prepare('INSERT INTO orders (user_id, full_name, email, address, postcode, card_last4, subtotal, delivery, total, status, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
           ->execute([$uid, $name, $email, $addr, $pc, $last4, $sub, $del, $sub + $del, 'Dispatched', $when]);
        $oid = (int) $db->lastInsertId();
        foreach ($items as [$pid, $q]) {
            $p = $db->query("SELECT name, price FROM products WHERE id = $pid")->fetch();
            $db->prepare('INSERT INTO order_items (order_id, product_id, name, price, quantity) VALUES (?,?,?,?,?)')
               ->execute([$oid, $pid, $p['name'], $p['price'], $q]);
        }
    }
}

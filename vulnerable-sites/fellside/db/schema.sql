CREATE TABLE users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    username      TEXT NOT NULL UNIQUE,
    email         TEXT NOT NULL UNIQUE,
    full_name     TEXT NOT NULL,
    password_hash TEXT NOT NULL,
    role          TEXT NOT NULL DEFAULT 'customer',
    address       TEXT,
    postcode      TEXT,
    created_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id    INTEGER PRIMARY KEY AUTOINCREMENT,
    slug  TEXT NOT NULL UNIQUE,
    name  TEXT NOT NULL,
    blurb TEXT
);

CREATE TABLE products (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER NOT NULL REFERENCES categories(id),
    slug        TEXT NOT NULL UNIQUE,
    name        TEXT NOT NULL,
    summary     TEXT NOT NULL,
    description TEXT NOT NULL,
    price       REAL NOT NULL,
    weight_g    INTEGER,
    colour      TEXT NOT NULL,
    swatch      TEXT NOT NULL,
    icon        TEXT NOT NULL,
    image       TEXT,
    tested_at   TEXT,
    grid_ref    TEXT,
    stock       INTEGER NOT NULL DEFAULT 10,
    featured    INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE reviews (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL REFERENCES products(id),
    user_id    INTEGER REFERENCES users(id),
    author     TEXT NOT NULL,
    rating     INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
    body       TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER REFERENCES users(id),
    full_name   TEXT NOT NULL,
    email       TEXT NOT NULL,
    address     TEXT NOT NULL,
    postcode    TEXT NOT NULL,
    card_last4  TEXT NOT NULL,
    subtotal    REAL NOT NULL,
    delivery    REAL NOT NULL,
    discount    REAL NOT NULL DEFAULT 0,
    total       REAL NOT NULL,
    coupon_code TEXT,
    status      TEXT NOT NULL DEFAULT 'Paid',
    created_at  TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE order_items (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id   INTEGER NOT NULL REFERENCES orders(id),
    product_id INTEGER NOT NULL REFERENCES products(id),
    name       TEXT NOT NULL,
    price      REAL NOT NULL,
    quantity   INTEGER NOT NULL
);

CREATE TABLE coupons (
    code     TEXT PRIMARY KEY,
    percent  INTEGER NOT NULL,
    active   INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE messages (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    name       TEXT NOT NULL,
    email      TEXT NOT NULL,
    order_ref  TEXT,
    body       TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

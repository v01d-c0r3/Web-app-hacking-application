# Fellside: e-commerce base for a vulnerable web app

A small, realistic outdoor-kit shop (PHP 8.3, SQLite, Apache) built as the clean
base for a config-driven vulnerable web app. There are **no intentional
vulnerabilities yet**: queries are parameterised, output is escaped and forms carry CSRF tokens.

## Run it

```bash
docker compose up --build
# open http://localhost:8080
```

Without Compose:

```bash
docker build -t fellside .
docker run --rm -p 8080:80 fellside
```

To wipe and reseed the database on start, set `FELLSIDE_RESET=1`.

Local dev without Docker (needs PHP 8.3 with pdo_sqlite):

```bash
FELLSIDE_DB=./data/fellside.sqlite php -S 0.0.0.0:8080 -t public public/index.php
```

## Seeded accounts (set in `config/app.ini`)

| Username | Password | Role |
|---|---|---|
| admin | Pen-y-ghent-694 | admin |
| tom.b, priya_w, jdobson | moorland | customer |

Coupon codes: `WELCOME10`, `DALES15` (active), `STAFF40` (inactive).

## Layout

```
config/app.ini        Runtime config read on startup (SecGen can overwrite this)
db/schema.sql         Tables: users, categories, products, reviews, orders, order_items, coupons, messages
db/seed.php           Seed data; runs automatically when the DB file does not exist
src/routes.php        Every endpoint, one small handler each
src/db.php            All queries go through q() / q_all() / q_one()
src/auth.php          Sessions, login, role checks
src/basket.php        Session basket, coupon and delivery maths
templates/            Page templates (layout.php wraps everything)
public/               Web root: index.php front controller, assets, uploads/
docker/               Entrypoint and the build-time font download
```

## Natural places to add vulnerabilities later

| Feature | Route | Typical vuln class |
|---|---|---|
| Search | `/shop?q=` | SQL injection, reflected XSS |
| Product page | `/product?id=` | SQLi on a numeric ID |
| Reviews | `/product/review` | Stored XSS |
| Order view | `/order?id=` | IDOR |
| Login | `/login` | SQLi auth bypass, brute force, open redirect via `next` |
| Coupons | `/basket/coupon` | Business logic (stacking, inactive codes, negative quantities) |
| Admin photo upload | `/admin/product` | Unrestricted file upload |
| Admin role check | `require_admin()` | Broken access control |
| Contact form → admin messages | `/contact` | Stored XSS against staff |

A `[vulnerabilities]` section in `config/app.ini` is reserved for toggles.

## Fonts

The Dockerfile downloads Archivo (SIL OFL) from Google Fonts at build time and
serves it locally, so the site works on offline lab networks. If the build
machine has no internet, it falls back to system fonts.

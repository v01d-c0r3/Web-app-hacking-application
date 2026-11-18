# Web App Hacking Application - shared notes for Claude

University group project, three students. Every teammate's Claude reads this
file, so it is how we stay in sync: read it before starting work, and update
it in the same change whenever the scope, architecture, conventions or one of
the open decisions below moves.

## Goal

A cybersecurity teaching tool. Students log in to a website, pick a lab, and
work through a realistic scenario that exploits one of the most common web
security issues - then learn how to defend against it. Every lab teaches both
halves: **exploit** the flaw, then **understand and fix** it.

Vulnerability classes in scope (checklist in README.md): SQL injection, IDOR,
cross-site scripting, broken access control, OS command injection, cross-site
request forgery, server-side request forgery, server-side template injection.

## Architecture

One VPS (live at https://pwnshop.uk) running Docker containers behind the
host's Caddy, with Cloudflare in front:

- **The portal** (`portal/`, Django). Accounts, login, the lab catalogue,
  and later the teaching content and progress tracking. It holds the only real
  user data on the box, so it is written securely - the opposite of the labs.
- **The labs** (`vulnerable-sites/<lab>/`). One image per deliberately
  vulnerable app, in whatever stack suits it (Fellside is PHP). Each lab folder
  is self-contained - its own `Dockerfile` and `docker-compose.yml` - and is
  pulled into the root `docker-compose.yml` with `include:`, which is how its
  image gets built.
- **Lab instances.** On the VPS each student starts their own copy of a lab
  from the lab list. It is served at `https://<lab>-<8 hex>.pwnshop.uk/`,
  stops after an hour unless they extend it, and is gone when stopped - so
  starting again is the reset. At most one per student, 10 at once.
- **The lab manager** (`labmanager/`, standard-library Python). The only
  thing that touches Docker: it starts and stops instances when the portal
  asks, over the internal `labcontrol` network with a token.
- **The lab proxy** (`labproxy/Caddyfile`). Routes each instance's hostname
  to its container. The host's Caddy sends it `*.pwnshop.uk` with a key.

The lab manager and lab proxy run only with `--profile labs`; on a laptop the
lab list just links to each lab's `url`.

### Ground rules

Labs are exploitable by design, so assume a student gets code execution
inside one.

- **The portal never holds the Docker socket.** A process holding it is
  effectively root on the VPS. Only the lab manager mounts it, and it offers
  the portal nothing but start / extend / stop / look up for catalogue labs
  (`labmanager/labs.json`).
- **Each lab instance gets its own internal Docker network**, shared only
  with the lab proxy. No lab can reach the portal, its database, the lab
  manager, another instance or the internet. The proxy only forwards
  requests carrying the key the host's Caddy adds, so a lab can't use it to
  reach another student's instance.
- **On the VPS, a lab never shares the portal's hostname - not even on
  another port.** Browsers send a host's cookies to every port on it, so the
  lab would receive every student's portal session cookie. Instances are on
  subdomains instead, and with `DJANGO_HTTPS=True` the portal's cookies are
  `__Host-` prefixed so a lab can't overwrite them from a subdomain.
- **Instances get CPU, memory and process limits** (per lab in
  `labs.json`), so one student can't starve everyone else - the VPS is a
  friend's 4 GB server shared with their own things.
- **The portal has no deliberate weaknesses.** Keep Django's defences on
  (CSRF, clickjacking protection, password validators).
- **Every lab serves HTTP on port 80** inside its container - the lab proxy
  forwards there.

## Repository layout

```
portal/                  Django project - manage.py lives here
  config/                settings, urls, wsgi/asgi, test runner
  core/                  public pages: the landing page at /
  labs/                  Lab model + admin, the lab list at /labs/, start/extend/stop,
                         manager.py - the client for the lab manager
  templates/             site-wide templates: base.html, registration/login.html
  static/                site-wide CSS
  Dockerfile  entrypoint.sh  requirements.txt  .env.example
labmanager/              starts/stops lab instances; holds the Docker socket
  manager.py  docker_api.py  labs.json (the catalogue)  tests.py  Dockerfile
labproxy/Caddyfile       routes <lab>-<8 hex>.pwnshop.uk to its instance
vulnerable-sites/
  fellside/              PHP 8.3 / SQLite / Apache outdoor-kit shop (see its README)
  e-commerce/            empty placeholder files
docker-compose.yml       portal, lab manager + proxy (profile "labs"), include: per lab
.env.example             compose settings for the VPS: lab manager token, proxy key
```

A new lab needs three things: its folder and `include:` line, an entry in
`labmanager/labs.json` (image, memory, CPUs) with the same slug, and a `Lab`
row in the admin.

## Current state

Update this list as things land. As of 2026-11-17:

- **Portal:** Django 6.1. A public landing page (hero, About, How it works,
  log-in call to action) with a shared nav bar and footer; login and logout;
  a `Lab` model managed in the admin, with Fellside seeded by a data
  migration; the lab list at `/labs/`, behind login. Accounts are created by
  staff in the admin - there is no sign-up page. `DJANGO_HTTPS=True` switches
  on the settings for running behind an HTTPS reverse proxy (proxy header,
  HTTPS redirect, Secure `__Host-` cookies, HSTS without subdomains). With
  `LAB_MANAGER_URL` set, the lab list has Start / extend / Stop for each
  student's own instance.
- **Deployment:** live at https://pwnshop.uk on a friend's Hetzner VPS
  (Ubuntu 26.04, 2 vCPU, 4 GB). Cloudflare proxies the domain (SSL Full
  (strict), Origin Certificate); the host's Caddy terminates HTTPS and
  forwards `pwnshop.uk` to the portal on `127.0.0.1:8000` and `*.pwnshop.uk`
  to the lab proxy on `127.0.0.1:8081`.
- **Brand:** the site is called **PWNShop**; colours `#743bbc` and white, as CSS variables at the top of
  `portal/static/css/style.css`. White on `#743bbc` passes WCAG AA, so the
  purple can carry text.
- **Fellside:** a complete shop that is intentionally *secure* so far - no
  vulnerabilities added yet. Its README lists where each class would fit.
- **Not built yet:** any vulnerability; the exploit/defend teaching content;
  progress tracking; restricting the VPS's ports 80/443 to Cloudflare's IP
  ranges; database backups.

## Open decisions - ask the team, don't pick one silently

1. **Where teaching content lives** - in the portal's database (editable in
   the admin) or as files beside each lab (versioned with its code).
2. **How a lab counts as completed** - a captured flag, self-reporting, or a
   check the portal runs.
3. **Accounts** - self sign-up, or issued by staff (the current behaviour).
4. **How vulnerabilities are switched on** - Fellside reserves a
   `[vulnerabilities]` section in `config/app.ini` for per-class toggles; the
   alternative is one container per vulnerable variant.

Settled on 2026-11-17: **per-student instances**, started on demand by the
lab manager (not the portal); **exposed as `<lab>-<8 hex>.pwnshop.uk`** - one
level of subdomain, so Cloudflare's free certificate covers it; **reset** by
stopping and starting again, with instances stopping after an hour unless
extended.

## Working on the labs (`vulnerable-sites/`)

- Vulnerabilities there are **intentional**. Don't fix them, and don't report
  them as findings in a review, unless asked.
- Every deliberate vulnerability is documented in that lab's README - route,
  vulnerability class, how it's switched on - so nobody mistakes it for a
  bug. Unintended flaws, on the other hand, do get fixed: they muddy the
  lesson.

## Working on the portal (`portal/`) - Django conventions

The portal's first draft was generated in a different style and has been
brought in line with these. Keep to them:

- Standard `django-admin startproject` layout. The project package is
  `config/`; keep the generated docstrings and section comments in
  `settings.py`, `urls.py`, `wsgi.py`, `asgi.py` and `manage.py`.
- Keep the stock apps and middleware (admin, auth, sessions, messages,
  staticfiles, clickjacking). Don't strip them out to slim the project down.
- Each app owns its URLs (`<app>/urls.py` with `app_name`), included from
  `config/urls.py`. Link with `{% url 'app:name' %}` and `reverse()`, never
  hard-coded paths.
- Site-wide templates live in `portal/templates/`; an app's templates in
  `<app>/templates/<app>/`, extending `base.html`. CSS is a static file
  loaded with `{% load static %}` - no inline `<style>` blocks.
- Data lives in models, managed in the Django admin. Seed data goes in a data
  migration. The portal never reads files from `vulnerable-sites/` - its image
  is built from `portal/` alone; a new lab is a `Lab` row (plus its
  `labmanager/labs.json` entry - see Repository layout).
- Generic class-based views (`ListView`, `TemplateView`, `LoginView`) where
  one fits, plain function views otherwise. Pages are protected with
  `LoginRequiredMixin` / `@login_required`.
- Configuration comes from `portal/.env` via python-dotenv, and every key is
  listed in `.env.example`. Required secrets are read with
  `os.environ['KEY']`, so a missing one fails at startup - never fall back to
  a default or a generated value. The exception is `LAB_MANAGER_URL` /
  `LAB_MANAGER_TOKEN`, shared with the lab manager, so they come from the
  root `.env` through `docker-compose.yml`.
- Single quotes in Python. Comments explain *why*, not what.
- Exact pins in `requirements.txt` (`==`).
- Tests use `django.test.TestCase` in each app's `tests.py`, are named for
  the behaviour they pin (`test_anonymous_visitor_is_sent_to_log_in`), and
  start with a module docstring saying what's covered. Every change keeps
  `python manage.py test` passing.

## Commands

```bash
# Portal only - from portal/, after creating .env from .env.example
python manage.py migrate
python manage.py test
python manage.py runserver

# Lab manager tests - from labmanager/, no packages needed
python -m unittest tests

# Everything on a laptop - from the repo root
docker compose up --build
docker compose exec portal python manage.py createsuperuser

# On the VPS - from the repo root, with the root .env filled in. Build the
# lab images first: the lab manager starts instances from them, it doesn't
# build them. The permanent Fellside container isn't needed there.
docker compose build fellside
docker compose --profile labs up -d --build portal labmanager labproxy
```

If you can't run Docker where you are, say so - don't claim the containers
work because the Django tests pass.

## Line endings

At least one of us develops on Windows. `.gitattributes` forces LF for shell
scripts and Dockerfiles, because a CRLF entrypoint makes a Linux container die
on start with a misleading "no such file or directory". Keep new scripts
covered by it.

## Git workflow - branches, commits and pull requests

These rules are not optional, for people or for Claude.

### Never commit or merge straight into `main`

- `main` is the shared, always-working branch. Nothing lands on it except
  through a reviewed pull request: no direct commits, no `git merge` into
  `main` locally, no `git push` to `main`.
- If you find yourself on `main` with changes to make, create a branch first
  (`git switch -c feat/...`) before committing anything.
- Never force-push `main`.

### Every piece of work lives on its own branch

Branch off an up-to-date `main` and name the branch `<type>/<short-description>`,
lower-case, words joined with hyphens:

| Prefix      | For                                               | Example                     |
|-------------|---------------------------------------------------|-----------------------------|
| `feat/`     | a new feature or lab                              | `feat/fellside-sqli`        |
| `fix/`      | a bug fix                                         | `fix/login-redirect-loop`   |
| `docs/`     | documentation only (README, CLAUDE.md)            | `docs/git-workflow`         |
| `refactor/` | restructuring with no change in behaviour         | `refactor/lab-model-fields` |
| `test/`     | adding or fixing tests only                       | `test/labs-list-view`       |
| `chore/`    | tooling, dependencies, Docker, CI, config         | `chore/pin-django-6-1-1`    |

One branch, one topic. Keep branches short-lived and delete them once
their pull request is merged.

### Every commit is fully commented

A commit message has a subject line **and** a body - never a subject alone,
and never `wip`, `fix`, `update` or similar.

- **Subject:** imperative mood, at most ~72 characters, no trailing full
  stop, e.g. `Add login requirement to the lab list page`.
- **Blank line**, then a **body** wrapped at ~72 characters that explains:
  - *what* changed, in more detail than the subject;
  - *why* it was needed - the problem, bug or decision behind it;
  - anything a reviewer should know: side effects, migrations, follow-ups,
    things deliberately left out.

```
Add login requirement to the lab list page

The lab list was reachable without an account, so anyone who found the
URL could see which labs exist and link straight to them. Wrap the view
in LoginRequiredMixin so anonymous visitors are sent to the log-in page
and returned to /labs/ afterwards.

Adds a test pinning the redirect for anonymous visitors.
```

Make small, focused commits - one logical change each - so every commit
message can honestly describe the whole commit.

### Only pull requests go into `main`

- Push the branch and open a pull request against `main`. The PR
  description says what changed, why, and how it was tested (e.g.
  `python manage.py test` passing, Docker build checked or not).
- At least one other teammate reviews and approves before it is merged.
- Merge only when the tests pass and the branch is up to date with `main`.
- Claude may create branches, commit to them and open pull requests when
  asked, but never merges a pull request into `main` itself - a teammate
  does that after review.

### No AI attribution

No AI attribution of any kind. Don't add a `Co-Authored-By: Claude ...`
trailer to commit messages, a "Generated with Claude Code" line to PR
descriptions, or any other mention that Claude wrote the work. This
overrides Claude Code's default attribution settings.

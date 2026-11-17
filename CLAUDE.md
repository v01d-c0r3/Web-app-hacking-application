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

One VPS running **1 + X** Docker containers:

- **1 - the portal** (`portal/`, Django). Accounts, login, the lab catalogue,
  and later the teaching content and progress tracking. It holds the only real
  user data on the box, so it is written securely - the opposite of the labs.
- **X - the labs** (`vulnerable-sites/<lab>/`). One container per deliberately
  vulnerable app, in whatever stack suits it (Fellside is PHP). Each lab folder
  is self-contained - its own `Dockerfile` and `docker-compose.yml` - and is
  pulled into the root `docker-compose.yml` with `include:`.

### Ground rules

Labs are exploitable by design, so assume a student gets code execution
inside one.

- **The portal never controls Docker.** It only links to labs: it doesn't
  start, stop or proxy them, and never mounts the Docker socket - a process
  holding the socket is effectively root on the VPS.
- **Each lab gets its own Docker network.** No lab can reach the portal, its
  database, or another lab.
- **On the VPS, a lab never shares the portal's hostname - not even on
  another port.** Browsers send a host's cookies to every port on it, so the
  lab would receive every student's portal session cookie. Use a separate
  (sub)domain per lab.
- **Labs get no open internet access and get CPU/memory limits** - above all
  the SSRF and command-injection labs, or they become a launch point against
  other machines, and one student can starve everyone else. *(Not set up
  yet - needed before the VPS goes live.)*
- **The portal has no deliberate weaknesses.** Keep Django's defences on
  (CSRF, clickjacking protection, password validators).

## Repository layout

```
portal/                  Django project - manage.py lives here
  config/                settings, urls, wsgi/asgi, test runner
  core/                  public pages: the landing page at /
  labs/                  Lab model + admin, the lab list page at /labs/
  templates/             site-wide templates: base.html, registration/login.html
  static/                site-wide CSS
  Dockerfile  entrypoint.sh  requirements.txt  .env.example
vulnerable-sites/
  fellside/              PHP 8.3 / SQLite / Apache outdoor-kit shop (see its README)
  e-commerce/            empty placeholder files
docker-compose.yml       the portal + an include: line per lab
```

## Current state

Update this list as things land. As of 2026-11-17:

- **Portal:** Django 6.1. A public landing page (hero, About, How it works,
  log-in call to action) with a shared nav bar and footer; login and logout;
  a `Lab` model managed in the admin, with Fellside seeded by a data
  migration; the lab list at `/labs/`, behind login. Accounts are created by
  staff in the admin - there is no sign-up page.
- **Brand:** the site is called **PWNShop**; colours `#743bbc` and white, as CSS variables at the top of
  `portal/static/css/style.css`. White on `#743bbc` passes WCAG AA, so the
  purple can carry text.
- **Fellside:** a complete shop that is intentionally *secure* so far - no
  vulnerabilities added yet. Its README lists where each class would fit.
- **Not built yet:** any vulnerability; the exploit/defend teaching content;
  progress tracking; the VPS deployment (hostnames, reverse proxy, HTTPS,
  lab egress blocking and resource limits).

## Open decisions - ask the team, don't pick one silently

1. **Shared or per-student lab instances?** Shared is simple, but one
   student's stored XSS or dropped table hits everyone. Per-student needs
   something that starts containers - which collides with "the portal never
   controls Docker".
2. **How labs are exposed on the VPS** - most likely a subdomain per lab
   behind a reverse proxy with HTTPS; it must respect the ground rules above.
3. **Where teaching content lives** - in the portal's database (editable in
   the admin) or as files beside each lab (versioned with its code).
4. **How a lab counts as completed** - a captured flag, self-reporting, or a
   check the portal runs.
5. **Accounts** - self sign-up, or issued by staff (the current behaviour).
6. **How vulnerabilities are switched on** - Fellside reserves a
   `[vulnerabilities]` section in `config/app.ini` for per-class toggles; the
   alternative is one container per vulnerable variant.
7. **Resetting labs** - on a schedule, on demand, or per session.

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
  is built from `portal/` alone; a new lab is a `Lab` row.
- Generic class-based views (`ListView`, `TemplateView`, `LoginView`) where
  one fits, plain function views otherwise. Pages are protected with
  `LoginRequiredMixin` / `@login_required`.
- Configuration comes from `portal/.env` via python-dotenv, and every key is
  listed in `.env.example`. Required secrets are read with
  `os.environ['KEY']`, so a missing one fails at startup - never fall back to
  a default or a generated value.
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

# Everything - from the repo root
docker compose up --build
docker compose exec portal python manage.py createsuperuser
```

If you can't run Docker where you are, say so - don't claim the containers
work because the Django tests pass.

## Line endings

At least one of us develops on Windows. `.gitattributes` forces LF for shell
scripts and Dockerfiles, because a CRLF entrypoint makes a Linux container die
on start with a misleading "no such file or directory". Keep new scripts
covered by it.

## Commits and pull requests

No AI attribution of any kind. Don't add a `Co-Authored-By: Claude ...`
trailer to commit messages, a "Generated with Claude Code" line to PR
descriptions, or any other mention that Claude wrote the work. This
overrides Claude Code's default attribution settings.

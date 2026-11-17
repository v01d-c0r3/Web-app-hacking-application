# Web-app-hacking-application

A cybersecurity teaching tool. Students log in to a Django portal and work
through labs - deliberately vulnerable web apps, each in its own container -
to exploit the most common web security issues and learn how to defend
against them.

Scope, architecture, conventions and open decisions are in
[CLAUDE.md](CLAUDE.md).

## Layout

- `portal/` - the Django portal: accounts, login and the lab catalogue.
- `vulnerable-sites/<lab>/` - one deliberately vulnerable app per folder, each
  with its own `Dockerfile` and `docker-compose.yml`.
- `docker-compose.yml` - runs the portal plus every lab.

## Run everything with Docker

Needs Docker Engine with Compose v2.20 or newer.

1. Copy `portal/.env.example` to `portal/.env` and set `DJANGO_SECRET_KEY`
   (the file explains how to generate one).
2. Start everything:

   ```bash
   docker compose up --build
   ```

3. Create a login (accounts are made by staff - there is no sign-up page yet):

   ```bash
   docker compose exec portal python manage.py createsuperuser
   ```

4. Open the portal at <http://localhost:8000> and the Fellside lab at
   <http://localhost:8080>.

Both ports are bound to `127.0.0.1`, so nothing is reachable from other
machines. Stop with `Ctrl+C` or `docker compose down`.

## Work on the portal without Docker

From `portal/`, with Python 3.12 or newer:

```bash
python -m venv .venv
source .venv/bin/activate        # Windows: .venv\Scripts\activate
pip install -r requirements.txt
cp .env.example .env             # Windows: copy .env.example .env
```

Set `DJANGO_SECRET_KEY` in `.env`, and `DJANGO_DEBUG=True` for local work.
Then:

```bash
python manage.py migrate
python manage.py createsuperuser
python manage.py test
python manage.py runserver
```

`runserver` only serves the portal. Start the labs with Docker Compose.

## Adding a lab

1. Add a folder under `vulnerable-sites/` with its own `Dockerfile` and
   `docker-compose.yml`. Put the service on its own network and publish its
   port on `127.0.0.1`.
2. Add it to the `include:` list in the root `docker-compose.yml`.
3. Add the lab in the Django admin (`/admin/` -> Labs), with the URL students
   should open.

## Vulnerabilities

- [ ] SQL Injection
- [ ] IDOR
- [ ] Cross-Site Scripting
- [ ] Broken Access Control
- [ ] OS Command Injection
- [ ] Cross-Site Request Forgery
- [ ] Server-Side Request Forgery
- [ ] Server-Side Template Injection

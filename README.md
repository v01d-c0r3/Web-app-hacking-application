# Web-app-hacking-application

## Local lab portal

This repository contains a Django portal and containerized practice websites.
The portal discovers site folders that have a container build file and a
`site.json` manifest.

### Start

Requires Docker Engine and Docker Compose v2.20 or newer:

```bash
docker compose up --build
```

Open the portal at <http://localhost:8000> and the Fellside lab at
<http://localhost:8080>. Stop the containers with `Ctrl+C` or
`docker compose down`.

Both ports are bound to loopback only. The Fellside container is on an
separate Docker network from the Django portal, which does not mount the
Docker socket or manage containers itself. Keep this lab local and do not
expose its ports to the internet or an untrusted network.

### Adding a site

Add a container build or Compose file and a `site.json` to a direct child
directory of `vulnerable-sites/`:

```json
{
  "name": "Example lab",
  "description": "Short description for the portal.",
  "url": "http://localhost:8090"
}
```

The URL must use plain HTTP and resolve to `localhost`, `127.0.0.1`, or
`::1`. Add the site's service to the root `docker-compose.yml` include list
and bind its published port to `127.0.0.1`.

### Current site content

- `vulnerable-sites/fellside/` is a working PHP/SQLite site running in a
  container. It currently has secure implementations and is a base for
  intentionally vulnerable variants.
- `vulnerable-sites/e-commerce/` is not configured yet.

### Development

Install `requirements.txt`, then run:

```bash
cd portal
python manage.py test
python manage.py runserver 127.0.0.1:8000
```

The Django development server only serves the portal; start the lab services
with Docker Compose as shown above.

### Potential Vulnerabilities

- [ ] SQL Injection
- [ ] IDOR
- [ ] Cross Site Scripting
- [ ] Broken Access Control
- [ ] OS Command Injection
- [ ] Client Side Request Forgery
- [ ] Server Side Request Forgery
- [ ] Server Side Template Injection
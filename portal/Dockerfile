FROM python:3.13-slim

# PYTHONUNBUFFERED keeps Django's output flowing to `docker compose logs`
# instead of sitting in a buffer until the container stops.
ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

WORKDIR /app

# Requirements are installed before the rest of the source is copied, so
# editing a template or a view reuses this layer instead of reinstalling
# Django on every build.
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Runs as a normal user, so a flaw in the portal is not a flaw with root behind
# it. /data is created here and handed to that user because Docker copies this
# ownership onto the named volume mounted there - which is what lets SQLite
# write to it.
RUN useradd --create-home --uid 10001 app \
    && mkdir -p /data \
    && chown app:app /data

# Copied as root, so the app user can read the code but not change it - even
# before the read-only filesystem set in docker-compose.yml.
COPY . .

# Git on Windows loses the executable bit easily, and a non-executable
# entrypoint fails with a bare "permission denied" before any code runs.
RUN chmod +x /app/entrypoint.sh

# collectstatic imports settings.py, which demands a secret key. This one is a
# throwaway that exists only while this layer builds - it is not part of the
# image or the running container.
RUN DJANGO_SECRET_KEY=build-time-only-not-a-real-key \
    python manage.py collectstatic --noinput

USER app

EXPOSE 8000

ENTRYPOINT ["/app/entrypoint.sh"]

# --no-control-socket: Gunicorn's runtime-management socket wants a writable
# home directory, which this read-only container doesn't have. Nothing here
# uses the socket, so it's switched off rather than warning on every start.
CMD ["gunicorn", "config.wsgi:application", \
     "--bind", "0.0.0.0:8000", \
     "--workers", "2", \
     "--no-control-socket", \
     "--access-logfile", "-"]

#!/bin/sh
set -e

# Migrations run on every start rather than as a manual deploy step: they are
# idempotent, and this way a container that starts is a container whose schema
# matches its code. If a migration fails, set -e stops the container instead of
# letting it serve requests against a half-migrated database.
python manage.py migrate --noinput

exec "$@"

#!/bin/sh
# Runs on every container start. The database is created and seeded on first request
# if it does not exist; set FELLSIDE_RESET=1 to wipe it and start fresh.
set -e
DB="${FELLSIDE_DB:-/var/www/data/fellside.sqlite}"
if [ "${FELLSIDE_RESET:-0}" = "1" ] && [ -f "$DB" ]; then
  echo "FELLSIDE_RESET=1, removing $DB"
  rm -f "$DB"
fi
chown -R www-data:www-data "$(dirname "$DB")" /var/www/fellside/public/uploads
exec docker-php-entrypoint "$@"

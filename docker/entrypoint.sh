#!/usr/bin/env sh

echo "$(date +"%Y-%m-%d %H:%M:%S,%3N") INFO Container start"

set -x

if test -n "$DEV_UID"; then
  usermod -u ${DEV_UID} www-data
  groupmod -g ${DEV_UID} www-data
fi

if [ $1 = "supervisord" ] || [ $1 = "php" ]; then
  git config --global --add safe.directory /var/www/app
  composer install --prefer-dist --no-progress --no-scripts --no-interaction
  chown -R www-data:www-data /var/www/app/
fi

exec "$@"
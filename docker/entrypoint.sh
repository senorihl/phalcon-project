#!/usr/bin/env sh

echo "$(date +"%Y-%m-%d %H:%M:%S,%3N") INFO Container start"

set -x

if test -n "$DEV_UID"; then
  usermod -u ${DEV_UID} www-data
  groupmod -g ${DEV_UID} www-data
fi

if test -n "$NODE_DEV_UID"; then
  usermod -u ${NODE_DEV_UID} www-data
  groupmod -g ${NODE_DEV_UID} www-data
fi

if [ $1 = "supervisord" ] || [ $1 = "php" ]; then
  git config --global --add safe.directory /var/www/app
  composer install --prefer-dist --no-progress --no-scripts --no-interaction
  chown -R www-data:www-data /var/www/
fi

if [ $1 = "yarn" ]; then
  git config --global --add safe.directory /var/www/app
  yarn install
  chown -R node:node /var/www/
fi

exec "$@"
#!/usr/bin/env sh

if test -n "$DEV_UID"; then
  usermod -u ${DEV_UID} www-data
  groupmod -g ${DEV_UID} www-data
fi

if [ $1 = "supervisord" ]; then
    composer install --prefer-dist --no-progress --no-scripts --no-interaction
    chown -R www-data:www-data /var/www/app/
fi

exec "$@"
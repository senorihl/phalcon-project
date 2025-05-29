#!/usr/bin/env sh

echo "$(date +"%Y-%m-%d %H:%M:%S,%3N") INFO Entrypoint started as $(whoami) ($(id -u):$(id -g))"

set -e

if test -n "$DEV_UID"; then
  usermod -u ${DEV_UID} www-data
  groupmod -g ${DEV_UID} www-data
fi

if test -n "$NODE_DEV_UID"; then
  usermod -u ${NODE_DEV_UID} node
  groupmod -g ${NODE_DEV_UID} node
fi

fixing_permissions () {
  echo "$(date +"%Y-%m-%d %H:%M:%S,%3N") INFO Changing ownership"
  chown www-data:www-data /var/www/app/
  chown -R www-data:www-data "$COMPOSER_HOME"
  ls -dA /var/www/app/* | grep -v git | xargs -r chown -R www-data:www-data
}

if [ $1 = "supervisord" ] || [ $1 = "php" ]; then
  echo "$(date +"%Y-%m-%d %H:%M:%S,%3N") INFO Add git safe directory"
  git config --global --add safe.directory /var/www/app
  echo "$(date +"%Y-%m-%d %H:%M:%S,%3N") INFO Install PHP dependencies"
  composer install --prefer-dist --no-progress --no-scripts --no-interaction
  fixing_permissions
fi

if [ $1 = "yarn" ]; then
  echo "$(date +"%Y-%m-%d %H:%M:%S,%3N") INFO Add git safe directory"
  git config --global --add safe.directory /var/www/app
  echo "$(date +"%Y-%m-%d %H:%M:%S,%3N") INFO Install JS dependencies"
  yarn install
  fixing_permissions
fi

echo "$(date +"%Y-%m-%d %H:%M:%S,%3N") INFO Running \"$@\""

exec "$@"
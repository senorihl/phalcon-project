#!/usr/bin/env sh

if test -n "$DEV_UID"; then
  usermod -u ${DEV_UID} www-data
  groupmod -g ${DEV_UID} www-data
fi

exec "$@"
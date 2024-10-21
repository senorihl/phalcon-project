#!/usr/bin/env sh

_status=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1/status)

if [ $_status -ne 200 ]; then
    return 127
fi

return 0
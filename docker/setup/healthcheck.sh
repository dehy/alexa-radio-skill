#!/bin/bash

set -x

_init () {
    scheme="http://"
    address="127.0.0.1:80"
    resource="/about"
    start=$(stat -c "%Y" /proc/1)
}


healthcheck_main () {
    # Get the http response code
    http_response=$(curl -H "User-Agent: Mozilla" -s -k -o /dev/null -I -w "%{http_code}" \
        ${scheme}${address}${resource})

    [ "$http_response" = "200" ] || [ "$http_response" = "401" ]
}

_init && healthcheck_main
#!/bin/sh
set -eu

template_dir=/etc/nginx/templates
config_path=/etc/nginx/conf.d/default.conf

if [ -f /etc/letsencrypt/live/angelow.online/fullchain.pem ] && [ -f /etc/letsencrypt/live/angelow.online/privkey.pem ]; then
  cp "$template_dir/https.conf" "$config_path"
else
  cp "$template_dir/http.conf" "$config_path"
fi

(
  while :; do
    sleep 12h
    nginx -s reload
  done
) &

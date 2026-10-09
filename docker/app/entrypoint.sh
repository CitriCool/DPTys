#!/bin/sh
set -e

# Archivos subidos y logs deben pertenecer a Apache (el contenedor del scheduler corre como www-data)
if [ "$(id -u)" = "0" ]; then
    mkdir -p /var/www/html/storage/app/public /var/www/html/storage/logs
    chown -R www-data:www-data /var/www/html/storage
fi

# Genera una APP_KEY local la primera vez y la reutiliza en los siguientes arranques
KEY_FILE=/var/www/html/storage/app/.app_key
if [ -z "$DPTYS_APP_KEY" ]; then
    if [ ! -f "$KEY_FILE" ]; then
        echo "base64:$(head -c 32 /dev/urandom | base64)" > "$KEY_FILE"
    fi
    DPTYS_APP_KEY=$(cat "$KEY_FILE")
    export DPTYS_APP_KEY
fi

# Apache no hereda variables de entorno por defecto: se las pasamos a PHP
if [ "$(id -u)" = "0" ]; then
    env | grep '^DPTYS_' | sed 's/^\([^=]*\)=\(.*\)$/export \1="\2"/' >> /etc/apache2/envvars
fi

php artisan package:discover --ansi >/dev/null
php artisan view:clear >/dev/null

# Solo el contenedor principal aplica migraciones y datos iniciales
if [ "$DPTYS_RUN_MIGRATIONS" = "true" ]; then
    php artisan migrate --force
    php artisan db:seed --force
    chown -R www-data:www-data /var/www/html/storage
fi

exec "$@"

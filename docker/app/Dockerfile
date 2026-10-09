FROM php:8.2-apache-bookworm

# Driver ODBC de Microsoft + extensiones PHP para SQL Server
RUN apt-get update \
 && apt-get install -y --no-install-recommends curl gnupg2 unixodbc-dev unzip git libzip-dev \
 && curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
 && curl -fsSL https://packages.microsoft.com/config/debian/12/prod.list > /etc/apt/sources.list.d/mssql-release.list \
 && apt-get update \
 && ACCEPT_EULA=Y apt-get install -y --no-install-recommends msodbcsql18 \
 && pecl install sqlsrv-5.11.1 pdo_sqlsrv-5.11.1 \
 && docker-php-ext-enable sqlsrv pdo_sqlsrv \
 && docker-php-ext-install zip \
 && a2enmod rewrite \
 && rm -rf /var/lib/apt/lists/*

# Límites de carga: archivos de tesis de hasta 50 MB
RUN { echo 'upload_max_filesize=50M'; echo 'post_max_size=520M'; echo 'max_file_uploads=110'; echo 'memory_limit=256M'; } \
    > /usr/local/etc/php/conf.d/dptys-uploads.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev \
 && chown -R www-data:www-data storage bootstrap/cache

COPY docker/app/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]

# Imagen local con PHP 8.2 + Apache (desarrollo).
# NOTA: en el VPS NO se usa esta imagen; allá corre nginx + desplegar/php/Dockerfile (php-fpm).
# Uso:  docker build -t vidasalud-apache .   (desde la raíz del proyecto)
#       docker run -p 8090:80 -v "$PWD:/var/www/html" vidasalud-apache
FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    vim nano \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    && docker-php-ext-install zip mysqli pdo pdo_mysql pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# rewrite (equivale al .htaccess) + compresión + caché de estáticos
RUN a2enmod rewrite deflate expires headers

# VirtualHost correcto: docroot = /var/www/html/public, sin listado de directorios.
# printf %s\n sustituye al echo con \n (que en comillas simples se quedaba literal
# y dejaba el conf en una sola línea -> Apache no arrancaba).
RUN printf '%s\n' \
    '<VirtualHost *:80>' \
    '    ServerName vidasaludclinica.com' \
    '    ServerAdmin webmaster@vidasaludclinica.com' \
    '    DocumentRoot /var/www/html/public' \
    '    ErrorLog ${APACHE_LOG_DIR}/vidasalud-error.log' \
    '    CustomLog ${APACHE_LOG_DIR}/vidasalud-access.log combined' \
    '' \
    '    <Directory /var/www/html/public>' \
    '        Options -Indexes +FollowSymLinks' \
    '        AllowOverride All' \
    '        Require all granted' \
    '    </Directory>' \
    '</VirtualHost>' \
    > /etc/apache2/sites-available/000-default.conf

# php.ini compartido con el despliegue del VPS (existe en el repo)
COPY desplegar/php/php.ini /usr/local/etc/php/php.ini

EXPOSE 80

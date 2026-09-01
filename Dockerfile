FROM php:8.2-apache

RUN a2enmod rewrite

COPY . /var/www/html/

RUN mkdir -p /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 755 /var/www/html/storage

EXPOSE 80

FROM php:8.2-apache

RUN docker-php-ext-install pdo_sqlite

RUN a2enmod rewrite

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html/storage
RUN chmod -R 755 /var/www/html/storage

EXPOSE 80

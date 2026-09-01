FROM php:8.2-apache

# Disable all MPMs, enable only prefork
RUN a2dismod mpm_event && a2dismod mpm_worker && a2enmod mpm_prefork

# Enable mod_rewrite
RUN a2enmod rewrite

# Copy app
COPY . /var/www/html/

# Setup storage
RUN mkdir -p /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 755 /var/www/html/storage

EXPOSE 80

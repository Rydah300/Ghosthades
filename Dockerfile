FROM php:8.2-apache

# Disable conflicting MPM modules, enable only prefork
RUN a2dismod mpm_event mpm_worker \
    && a2enmod mpm_prefork \
    && a2enmod rewrite

# Copy app files
COPY . /var/www/html/

# Create storage folder and set permissions
RUN mkdir -p /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 755 /var/www/html/storage

EXPOSE 80

FROM php:8.2-apache

# Enable mod_rewrite for .htaccess
RUN a2enmod rewrite

# Copy app files
COPY . /var/www/html/

# Create storage folder and set permissions
RUN mkdir -p /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 755 /var/www/html/storage

EXPOSE 80

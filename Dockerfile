FROM php:8.2-apache

# Install SQLite3 dev package
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && apt-get clean

# Enable mod_rewrite for .htaccess
RUN a2enmod rewrite

# Copy app files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage
RUN chmod -R 755 /var/www/html/storage

EXPOSE 80

FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    unzip \
    && docker-php-ext-install pdo pdo_sqlite

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Environment Variables
ENV DB_PATH=/var/www/html/data/database.sqlite

# Prepare Data Directory
RUN mkdir -p /var/www/html/data

# Initialize Fresh Database (No Dummy Data)
RUN DB_PATH=/var/www/html/data/database.sqlite php /var/www/html/backend/init_fresh_database.php && \
    mv /var/www/html/data/database.sqlite /var/www/html/database_seed.sqlite

# Create empty uploads seed folder (for clean installation)
RUN mkdir -p /var/www/html/uploads_seed

# Copy Entrypoint Script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Set Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/uploads \
    && chmod -R 777 /var/www/html/data

# Expose Port
EXPOSE 80

# Entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]

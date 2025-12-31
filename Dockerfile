FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    unzip \
    openssh-server \
    && docker-php-ext-install pdo pdo_sqlite \
    && mkdir -p /var/run/sshd

# Configure SSH
RUN sed -i 's/#PermitRootLogin prohibit-password/PermitRootLogin no/' /etc/ssh/sshd_config
RUN sed -i 's/#PasswordAuthentication yes/PasswordAuthentication no/' /etc/ssh/sshd_config

# Create admin user for SSH access
RUN useradd -m -s /bin/bash admin
RUN mkdir -p /home/admin/.ssh
COPY ssh_keys/admin_key.pub /home/admin/.ssh/authorized_keys
RUN chown -R admin:admin /home/admin/.ssh && chmod 600 /home/admin/.ssh/authorized_keys

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure Apache DocumentRoot to point to frontend
ENV APACHE_DOCUMENT_ROOT /var/www/html/frontend
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Create Alias for assets
RUN echo "Alias /assets /var/www/html/assets" >> /etc/apache2/apache2.conf
RUN echo "<Directory /var/www/html/assets>" >> /etc/apache2/apache2.conf
RUN echo "    Options Indexes FollowSymLinks" >> /etc/apache2/apache2.conf
RUN echo "    AllowOverride None" >> /etc/apache2/apache2.conf
RUN echo "    Require all granted" >> /etc/apache2/apache2.conf
RUN echo "</Directory>" >> /etc/apache2/apache2.conf

# Create Alias for backend
RUN echo "Alias /backend /var/www/html/backend" >> /etc/apache2/apache2.conf
RUN echo "<Directory /var/www/html/backend>" >> /etc/apache2/apache2.conf
RUN echo "    Options Indexes FollowSymLinks" >> /etc/apache2/apache2.conf
RUN echo "    AllowOverride All" >> /etc/apache2/apache2.conf
RUN echo "    Require all granted" >> /etc/apache2/apache2.conf
RUN echo "</Directory>" >> /etc/apache2/apache2.conf

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
EXPOSE 80 22

# Entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]

# Dockerfile

FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install PHP extensions (if needed)
RUN docker-php-ext-install pdo_mysql

# Enable Apache modules (if needed)
RUN a2enmod rewrite

# Copy the source code into the container
COPY ./src /var/www/html

# Copy custom Apache configuration
COPY ./apache-conf/000-default.conf /etc/apache2/sites-available/000-default.conf

# Ensure Apache can access the directory
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80 for the web server
EXPOSE 80


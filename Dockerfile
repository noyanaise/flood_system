# 1. Use the official PHP image with Apache pre-installed
FROM php:8.2-apache

# 2. Install essential system updates and required PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql

# 3. Enable Apache rewrite engine for clean routing
RUN a2enmod rewrite

# 4. Set the working directory inside the container
WORKDIR /var/www/html

# 5. Copy all your application code files from GitHub into the container
COPY . /var/www/html/

# 6. Set correct permissions so Apache can read and execute your PHP files securely
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# 7. Configure Apache to listen on the dynamic port Railway assigns
RUN sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/g' /etc/apache2/sites-available/000-default.conf

# 8. Expose the environment port container path
EXPOSE ${PORT}

# 9. Start Apache in the foreground
CMD ["apache2-foreground"]

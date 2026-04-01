# FoodFusion — deploy as a Web Service on Render (Docker runtime).
# After deploy: set env vars (see includes/config.example.php / Render dashboard),
# open /setup.php once to create tables, then use the site.
FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers

WORKDIR /var/www/html
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

COPY docker/apache-start.sh /usr/local/bin/apache-start.sh
RUN chmod +x /usr/local/bin/apache-start.sh

CMD ["apache-start.sh"]

# PHP + Apache for Railway / Render (listens on $PORT).
#
# Railway quick setup:
# 1) New → MySQL (or add MySQL to the project).
# 2) On this web service → Variables → add variables from MySQL (MYSQLHOST, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE, MYSQLPORT).
# 3) Settings → Networking → Generate Domain (otherwise you may see 404).
# 4) After deploy, open https://YOUR_DOMAIN/setup.php once, then use the site.
FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers

WORKDIR /var/www/html
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

COPY docker/apache-start.sh /usr/local/bin/apache-start.sh
RUN chmod +x /usr/local/bin/apache-start.sh

CMD ["apache-start.sh"]

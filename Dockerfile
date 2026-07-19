FROM php:8.2-apache

RUN docker-php-ext-install mysqli \
    && a2enmod rewrite

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 10000

CMD sed -i "s/80/${PORT:-80}/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf \
    && apache2-foreground

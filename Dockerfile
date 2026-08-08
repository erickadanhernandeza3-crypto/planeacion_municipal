FROM php:8.2-apache

RUN docker-php-ext-install mysqli \
    && a2enmod rewrite

# La imagen base no incluye php.ini; sin él PHP muestra los warnings dentro
# de las respuestas y rompe el JSON de las peticiones AJAX.
COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 10000

CMD sed -i "s/80/${PORT:-80}/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf \
    && apache2-foreground

FROM php:8.2-cli

WORKDIR /var/www/html

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 1234

CMD ["php", "-S", "0.0.0.0:1234"]

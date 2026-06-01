FROM php:8.4-apache

RUN docker-php-ext-install pdo_mysql

COPY . /var/www/html

EXPOSE 80
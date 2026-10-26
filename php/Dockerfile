FROM php:8.2-fpm-alpine

# Instalar dependencias del sistema y extensiones de MySQL necesarias para PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && docker-php-ext-enable mysqli pdo_mysql

WORKDIR /var/www/html

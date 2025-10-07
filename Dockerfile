FROM php:8.2-fpm

RUN apt-get update && apt-get install -y zlib1g-dev libpng-dev libzip-dev libpq-dev\
    && docker-php-ext-install pdo pdo_pgsql zip gd

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN apt-get install -y --no-install-recommends \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libxpm-dev \
    libvpx-dev \
&& docker-php-ext-configure gd \
    --with-xpm=/usr/include/ \
    --with-jpeg=/usr/include/ \
    --with-freetype=/usr/include/ \
&& docker-php-ext-install gd

RUN apt-get update -y

RUN apt-get install -y \
      ca-certificates \
      unzip

RUN chown -R www-data:www-data /var/www/html

RUN docker-php-ext-configure opcache --enable-opcache \
    && docker-php-ext-install opcache
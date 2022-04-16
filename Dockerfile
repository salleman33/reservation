FROM php:8.0-apache

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer --version

RUN apt update && apt install -y wget unzip zip libzip-dev libbz2-dev icu-devtools libicu-dev libpng-dev 

RUN docker-php-ext-install -j$(nproc) opcache zip bz2 exif intl gd mysqli 


RUN cd /var/www/html 
RUN wget https://github.com/glpi-project/glpi/releases/download/10.0.0-rc3/glpi-10.0.0-rc3.tgz && \
	tar xvf glpi-10.0.0-rc3.tgz && rm glpi-10.0.0-rc3.tgz
RUN chown -R www-data glpi/files glpi/config




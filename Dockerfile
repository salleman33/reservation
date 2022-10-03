FROM php:8.0-apache

ARG RELEASE=10.0.3

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer --version

RUN apt update && apt install -y wget unzip zip libzip-dev libbz2-dev icu-devtools libicu-dev libpng-dev libldap2-dev

RUN docker-php-ext-install -j$(nproc) ldap opcache zip bz2 exif intl gd mysqli 

# securité
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
	echo "session.cookie_httponly=On" >> "$PHP_INI_DIR/php.ini"

RUN cd /var/www/html 
RUN wget https://github.com/glpi-project/glpi/releases/download/${RELEASE}/glpi-${RELEASE}.tgz && \
	tar xvf glpi-${RELEASE}.tgz && rm glpi-${RELEASE}.tgz
RUN chown -R www-data glpi/files glpi/config




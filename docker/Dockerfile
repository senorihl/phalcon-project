# syntax=docker/dockerfile:1

FROM ubuntu:24.04 AS core

RUN export DEBIAN_FRONTEND=noninteractive \
    && mkdir -p /var/www/app \
    && mkdir -p /var/www/.composer \
    && chown www-data:www-data -R /var/www/app \
    && chown www-data:www-data -R /var/www/.composer \
    && apt update -y \
    && apt install --no-install-recommends -y \
        software-properties-common \
        apt-utils \
        apt-transport-https \
        ca-certificates \
        curl \
        lsb-release \
        git \
        unzip \
        nginx nginx-extras \
        supervisor \
    && apt clean autoclean \
    && apt-get autoremove -y \
    && rm -rf /var/lib/{apt,dpkg,cache,log} \
    && rm -rf /var/www/html/

RUN export DEBIAN_FRONTEND=noninteractive \
    && LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php \
    && apt update -y \
    && apt install -y \
        php8.3-fpm \
        php8.3-mbstring \
        php8.3-curl \
        php8.3-dom \
        php8.3-fileinfo \
        php8.3-pdo \
        php8.3-pgsql \
        php8.3-mysql \
        php8.3-sqlite3 \
        php8.3-gettext \
        php8.3-phalcon5 \
        php8.3-zip \
        php8.3-xdebug \
    && apt clean autoclean \
    && apt-get autoremove -y \
    && rm -rf /var/lib/{apt,dpkg,cache,log}/ \
    && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php -r "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');" \
    && mv composer.phar /usr/local/bin/composer \
    && php -v \
    && composer -V

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/healthcheck.sh /usr/local/bin/healthcheck.sh
COPY docker/supervisor/supervisor.conf /etc/supervisord.conf
COPY docker/php/php.ini /etc/php/8.3/fpm/php.ini
COPY docker/php/php-fpm.conf /etc/php/8.3/fpm/php-fpm.conf
COPY docker/php/xdebug.ini /etc/php/8.3/mods-available/xdebug.ini
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/site.conf /etc/nginx/sites-available/default

WORKDIR /var/www/app/

COPY composer.json composer.lock /var/www/app/

ENV COMPOSER_HOME=/var/www/.composer

RUN --mount=type=cache,target=/var/www/.composer \
     composer install && chown www-data:www-data -R /var/www/.composer

RUN deluser ubuntu

COPY --chown=www-data:www-data . /var/www/app/

EXPOSE 80

HEALTHCHECK --start-period=5s CMD [ "/usr/local/bin/healthcheck.sh" ]

ENTRYPOINT [ "/usr/local/bin/entrypoint.sh" ]

CMD [ "supervisord", "-c", "/etc/supervisord.conf" ]

FROM core AS runnable

ARG PHALCON_MODULE=web

COPY . /var/www/app/

RUN unset COMPOSER_HOME \
    && export DEBIAN_FRONTEND=noninteractive \
    && find . -type d | grep .git | xargs rm -rf \
    && apt autoremove -y php8.3-xdebug \
    && sed -i 's/^/;/' /etc/php/8.0/mods-available/xdebug.ini \
    && composer clear-cache \
    && composer install --prefer-dist \
    && sed -i '/^#/d' /etc/nginx/sites-available/default \
    && sed -i '/^$/d' /etc/nginx/sites-available/default \
    && sed -i 's/\$http_x_phalcon_module if_not_empty/'"${PHALCON_MODULE}"'/' /etc/nginx/sites-available/default


HEALTHCHECK CMD [ "/usr/local/bin/healthcheck.sh" ]


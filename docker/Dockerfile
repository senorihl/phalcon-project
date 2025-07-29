# syntax=docker/dockerfile:1

ARG NODE_VERSION=lts
ARG PHP_VERSION=8.4
ARG PHALCON_VERSION=5.9.*

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

ARG PHP_VERSION
ARG PHALCON_VERSION

RUN export DEBIAN_FRONTEND=noninteractive \
    && LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php \
    && apt update -y \
    && apt install -y \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-dom \
        php${PHP_VERSION}-fileinfo \
        php${PHP_VERSION}-intl \
        php${PHP_VERSION}-bcmath \
        php${PHP_VERSION}-pdo \
        php${PHP_VERSION}-pgsql \
        php${PHP_VERSION}-mysql \
        php${PHP_VERSION}-sqlite3 \
        php${PHP_VERSION}-gettext \
        php${PHP_VERSION}-phalcon=${PHALCON_VERSION} \
        php${PHP_VERSION}-zip \
        php${PHP_VERSION}-yaml \
        php${PHP_VERSION}-xdebug \
    && apt clean autoclean \
    && apt-get autoremove -y \
    && rm -rf /var/lib/{apt,dpkg,cache,log}/ \
    && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php -r "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');" \
    && mv composer.phar /usr/local/bin/composer \
    && update-alternatives --install /usr/bin/php-fpm fpm /usr/sbin/php-fpm${PHP_VERSION} 100 \
    && php -v \
    && php-fpm -v \
    && composer -V

COPY docker/supervisor/supervisor.conf /etc/supervisord.conf
COPY docker/php/php.ini /etc/php/${PHP_VERSION}/fpm/php.ini
COPY docker/php/php-fpm.conf /etc/php/${PHP_VERSION}/fpm/php-fpm.conf
COPY docker/php/xdebug.ini /etc/php/${PHP_VERSION}/mods-available/xdebug.ini
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/site.conf /etc/nginx/sites-available/default

WORKDIR /var/www/app/

COPY composer.* /var/www/app/

ENV COMPOSER_HOME=/var/www/.composer

#RUN --mount=type=cache,target=/var/www/.composer \
#     composer install && chown www-data:www-data -R /var/www/.composer

RUN deluser ubuntu

COPY --chown=www-data:www-data . /var/www/app/

ENV PHP_VERSION=${PHP_VERSION}

EXPOSE 80

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

ENTRYPOINT [ "/usr/local/bin/entrypoint.sh" ]

CMD [ "supervisord", "-c", "/etc/supervisord.conf" ]

FROM node:${NODE_VERSION} AS assets-builder

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

WORKDIR /var/www/app/

COPY package.json yarn.lock *.js /var/www/app/

ENV YARN_CACHE_FOLDER=/var/www/.yarn

RUN --mount=type=cache,target=/var/www/.yarn \
     yarn install && chown node:node -R /var/www/.yarn

COPY --chown=node:node assets/ /var/www/app/assets/
COPY --chown=node:node views/ /var/www/app/views/

CMD [ "yarn", "webpack", "--progress", "--env", "production" ]

FROM assets-builder AS production-assets

RUN yarn webpack --progress --env production

FROM core AS runnable

ARG PHALCON_MODULE=web

COPY . /var/www/app/

RUN unset COMPOSER_HOME \
    && export DEBIAN_FRONTEND=noninteractive \
    && find . -type d | grep .git | xargs rm -rf \
    && apt autoremove -y php${PHP_VERSION}-xdebug \
    && sed -i 's/^/;/' /etc/php/${PHP_VERSION}/mods-available/xdebug.ini \
    && composer clear-cache \
    && composer install --prefer-dist \
    && sed -i '/^#/d' /etc/nginx/sites-available/default \
    && sed -i '/^$/d' /etc/nginx/sites-available/default \
    && sed -i 's/\$http_x_phalcon_module if_not_empty/'"${PHALCON_MODULE}"'/' /etc/nginx/sites-available/default

COPY --from=production-assets \
    --chown=www-data:www-data \
    /var/www/app/public/assets/ \
    /var/www/app/public/assets/

COPY docker/healthcheck.sh /usr/local/bin/healthcheck.sh

HEALTHCHECK CMD [ "/usr/local/bin/healthcheck.sh" ]


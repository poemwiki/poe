# syntax=docker/dockerfile:1

ARG PHP_VERSION=8.3
FROM docker.io/library/php:${PHP_VERSION}-fpm

LABEL "language"="php"
LABEL "framework"="laravel"

ENV APP_ENV=prod
ENV APP_DEBUG=true

WORKDIR /var/www

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && sync

RUN set -eux \
  && apt update \
  && apt install -y cron curl gettext git grep libicu-dev nginx pkg-config unzip \
  && rm -rf /var/www/html \
  && curl -fsSL https://deb.nodesource.com/setup_18.x -o nodesource_setup.sh \
  && bash nodesource_setup.sh \
  && apt install -y nodejs \
  && npm install -g pnpm \
  && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions @composer apcu bcmath gd intl mysqli opcache pcntl \
    pdo_mysql sysvsem zip curl exif gmp json redis

RUN cat <<'EOF' > /etc/nginx/sites-enabled/default
server {
    listen 8080;
    root /var/www;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php index.html;
    charset utf-8;

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_hide_header X-Powered-By;
    }

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
        gzip_static on;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    error_log /dev/stderr;
    access_log /dev/stderr;
}
EOF

RUN chown -R www-data:www-data /var/www
COPY --link --chown=www-data:www-data --chmod=755 . /var/www
RUN mkdir -p /var/www/bootstrap/cache && chown -R www-data:www-data /var/www/bootstrap/cache

USER www-data
RUN set -eux \
    && if [ -f composer.json ]; then composer config --no-plugins allow-plugins.easywechat-composer/easywechat-composer true; fi \
    && if [ -f composer.json ]; then composer install --optimize-autoloader --classmap-authoritative --no-dev; fi \
    && if [ -f package.json ]; then pnpm install; fi

RUN <<EOF
    set -ux

    if [ -x artisan ]; then
        php artisan optimize
        php artisan config:cache
        php artisan event:cache
        php artisan route:cache
        php artisan view:cache
    fi

    if [ -x bin/console ]; then
        composer dump-env prod
        composer run-script --no-dev post-install-cmd
        php bin/console cache:clear
        php bin/console asset-map:compile
    fi

    if [ -x ./node_modules/.bin/encore ]; then
        ./node_modules/.bin/encore production
    fi

    if grep -q '"build":' package.json; then
        pnpm run build
    fi
EOF

USER root

RUN if [ -d /var/www/public ]; then sed -i 's|root /var/www;|root /var/www/public;|' /etc/nginx/sites-enabled/default; fi

CMD nginx; php-fpm;

EXPOSE 8080

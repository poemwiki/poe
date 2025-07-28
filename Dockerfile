# syntax=docker/dockerfile:1

ARG PHP_VERSION=8.3
FROM docker.io/library/php:${PHP_VERSION}-fpm

LABEL "language"="php"
LABEL "framework"="laravel"

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
  && rm -rf /var/lib/apt/lists/* \
  && install-php-extensions @composer apcu bcmath gd intl mysqli pcntl \
     pdo_mysql sysvsem zip exif gmp redis

# PHP性能优化配置
RUN cat <<'EOF' > /usr/local/etc/php/conf.d/99-performance.ini
; JIT优化
opcache.jit_buffer_size=128M
opcache.jit=tracing
opcache.jit_hot_func=16
opcache.jit_hot_loop=16

; OPcache优化
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256M
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.revalidate_freq=0

; 内存优化
memory_limit=512M
post_max_size=50M
upload_max_filesize=50M
max_file_uploads=20

; 性能优化
expose_php=Off
realpath_cache_size=4M
realpath_cache_ttl=600
max_input_vars=3000
max_input_time=60
max_execution_time=60
EOF

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

COPY --link . /var/www
RUN chown -R www-data:www-data /var/www \
    && mkdir -p /var/www/bootstrap/cache

USER www-data
RUN set -eux \
    && if [ -f composer.json ]; then composer config --no-plugins allow-plugins.easywechat-composer/easywechat-composer true; fi \
    && if [ -f composer.json ]; then composer install --optimize-autoloader --classmap-authoritative --no-dev; fi \
    && if [ -f package.json ]; then pnpm install; fi

RUN <<EOF
    set -ux

    if grep -q '"build":' package.json; then
        pnpm run build
    fi
EOF

USER root

RUN if [ -d /var/www/public ]; then sed -i 's|root /var/www;|root /var/www/public;|' /etc/nginx/sites-enabled/default; fi

CMD nginx; php-fpm;

EXPOSE 8080

# 使用专门的Laravel优化镜像
FROM webdevops/php-nginx:8.3-alpine

LABEL "language"="php"
LABEL "framework"="laravel"

# 设置环境变量
ENV WEB_DOCUMENT_ROOT=/app/public
ENV WEB_DOCUMENT_INDEX=index.php
ENV WEB_ALIAS_DOMAIN=*.zeabur.app
ENV WEB_PHP_TIMEOUT=30
ENV WEB_PHP_SOCKET=""
ENV SERVICE_NGINX_CLIENT_MAX_BODY_SIZE="50M"

WORKDIR /app

# PHP性能优化配置
COPY <<EOF /opt/docker/etc/php/php.ini
; JIT优化
opcache.jit_buffer_size=128M
opcache.jit=tracing
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256M
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.revalidate_freq=0

; 内存和性能优化
memory_limit=512M
post_max_size=50M
upload_max_filesize=50M
max_file_uploads=20
expose_php=Off
realpath_cache_size=4M
realpath_cache_ttl=600
max_execution_time=30

; Session优化
session.save_handler=files
session.save_path="/tmp"
session.gc_maxlifetime=7200
EOF

# PHP-FPM优化配置 - 覆盖默认的www pool
COPY <<EOF /opt/docker/etc/php/fpm/pool.d/www.conf
[global]
error_log = /proc/self/fd/2
daemonize = no

[www]
user = application
group = application
listen = 127.0.0.1:9000
pm = dynamic
pm.max_children = 20
pm.start_servers = 4
pm.min_spare_servers = 2
pm.max_spare_servers = 6
pm.max_requests = 1000
request_terminate_timeout = 30
access.log = /proc/self/fd/2
EOF

# Nginx优化配置
COPY <<EOF /opt/docker/etc/nginx/vhost.conf
server {
    listen 8080 default_server;
    listen [::]:8080 default_server;

    server_name _;
    root /app/public;
    index index.php index.html;

    # 静态文件缓存
    location ~* \.(css|js|gif|jpe?g|png|ico|woff|woff2|ttf|svg|eot|otf)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header X-Content-Type-Options nosniff;
        access_log off;
    }

    # Gzip压缩
    gzip on;
    gzip_vary on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        try_files \$uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param PATH_INFO \$fastcgi_path_info;

        # FastCGI优化
        fastcgi_read_timeout 30s;
        fastcgi_send_timeout 30s;
        fastcgi_connect_timeout 5s;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF

# 安装Node.js和包管理器
RUN apk add --no-cache nodejs npm && npm install -g pnpm

# 复制应用文件
COPY --chown=application:application . /app

# 安装依赖并构建
USER application
RUN if [ -f composer.json ]; then \
        composer config --no-plugins allow-plugins.easywechat-composer/easywechat-composer true && \
        composer install --optimize-autoloader --classmap-authoritative --no-dev; &&\
        php artisan passport:install\
    fi

RUN if [ -f package.json ]; then \
        pnpm install && \
        if grep -q '"build":' package.json; then pnpm run build; fi; \
    fi


USER root

EXPOSE 8080
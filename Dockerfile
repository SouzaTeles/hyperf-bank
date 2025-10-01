# Default Dockerfile
#
# @link     https://www.hyperf.io
# @document https://hyperf.wiki
# @contact  group@hyperf.io
# @license  https://github.com/hyperf/hyperf/blob/master/LICENSE

FROM hyperf/hyperf:8.3-alpine-v3.19-swoole
LABEL maintainer="Hyperf Developers <group@hyperf.io>" version="1.0" license="MIT" app.name="Hyperf"

##
# ---------- env settings ----------
##
# --build-arg timezone=Asia/Shanghai
ARG timezone

ENV TIMEZONE=${timezone:-"America/Sao_Paulo"} \
    APP_ENV=prod \
    SCAN_CACHEABLE=true

# update
RUN set -ex \
    && php -v \
    && php -m \
    && php --ri swoole \
    && cd /etc/php* \
    && { \
        echo "upload_max_filesize=128M"; \
        echo "post_max_size=128M"; \
        echo "memory_limit=1G"; \
        echo "date.timezone=${TIMEZONE}"; \
    } | tee conf.d/99_overrides.ini \
    && ln -sf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime \
    && echo "${TIMEZONE}" > /etc/timezone \
    && rm -rf /var/cache/apk/* /tmp/* /usr/share/man \
    && echo -e "\033[42;37m Build Completed :).\033[0m\n"

# Utilities needed by entrypoint (mysqladmin for readiness checks)
RUN apk add --no-cache mariadb-client

# Install Node.js and npm for MJML
RUN apk add --no-cache nodejs npm

# Install MJML globally
RUN npm install -g mjml

# Install cron
RUN apk add --no-cache dcron

WORKDIR /opt/www

# Composer Cache
# COPY ./composer.* /opt/www/
# RUN composer install --no-dev --no-scripts

COPY . /opt/www
RUN composer install --no-dev -o && php bin/hyperf.php

# Setup cron
RUN echo "* * * * * cd /opt/www && php bin/hyperf.php withdraw:process-scheduled >> /opt/www/runtime/logs/cron.log 2>&1" > /etc/crontabs/root && \
    chmod 0644 /etc/crontabs/root

# Start cron and Hyperf server
EXPOSE 9501
CMD crond -l 2 && php bin/hyperf.php server:watch

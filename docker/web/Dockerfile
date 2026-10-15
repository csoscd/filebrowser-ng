################################################################################
# filebrowser-ng Web – Docker image
#
# Stages:
#   1. composer  – installs PHP dependencies
#   2. final     – runtime image (cs1711/filebrowser-ng)
#
# Build from repository root:
#   docker build -f docker/web/Dockerfile -t cs1711/filebrowser-ng:latest .
#
# @author  Christian Schulz <technik@meinetechnikwelt.rocks>
# @license GNU General Public License version 3 or later
################################################################################

# ── Stage 1: Composer dependencies ───────────────────────────────────────────
FROM composer:2 AS composer-build

WORKDIR /build

COPY composer.json ./
COPY composer.lock* ./

RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --optimize-autoloader \
        --no-scripts


# ── Stage 2: Runtime image ────────────────────────────────────────────────────
FROM cs1711/cs_php-nginx-fpm:latest-debian

ARG APP_VERSION=unknown
ENV APP_VERSION=${APP_VERSION}

LABEL org.opencontainers.image.title="filebrowser-ng" \
      org.opencontainers.image.description="Web-based file manager for SMB/Samba shares" \
      org.opencontainers.image.authors="Christian Schulz <technik@meinetechnikwelt.rocks>" \
      org.opencontainers.image.licenses="GPL-3.0-or-later" \
      org.opencontainers.image.source="https://github.com/csoscd/filebrowser-ng"

# nosemgrep: dockerfile.security.last-user-is-root.last-user-is-root
USER root

# smbclient (Wrapped backend) + libsmbclient-php (Native backend, enables append/disk-info)
RUN apt-get update && apt-get install -y --no-install-recommends \
        smbclient \
        libsmbclient-dev \
    && pecl install smbclient \
    && docker-php-ext-enable smbclient \
    && apt-get remove -y libsmbclient-dev \
    && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

# The base image's PHP-FPM pool config has no user/group set.
# Our entrypoint runs as root (for chown of bind-mounted volumes), so
# the FPM master also starts as root. Set worker user/group and socket
# ownership so nginx workers (nobody:nogroup) can connect.
RUN printf '[www]\nuser = nobody\ngroup = nogroup\nlisten.owner = nobody\nlisten.group = nogroup\nlisten.mode = 0660\n' \
    > /usr/local/etc/php-fpm.d/zz-user.conf

# Composer vendor
COPY --from=composer-build --chown=nobody:nogroup /build/vendor /var/www/libs/vendor

# Application source
COPY --chown=nobody:nogroup src/          /var/www/html/src/
COPY --chown=nobody:nogroup templates/    /var/www/html/templates/
COPY --chown=nobody:nogroup lang/         /var/www/html/lang/
COPY --chown=nobody:nogroup public/       /var/www/html/public/

# Runtime directories
RUN mkdir -p /var/www/conf /var/www/data /var/www/log \
    && chown -R nobody:nogroup /var/www/conf /var/www/data /var/www/log

COPY docker/web/nginx-default.conf /etc/nginx/conf.d/default.conf
COPY docker/web/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

VOLUME ["/var/www/log", "/var/www/data"]

EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]

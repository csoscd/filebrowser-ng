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

# smbclient is required by icewind/smb
RUN apt-get update && apt-get install -y --no-install-recommends \
        smbclient \
    && rm -rf /var/lib/apt/lists/*

# The base image's PHP-FPM pool config has no user/group set.
# Add an override so FPM workers run as nobody:nogroup.
RUN printf '[www]\nuser = nobody\ngroup = nogroup\n' \
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

COPY docker/web/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

VOLUME ["/var/www/log", "/var/www/data"]

EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]

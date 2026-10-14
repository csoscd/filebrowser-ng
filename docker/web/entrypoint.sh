#!/bin/bash
# =============================================================================
# filebrowser-ng – entrypoint.sh
#
# 1. Generate /var/www/conf/config.json from environment variables.
# 2. Fix ownership of runtime directories.
# 3. Start supervisord (nginx + php-fpm).
#
# Required environment variables:
#   SMB_SERVER   – Samba server hostname or IP
#
# Optional environment variables (defaults shown):
#   SMB_DOMAIN            ""
#   SESSION_LIFETIME      3600
#   SESSION_NAME          filebrowser_sess
#   LOG_PATH              /var/www/log/filebrowser-ng.log
#   LOG_LEVEL             info
#   LOG_MAX_DAYS          30
#   I18N_LANGUAGE         en
#   DB_PATH               /var/www/data/filebrowser.sqlite
#
# @author  Christian Schulz <technik@meinetechnikwelt.rocks>
# @license GNU General Public License version 3 or later
# =============================================================================

set -eu

CONFIG_FILE="/var/www/conf/config.json"

log_info()  { echo "[entrypoint] [INFO]  $(date -Iseconds) $*"; }
log_error() { echo "[entrypoint] [ERROR] $(date -Iseconds) $*" >&2; }

# ── Validate required variables ───────────────────────────────────────────────
if [ -z "${SMB_SERVER:-}" ]; then
    log_error "SMB_SERVER is required but not set."
    exit 1
fi

mkdir -p /var/www/conf /var/www/data /var/www/log

log_info "Generating ${CONFIG_FILE}..."

php -r "
\$config = [
    'smb' => [
        'server' => getenv('SMB_SERVER'),
        'domain' => getenv('SMB_DOMAIN') ?: '',
    ],
    'session' => [
        'lifetime' => (int)(getenv('SESSION_LIFETIME') ?: 3600),
        'name'     => getenv('SESSION_NAME') ?: 'filebrowser_sess',
    ],
    'logging' => [
        'path'     => getenv('LOG_PATH') ?: '/var/www/log/filebrowser-ng.log',
        'level'    => getenv('LOG_LEVEL') ?: 'info',
        'max_days' => (int)(getenv('LOG_MAX_DAYS') ?: 30),
    ],
    'i18n' => [
        'default_language' => getenv('I18N_LANGUAGE') ?: 'en',
    ],
    'database' => [
        'path' => getenv('DB_PATH') ?: '/var/www/data/filebrowser.sqlite',
    ],
];
file_put_contents('${CONFIG_FILE}', json_encode(\$config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
echo 'Config written.' . PHP_EOL;
"

log_info "Config written."

chown -R nobody:nogroup /var/www/conf /var/www/data /var/www/log
log_info "Ownership fixed."

log_info "Starting supervisord..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

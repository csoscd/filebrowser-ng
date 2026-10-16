#!/bin/bash
# =============================================================================
# filebrowser-ng – entrypoint.sh
#
# 1. Generate /var/www/conf/config.json from environment variables.
# 2. Configure PHP upload limits and nginx body size from UPLOAD_CHUNK_SIZE.
# 3. Fix ownership of runtime directories.
# 4. Start supervisord (nginx + php-fpm).
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
#   UPLOAD_CHUNK_SIZE     10485760   (bytes, default 10 MB)
#
# OIDC (optional – leave empty to use classic SMB login):
#   OIDC_ENABLED          false
#   OIDC_CLIENT_ID        ""
#   OIDC_CLIENT_SECRET    ""
#   OIDC_DISCOVERY_URL    ""   (OpenID Configuration URL or issuer URL)
#   OIDC_REDIRECT_URI     ""   (must match the registered redirect URI at the provider)
#   OIDC_SMB_KEY_CLAIM    smb_key   (custom claim carrying the SMB encryption key)
#   OIDC_USERNAME_CLAIM   ""   (empty = auto: preferred_username → sub)
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

UPLOAD_CHUNK_SIZE="${UPLOAD_CHUNK_SIZE:-10485760}"

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
    'upload' => [
        'chunk_size' => (int)(getenv('UPLOAD_CHUNK_SIZE') ?: 10485760),
    ],
    'oidc' => [
        'enabled'        => filter_var(getenv('OIDC_ENABLED') ?: 'false', FILTER_VALIDATE_BOOLEAN),
        'client_id'      => getenv('OIDC_CLIENT_ID') ?: '',
        'client_secret'  => getenv('OIDC_CLIENT_SECRET') ?: '',
        'discovery_url'  => getenv('OIDC_DISCOVERY_URL') ?: '',
        'redirect_uri'   => getenv('OIDC_REDIRECT_URI') ?: '',
        'smb_key_claim'  => getenv('OIDC_SMB_KEY_CLAIM') ?: 'smb_key',
        'username_claim' => getenv('OIDC_USERNAME_CLAIM') ?: '',
        'verify_ssl'     => filter_var(getenv('OIDC_VERIFY_SSL') ?: 'true', FILTER_VALIDATE_BOOLEAN),
        'ca_bundle'      => getenv('OIDC_SSL_CA_BUNDLE') ?: '',
        'scopes'         => getenv('OIDC_SCOPES') ?: 'openid profile',
    ],
];
file_put_contents('${CONFIG_FILE}', json_encode(\$config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
echo 'Config written.' . PHP_EOL;
"

log_info "Config written."

# ── PHP upload limits (chunk size + 2 MB overhead for form fields) ────────────
CHUNK_MB=$(( (UPLOAD_CHUNK_SIZE / 1048576) + 2 ))
cat > /usr/local/etc/php/conf.d/99-filebrowser-upload.ini <<EOF
upload_max_filesize = ${CHUNK_MB}M
post_max_size = ${CHUNK_MB}M
EOF
log_info "PHP upload limits set to ${CHUNK_MB}M."

# ── nginx body size limit ─────────────────────────────────────────────────────
echo "client_max_body_size ${CHUNK_MB}m;" > /etc/nginx/conf.d/upload-body-limit.conf
log_info "nginx client_max_body_size set to ${CHUNK_MB}m."

chown -R nobody:nogroup /var/www/conf /var/www/data /var/www/log
log_info "Ownership fixed."

log_info "Starting supervisord..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

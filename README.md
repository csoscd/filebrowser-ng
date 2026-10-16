# filebrowser-ng

Web-based file manager for SMB/Samba shares. Users log in with their Samba credentials and can browse, download, upload, rename, delete files and create folders – directly in the browser. No separate user management required: Samba is the single source of truth for authentication.

[![PHP Tests](https://github.com/csoscd/filebrowser-ng/actions/workflows/php-tests.yml/badge.svg)](https://github.com/csoscd/filebrowser-ng/actions/workflows/php-tests.yml)
[![Security checks](https://github.com/csoscd/filebrowser-ng/actions/workflows/security.yml/badge.svg)](https://github.com/csoscd/filebrowser-ng/actions/workflows/security.yml)
[![Docker Hub](https://img.shields.io/docker/v/cs1711/filebrowser-ng?label=Docker%20Hub)](https://hub.docker.com/r/cs1711/filebrowser-ng)
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)

---

## Features

| Feature | Description |
|---------|-------------|
| **Authentication** | Login with Samba username + password |
| **Share discovery** | Automatic SMB share enumeration |
| **Directory navigation** | Breadcrumb navigation, click to open folders |
| **File download** | Download individual files |
| **File upload** | Drag & drop, multiple files at once, chunked streaming |
| **Rename** | Rename files and folders |
| **Delete** | Delete files and folders (with confirmation dialog) |
| **Move / Copy** | Move or copy files and folders across shares |
| **New folder** | Create a new directory in the current path |
| **Multi-server** | Add multiple SMB servers per user session; per-server credentials stored in session |
| **Custom shares** | Manually add hidden shares (persisted in SQLite) |
| **Disk usage** | Per-share disk space indicator in the sidebar |
| **Internationalisation** | English, German and French UI |

---

## Quick start

### Docker Compose

```yaml
services:
  filebrowser-ng:
    image: cs1711/filebrowser-ng:latest
    container_name: filebrowser-ng
    restart: unless-stopped
    ports:
      - "8080:8080"
    environment:
      SMB_SERVER: "192.168.1.100"
    volumes:
      - /etc/localtime:/etc/localtime:ro
      - ./conf:/var/www/conf
      - ./data:/var/www/data
      - ./log:/var/www/log
```

Open `http://localhost:8080` and log in with your Samba credentials.

### Environment variables

| Variable | Default | Required | Description |
|----------|---------|----------|-------------|
| `SMB_SERVER` | — | **yes** | Samba server hostname or IP |
| `SMB_DOMAIN` | `""` | no | Workgroup / domain |
| `SESSION_LIFETIME` | `3600` | no | Session duration in seconds |
| `SESSION_NAME` | `filebrowser_sess` | no | Session cookie name |
| `LOG_PATH` | `/var/www/log/filebrowser-ng.log` | no | Log file path |
| `LOG_LEVEL` | `info` | no | Monolog level (`debug`, `info`, `warning`, `error`) |
| `LOG_MAX_DAYS` | `30` | no | Log rotation in days |
| `I18N_LANGUAGE` | `en` | no | UI language (`en`, `de`, `fr`) |
| `DB_PATH` | `/var/www/data/filebrowser.sqlite` | no | SQLite database path |
| `UPLOAD_CHUNK_SIZE` | `10485760` | no | Upload chunk size in bytes (default 10 MB) |

#### OIDC (optional)

Leave all `OIDC_*` variables empty (or set `OIDC_ENABLED=false`) to keep the classic SMB login. Set `OIDC_ENABLED=true` to replace it with SSO.

| Variable | Default | Description |
|----------|---------|-------------|
| `OIDC_ENABLED` | `false` | Activate OIDC authentication |
| `OIDC_CLIENT_ID` | — | Client ID registered at the OIDC provider |
| `OIDC_CLIENT_SECRET` | — | Client secret |
| `OIDC_DISCOVERY_URL` | — | OpenID Configuration URL **or** issuer URL (`/.well-known/openid-configuration` is appended automatically) |
| `OIDC_REDIRECT_URI` | — | Callback URL – must match the redirect URI registered at the provider (e.g. `https://files.example.com/auth/oidc/callback`) |
| `OIDC_SMB_KEY_CLAIM` | `smb_key` | Custom claim in the ID Token carrying a per-user encryption key for stored SMB passwords (optional but recommended) |
| `OIDC_USERNAME_CLAIM` | `""` | Claim to use as the SMB username. Empty = auto (`preferred_username` → `sub`) |

##### OIDC login flow

```
1. User clicks "Sign in with SSO"
2. Browser is redirected to the OIDC provider
3. After successful authentication the provider redirects back to /auth/oidc/callback
4. The app verifies the ID Token, extracts the username (and optionally the smb_key claim)
5a. If a stored SMB password exists and can be decrypted → login complete
5b. Otherwise → SMB password form is shown
6. User enters the SMB password (optional: tick "Remember password")
7. Login complete
```

##### Setting up the smb_key claim

To enable the "Remember password" feature the OIDC provider must deliver a stable,
per-user random string as a custom claim. The claim name is configurable via
`OIDC_SMB_KEY_CLAIM` (default: `smb_key`).

**Authentik example** – create a *Property Mapping* (Scope type) for the application:
```python
return {
    "smb_key": user.attributes.get("smb_key", "")
}
```
Add a random 32-character hex string as `smb_key` to each user's attributes.

**Keycloak example** – add a *User Attribute Mapper* named `smb_key` to the client,
sourced from the user attribute `smb_key`.

If the claim is absent the "Remember password" checkbox is shown as disabled with an
explanatory note. Passwords will still work but must be re-entered on every login.

### Volumes

| Host path | Container path | Purpose |
|-----------|---------------|---------|
| `./conf` | `/var/www/conf` | Generated `config.json` (read/write) |
| `./data` | `/var/www/data` | SQLite database for custom shares and additional servers |
| `./log` | `/var/www/log` | Application log files |

> **Permissions**: no manual `chown` needed on the Docker host. The container entrypoint
> runs as `root` and sets ownership of all three directories to `nobody:nogroup`
> (UID/GID 65534) at startup. Since the directories are bind-mounted, this also
> updates the host-side ownership automatically.

---

## Architecture

```
Browser
  │
  ▼
┌─────────────────────────┐
│  filebrowser-ng          │  PHP 8.4 + Nginx + PHP-FPM
│  (Docker container)      │
│                          │
│  Router → Controller     │  Tailwind CSS + Alpine.js
│  Session (PHP)           │  No JavaScript build step
│  I18n (PHP arrays)       │
│  SQLite (custom shares)  │
└────────────┬────────────┘
             │ icewind/smb → smbclient binary
             ▼
     ┌──────────────┐
     │  Samba/SMB   │
     │  Server      │
     └──────────────┘
```

### Multi-server support (v0.3+)

Users can add additional SMB servers directly from the sidebar without leaving the file manager.
The default server is defined by `SMB_SERVER`. Extra servers are stored per-user in SQLite.

- **Same credentials**: the app tries the session credentials first; if the connection succeeds, the server is accessible immediately.
- **Different credentials**: if the session credentials fail, a per-server credential prompt appears inline in the sidebar. The entered credentials are stored only in the PHP session – never in the database or on disk.
- Removing a server also removes all its custom shares (cascade delete).

### Extensible storage layer

SMB is the first implementation of `StorageProviderInterface`. The architecture is designed for additional protocols from the start:

```
StorageProviderInterface
├── SmbProvider   ← v1
├── FtpProvider   ← future
└── SftpProvider  ← future
```

---

## Development

### Requirements

- PHP 8.4 with extensions: `pdo`, `pdo_sqlite`, `mbstring`
- Composer 2
- `smbclient` binary (for SMB integration tests against a real server)

### Setup

```bash
git clone https://github.com/csoscd/filebrowser-ng.git
cd filebrowser-ng
composer install
```

### Run tests

```bash
./vendor/bin/phpunit --testsuite unit,integration
```

### Local configuration (development)

Create `conf/config.json` (or copy from example):

```json
{
    "smb": {
        "server": "192.168.1.100",
        "domain": ""
    },
    "session": {
        "lifetime": 3600,
        "name": "filebrowser_sess"
    },
    "logging": {
        "path": "/var/www/log/filebrowser-ng.log",
        "level": "info",
        "max_days": 30
    },
    "i18n": {
        "default_language": "en"
    },
    "database": {
        "path": "/var/www/data/filebrowser.sqlite"
    }
}
```

---

## CI / CD

| Workflow | Trigger | Purpose |
|----------|---------|---------|
| `php-tests.yml` | Push + PR | PHPUnit unit + integration tests (PHP 8.4) |
| `security.yml` | Push + PR | Dependency audit, Semgrep SAST, Gitleaks secret scan |
| `docker-dev.yml` | Push (non-main) | Build `cs1711/filebrowser-ng:dev` |
| `docker-release.yml` | Release published | Multi-arch build → Docker Hub (semver + `latest`) |
| `sbom.yml` | Release published | CycloneDX JSON SBOM as release asset |
| `auto-patch-release.yml` | `workflow_dispatch` / `repository_dispatch` | Auto patch release on base image update |

**Required secrets:** `DOCKERHUB_USERNAME`, `DOCKERHUB_TOKEN`

---

## License

GNU General Public License v3.0 or later. See [LICENSE](LICENSE) for details.

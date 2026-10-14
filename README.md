# filebrowser-ng

Web-based file manager for SMB/Samba shares. Users log in with their Samba credentials and can browse, download, upload, rename, delete files and create folders – directly in the browser. No separate user management required: Samba is the single source of truth for authentication.

[![PHP Tests](https://github.com/csoscd/filebrowser-ng/actions/workflows/php-tests.yml/badge.svg)](https://github.com/csoscd/filebrowser-ng/actions/workflows/php-tests.yml)
[![Security checks](https://github.com/csoscd/filebrowser-ng/actions/workflows/security.yml/badge.svg)](https://github.com/csoscd/filebrowser-ng/actions/workflows/security.yml)
[![Docker Hub](https://img.shields.io/docker/v/cs1711/filebrowser-ng?label=Docker%20Hub)](https://hub.docker.com/r/cs1711/filebrowser-ng)
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)

---

## Features (v1)

| Feature | Description |
|---------|-------------|
| **Authentication** | Login with Samba username + password |
| **Share discovery** | Automatic SMB share enumeration |
| **Directory navigation** | Breadcrumb navigation, click to open folders |
| **File download** | Download individual files |
| **File upload** | Drag & drop, multiple files at once |
| **Rename** | Rename files and folders |
| **Delete** | Delete files and folders (with confirmation dialog) |
| **New folder** | Create a new directory in the current path |
| **Custom shares** | Manually add hidden shares (persisted in SQLite) |
| **Internationalisation** | English and German UI |

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
| `I18N_LANGUAGE` | `en` | no | UI language (`en`, `de`) |

### Volumes

| Host path | Container path | Purpose |
|-----------|---------------|---------|
| `./conf` | `/var/www/conf` | Generated `config.json` (read/write) |
| `./data` | `/var/www/data` | SQLite database for custom shares |
| `./log` | `/var/www/log` | Application log files |

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

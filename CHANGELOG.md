# Changelog

All notable changes to filebrowser-ng are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

---

## [0.1.0] – 2026-10-14

### Added

- **HTTP layer**: `Router`, `Request`, `Response` (front-controller pattern, single entry point `public/index.php`)
- **Storage abstraction**: `StorageProviderInterface` for extensible protocol support
- **SMB provider**: `SmbProvider` using `icewind/smb` v3.8+; supports list shares, list directory, download, upload, rename, delete (recursive), create directory
- **Authentication**: Login/logout with Samba credentials; SMB connect-test as authentication verification; session management with configurable lifetime and idle expiry
- **File browser UI**: Tailwind CSS + Alpine.js; share sidebar, breadcrumb navigation, file table with size and modification date
- **File actions**: Download, rename, delete (with confirmation dialog), create new folder
- **Upload**: Drag & drop and button-triggered multi-file upload with progress indicator
- **SQLite repository**: `ServerRepository` for persisting user-defined custom shares
- **Internationalisation**: `Translator` with `lang/en.php` and `lang/de.php`
- **Logging**: Monolog `RotatingFileHandler`, 30-day rotation, configurable level and path
- **Configuration**: Noodlehaus/Config (JSON), generated from environment variables at container start
- **Docker**: Multi-stage `Dockerfile` (Composer + `cs1711/cs_php-nginx-fpm:latest-debian`); `entrypoint.sh` generates `config.json` from env vars; `smbclient` installed for SMB backend
- **docker-compose.yml** with volumes for `conf/`, `data/`, `log/`
- **GitHub Actions**:
  - `php-tests.yml`: PHPUnit unit + integration tests on PHP 8.4
  - `security.yml`: Composer audit, Semgrep SAST (p/php + p/owasp-top-ten), Gitleaks secret scan
  - `docker-dev.yml`: Build and push `cs1711/filebrowser-ng:dev` on non-main branch pushes
  - `docker-release.yml`: Multi-arch (amd64/arm64) build + push on GitHub release
  - `sbom.yml`: CycloneDX JSON SBOM attached to release
  - `auto-patch-release.yml`: Auto patch release on base image update
- **PHPUnit tests**: `SessionManagerTest`, `TranslatorTest` (unit); `ServerRepositoryTest` (integration)
- **.semgrepignore**, **.gitleaks.toml** for CI false-positive suppression

[Unreleased]: https://github.com/csoscd/filebrowser-ng/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/csoscd/filebrowser-ng/releases/tag/v0.1.0

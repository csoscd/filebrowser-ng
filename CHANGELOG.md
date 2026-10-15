# Changelog

All notable changes to filebrowser-ng are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

---

## [0.3.0] – 2026-10-15

### Added

- **Multi-server support**: users can add additional SMB servers directly from the sidebar without leaving the file manager
  - "+" button at the bottom of the sidebar opens the Add Server dialog (host, optional display name, optional domain)
  - The app tests the connection using the current session credentials first; extra credentials are only requested when the session credentials fail
  - Per-server inline credential prompt in the sidebar (username + password stored in PHP session only, not in the database)
  - Removing a server removes all its saved custom shares automatically (cascade delete)
- **`ServerRepository`** extended:
  - New `user_servers` table (username, host, display_name, domain; UNIQUE username+host)
  - `user_shares` now has a `server_id` FK → `user_servers(id) ON DELETE CASCADE`
  - New methods: `addServer`, `findServersByUser`, `findServer`, `findServerByHost`, `deleteServer`, `findSharesByServer`
  - Legacy alias methods preserved for backwards compatibility
  - `PRAGMA foreign_keys = ON` enabled so cascade deletes work correctly
- **`ServerController`**: new controller for server management API
  - `GET /api/servers`: list user's servers
  - `POST /api/servers`: add server (connection test, store extra credentials in session)
  - `DELETE /api/servers/{id}`: remove server (cascade, clear session credentials)
  - `POST /api/servers/{id}/credentials`: store per-server credentials in session
- **`SessionManager`** extended:
  - `setExtraCredentials(host, username, password)`: store per-host credentials in session
  - `getExtraCredentials(host)`: retrieve per-host credentials
  - `hasExtraCredentials(host)`: check whether credentials exist for a host
  - `clearExtraCredentials(host)`: remove per-host credentials
- **`/api/shares` response format** changed from `{auto, custom}` to `{servers: [...]}` – each server entry includes `id`, `host`, `display_name`, `shares`, `custom`, `is_default` and `needs_credentials`
- **`server` parameter** propagated to all API calls:
  - `/api/files`, `/browser/download`, `/browser/download-zip`
  - `/upload/chunk`, `/file/rename`, `/file/delete`, `/file/mkdir`, `/file/move`, `/file/copy`, `/api/sort-pref`
- **Sidebar redesign**: shares grouped by server with collapsible sections; server header shows default label and remove button on hover

### Changed

- `BrowserController`: `shares()` returns new grouped format; `connectSmbForHost()` picks session credentials for the default server or extra credentials for additional servers
- `BrowserController`: API endpoints (`shares`, `listFiles`, `download`, `downloadZip`, `saveSortPref`) now return JSON 401 on unauthenticated access via new `requireAuthApi()` method; the HTML index route continues to redirect to `/`
- `FileActionController`: `copy()` and `move()` now handle cross-server operations – `dest_server` is read from the request; if it differs from the source server, the file is downloaded to a local temp file via the source connection and uploaded via a fresh destination connection; move additionally deletes the source after a successful upload
- `FileActionController`, `UploadController`: all actions accept optional `server` parameter from request body
- `SessionManager::destroy()`: guarded with `session_status()` check to prevent PHP warnings when no session is active
- Router: `delete()` method added

### Tests

- **Test infrastructure**: `Response::$testMode` flag + `ResponseSentException` (thrown instead of `exit` in all Response methods); `tests/Helpers/TestRequest` (Request subclass bypassing superglobals)
- `ServerRepositoryTest` rewritten to cover new server CRUD methods, cascade delete, `findSharesByServer`, and legacy alias methods
- `SessionManagerTest` extended with tests for `setExtraCredentials`, `getExtraCredentials`, `hasExtraCredentials`, `clearExtraCredentials`
- `ServerControllerTest` (13 tests): list, add (missing host, duplicate, connection failed, session creds, extra creds), remove, storeCredentials
- `BrowserControllerSharesTest` (7 tests): response shape, default server entry, connection failure, user-added server, needs-credentials flag, extra-credentials from session, auth required
- `ServerParameterTest` (5 tests): `server` parameter forwarding in `UploadController` and `FileActionController` for default server, extra server without credentials, extra server with stored credentials

---

## [0.2.0] – 2026-10-15

### Added

- **Upload manager panel**: floating panel replaces the single progress bar; shows all queued uploads simultaneously with per-file progress, speed (Mbit/s, EMA-smoothed), status and cancel button
- **Chunked upload via native SMB streaming**: chunks are streamed directly to the SMB share using `IShare::write()` / `IShare::append()` – no local temp file or assembly step required
- **`.part` suffix during upload**: files are written as `<name>.part` on the share and renamed to their final name only after all chunks arrive; prevents other users from seeing incomplete files
- **`beforeunload` warning**: browser shows a "Leave page?" confirmation dialog when uploads are still running
- **Disk usage per share**: free/total space shown as progress bar in the sidebar, colour-coded at 75 % and 90 % usage
- **Native SMB backend** (`php-libsmbclient` PECL extension): added to Docker image; enables `append()` streaming and `smbclient_statvfs()` for disk info
- **Disk info refresh after upload**: sidebar disk usage updates automatically via AJAX after a successful upload batch
- **Column sorting**: click column headers (name, size, modified) to sort; sort preference persisted per folder in SQLite (`user_preferences` table)
- **Double-click to open**: opens files in a new browser tab
- **Bulk operations**: checkbox multi-select; bulk delete, bulk move/copy
- **Move / Copy dialog**: folder picker with breadcrumb navigation, cross-share support; separate Move and Copy actions
- **Mobile-responsive sidebar**: drawer toggle on small screens
- **French translation** (`lang/fr.php`); language switcher in the navigation bar
- **All JS-facing strings externalised** to lang files (`js.error.*`, `upload.status.*`, dialog labels)
- **`UserPreferenceRepository`**: SQLite-backed key/value store for per-user UI preferences (sort column/direction)
- **`FileActionController`**: dedicated controller for move and copy operations

### Fixed

- Disk info calculation corrected: `libsmbclient` maps `SectorsPerAllocationUnit` to `frsize` (not bytes); correct formula is `blocks × frsize × bsize`
- Delete confirmation dialog now shows the filename correctly
- Folder picker now starts at shares level and supports cross-share move/copy

### Changed

- Chunked upload rewritten twice: first to a single growing temp file (no per-chunk assembly), then to direct SMB streaming
- `StorageProviderInterface`: added `copy()` and `openFileStream()` methods
- Docker image: added `libsmbclient-dev` build dependency and `smbclient` PECL extension

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
- **`DB_PATH` environment variable** for configuring the SQLite database path (default: `/var/www/data/filebrowser.sqlite`)
- **PHPUnit tests**: `SessionManagerTest`, `TranslatorTest` (unit); `ServerRepositoryTest` (integration)
- **.semgrepignore**, **.gitleaks.toml** for CI false-positive suppression

[Unreleased]: https://github.com/csoscd/filebrowser-ng/compare/v0.3.0...HEAD
[0.3.0]: https://github.com/csoscd/filebrowser-ng/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/csoscd/filebrowser-ng/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/csoscd/filebrowser-ng/releases/tag/v0.1.0

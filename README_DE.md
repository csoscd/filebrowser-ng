# filebrowser-ng

Web-basierter Dateimanager für SMB/Samba-Shares. Benutzer melden sich mit ihren Samba-Zugangsdaten an und können Dateien direkt im Browser verwalten: navigieren, herunterladen, hochladen, umbenennen, löschen und Ordner anlegen. Kein separates Benutzerverwaltungssystem erforderlich – Samba ist die einzige Wahrheitsquelle für die Authentifizierung.

[![PHP Tests](https://github.com/csoscd/filebrowser-ng/actions/workflows/php-tests.yml/badge.svg)](https://github.com/csoscd/filebrowser-ng/actions/workflows/php-tests.yml)
[![Security checks](https://github.com/csoscd/filebrowser-ng/actions/workflows/security.yml/badge.svg)](https://github.com/csoscd/filebrowser-ng/actions/workflows/security.yml)
[![Docker Hub](https://img.shields.io/docker/v/cs1711/filebrowser-ng?label=Docker%20Hub)](https://hub.docker.com/r/cs1711/filebrowser-ng)
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)

---

## Funktionen

| Funktion | Beschreibung |
|----------|-------------|
| **Authentifizierung** | Anmeldung mit Samba-Benutzername und Passwort |
| **Share-Erkennung** | Automatische SMB-Share-Enumeration |
| **Verzeichnis-Navigation** | Breadcrumb-Navigation, Ordner per Klick öffnen |
| **Datei-Download** | Einzelne Dateien herunterladen |
| **Datei-Upload** | Drag & Drop, mehrere Dateien gleichzeitig, Chunked Streaming |
| **Umbenennen** | Dateien und Ordner umbenennen |
| **Löschen** | Dateien und Ordner löschen (mit Bestätigungsdialog) |
| **Verschieben / Kopieren** | Dateien und Ordner auch zwischen Shares verschieben oder kopieren |
| **Neuer Ordner** | Neues Verzeichnis im aktuellen Pfad anlegen |
| **Multi-Server** | Mehrere SMB-Server pro Benutzersitzung hinzufügen; Zugangsdaten pro Server in der Session gespeichert |
| **Eigene Shares** | Versteckte Shares manuell hinzufügen (in SQLite gespeichert) |
| **Speicherplatz** | Speicherplatzanzeige pro Share in der Seitenleiste |
| **Mehrsprachigkeit** | Benutzeroberfläche auf Englisch, Deutsch und Französisch |

---

## Schnellstart

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

`http://localhost:8080` aufrufen und mit den Samba-Zugangsdaten anmelden.

### Umgebungsvariablen

| Variable | Standard | Pflicht | Beschreibung |
|----------|----------|---------|-------------|
| `SMB_SERVER` | — | **ja** | Hostname oder IP des Samba-Servers |
| `SMB_DOMAIN` | `""` | nein | Workgroup / Domäne |
| `SESSION_LIFETIME` | `3600` | nein | Session-Dauer in Sekunden |
| `SESSION_NAME` | `filebrowser_sess` | nein | Name des Session-Cookies |
| `LOG_PATH` | `/var/www/log/filebrowser-ng.log` | nein | Pfad zur Log-Datei |
| `LOG_LEVEL` | `info` | nein | Monolog-Level (`debug`, `info`, `warning`, `error`) |
| `LOG_MAX_DAYS` | `30` | nein | Log-Rotation in Tagen |
| `I18N_LANGUAGE` | `en` | nein | Sprache der Benutzeroberfläche (`en`, `de`, `fr`) |
| `DB_PATH` | `/var/www/data/filebrowser.sqlite` | nein | Pfad zur SQLite-Datenbank |
| `UPLOAD_CHUNK_SIZE` | `10485760` | nein | Upload-Chunk-Größe in Bytes (Standard: 10 MB) |

#### OIDC (optional)

Alle `OIDC_*`-Variablen leer lassen (oder `OIDC_ENABLED=false` setzen), um die klassische SMB-Anmeldung zu verwenden. Mit `OIDC_ENABLED=true` wird SSO aktiviert.

| Variable | Standard | Beschreibung |
|----------|----------|-------------|
| `OIDC_ENABLED` | `false` | OIDC-Authentifizierung aktivieren |
| `OIDC_CLIENT_ID` | — | Client-ID beim OIDC-Provider |
| `OIDC_CLIENT_SECRET` | — | Client-Secret |
| `OIDC_DISCOVERY_URL` | — | OpenID Configuration URL **oder** Issuer-URL (`/.well-known/openid-configuration` wird automatisch angehängt) |
| `OIDC_REDIRECT_URI` | — | Callback-URL – muss exakt mit der beim Provider registrierten Redirect-URI übereinstimmen (z. B. `https://dateien.example.com/auth/oidc/callback`) |
| `OIDC_SMB_KEY_CLAIM` | `smb_key` | Custom-Claim im ID-Token mit einem benutzerspezifischen Verschlüsselungsschlüssel für gespeicherte SMB-Passwörter (optional, aber empfohlen) |
| `OIDC_USERNAME_CLAIM` | `""` | Claim für den SMB-Benutzernamen. Leer = automatisch (`preferred_username` → `sub`) |
| `OIDC_VERIFY_SSL` | `true` | Auf `false` setzen, um SSL-Verifikation vollständig zu deaktivieren (nicht empfohlen) |
| `OIDC_SSL_CA_BUNDLE` | `""` | Pfad zu einem eigenen CA-Bundle (PEM) im Container. Hat Vorrang vor `OIDC_VERIFY_SSL`. Verwenden bei privaten oder selbstsignierten Zertifikaten |
| `OIDC_SCOPES` | `openid profile` | Leerzeichen-getrennte Liste der anzufragenden OIDC-Scopes. Den Namen des eigenen Scope-Mappings hier ergänzen, damit extra Claims (z. B. `smb_key`) im ID-Token enthalten sind |

##### OIDC-Anmeldeablauf

```
1. Benutzer klickt „Mit SSO anmelden"
2. Browser wird zum OIDC-Provider weitergeleitet
3. Nach erfolgreicher Authentifizierung leitet der Provider zurück zu /auth/oidc/callback
4. Die App prüft das ID-Token, extrahiert Benutzername (und optional den smb_key-Claim)
5a. Falls ein gespeichertes SMB-Passwort vorhanden und entschlüsselbar ist → Anmeldung abgeschlossen
5b. Andernfalls → SMB-Passwort-Formular wird angezeigt
6. Benutzer gibt das SMB-Passwort ein (optional: „Passwort merken" ankreuzen)
7. Anmeldung abgeschlossen
```

##### smb_key-Claim einrichten

Für die Funktion „Passwort merken" muss der OIDC-Provider einen stabilen, benutzerspezifischen Zufalls-String als Custom-Claim liefern. Der Claim-Name ist über `OIDC_SMB_KEY_CLAIM` konfigurierbar (Standard: `smb_key`).

**Authentik-Beispiel** – ein *Property Mapping* (Typ: Scope) für die Anwendung anlegen:
```python
return {
    "smb_key": user.attributes.get("smb_key", "")
}
```
Jeden Benutzer mit einem eindeutigen, zufälligen 32-stelligen Hex-String als `smb_key`-Attribut versehen.

**Keycloak-Beispiel** – einen *User Attribute Mapper* namens `smb_key` im Client anlegen, der das Benutzerattribut `smb_key` ausliest.

Ist der Claim nicht vorhanden, wird die Checkbox „Passwort merken" deaktiviert mit einem erläuternden Hinweis angezeigt. Das SMB-Passwort funktioniert weiterhin, muss aber bei jeder Anmeldung neu eingegeben werden.

---

## OIDC-Einrichtung (Authentik)

Diese Schritt-für-Schritt-Anleitung beschreibt die Anbindung von filebrowser-ng an
[Authentik](https://goauthentik.io/). Die gleichen Prinzipien gelten für andere
OIDC-Provider; UI und Terminologie weichen ab.

### 1 – OAuth2/OIDC-Provider in Authentik anlegen

1. **Admin Interface → Applications → Providers → Create** öffnen
2. **OAuth2/OpenID Provider** wählen
3. Felder ausfüllen:
   - **Name**: `filebrowser-ng`
   - **Authorization flow**: Standard-Autorisierungsflow (z. B. `default-provider-authorization-explicit-consent`)
   - **Client type**: `Confidential`
   - **Client ID**: Wert kopieren → wird als `OIDC_CLIENT_ID` benötigt
   - **Client Secret**: Wert kopieren → wird als `OIDC_CLIENT_SECRET` benötigt
   - **Redirect URIs**: `https://dateien.example.com/auth/oidc/callback`
     (eigenen Hostnamen einsetzen; muss exakt mit `OIDC_REDIRECT_URI` übereinstimmen)
   - **Scopes**: `openid`, `profile` (Standardwerte sind ausreichend)
4. Speichern.
5. Die **OpenID Configuration URL** auf der Provider-Detailseite notieren – etwa
   `https://login.example.com/application/o/filebrowser-ng/.well-known/openid-configuration`.
   Diese als `OIDC_DISCOVERY_URL` verwenden.

### 2 – Anwendung in Authentik anlegen

1. **Applications → Applications → Create**
2. Felder ausfüllen:
   - **Name**: `filebrowser-ng`
   - **Slug**: `filebrowser-ng`
   - **Provider**: den zuvor erstellten Provider auswählen
3. Speichern.

### 3 – smb_key-Attribut für Benutzer setzen (optional, aber empfohlen)

Dieser Schritt aktiviert die Funktion „Passwort merken". Überspringen, wenn Benutzer das SMB-Passwort bei jeder Anmeldung neu eingeben sollen.

Für jeden Benutzer, der das Passwort speichern können soll:

1. **Directory → Users → [Benutzer auswählen] → Edit**
2. Im Feld **Attributes** (JSON) ergänzen:
   ```json
   {
     "smb_key": "a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4"
   }
   ```
   Einen eindeutigen, zufälligen Hex-String pro Benutzer erzeugen, z. B.:
   ```bash
   openssl rand -hex 16
   ```
3. Speichern.

> **Wichtig**: Jeder Benutzer benötigt einen eigenen, einzigartigen `smb_key`. Denselben Wert niemals für mehrere Benutzer verwenden.

### 4 – Property Mapping (Scope) in Authentik anlegen

Das `smb_key`-Attribut muss als Custom-Claim im ID-Token enthalten sein.

1. **Customisation → Property Mappings → Create**
2. **Scope Mapping** wählen
3. Felder ausfüllen:
   - **Name**: `filebrowser-ng smb_key`
   - **Scope name**: `smb_key` (muss mit dem in Schritt 5 bei `OIDC_SCOPES` eingetragenen Wert übereinstimmen)
   - **Expression**:
     ```python
     return {
         "smb_key": user.attributes.get("smb_key", "")
     }
     ```
4. Speichern.
5. Zurück zum **Provider → Edit**: das Mapping unter **Advanced protocol settings → Scopes** hinzufügen.

> **Wichtig**: Das Hinzufügen des Mappings zum Provider *erlaubt* den Scope nur. Der Client muss ihn zusätzlich explizit *anfordern* – siehe `OIDC_SCOPES` in Schritt 5.

### 5 – filebrowser-ng konfigurieren

In der `docker-compose.yml` ergänzen:

```yaml
environment:
  SMB_SERVER: "192.168.1.100"
  OIDC_ENABLED: "true"
  OIDC_CLIENT_ID: "client-id-hier-eintragen"
  OIDC_CLIENT_SECRET: "client-secret-hier-eintragen"
  OIDC_DISCOVERY_URL: "https://login.example.com/application/o/filebrowser-ng/.well-known/openid-configuration"
  OIDC_REDIRECT_URI: "https://dateien.example.com/auth/oidc/callback"
  OIDC_SMB_KEY_CLAIM: "smb_key"
  # Den eigenen Scope anfordern, damit Authentik den smb_key-Claim im ID-Token liefert.
  # Der Scope-Name muss mit dem "Scope name"-Feld des Property Mappings (Schritt 4) übereinstimmen.
  OIDC_SCOPES: "openid profile smb_key"
```

**Privates CA-Zertifikat (selbstsigniert):** Falls der OIDC-Provider ein Zertifikat einer privaten CA verwendet, das Root-Zertifikat in den Container mounten und den Pfad angeben:

```yaml
volumes:
  - /pfad/zum/root_ca.crt:/var/www/cert/root_ca.crt:ro
environment:
  OIDC_SSL_CA_BUNDLE: "/var/www/cert/root_ca.crt"
```

### 6 – Anmeldung testen

1. Die filebrowser-URL im Browser aufrufen.
2. Es sollte die Schaltfläche „Mit SSO anmelden" erscheinen (daneben weiterhin das klassische SMB-Formular).
3. Klick auf die Schaltfläche leitet zu Authentik weiter.
4. Nach erfolgreicher Authentifizierung bei Authentik erscheint das SMB-Passwort-Formular.
5. Samba-Passwort eingeben. Wenn das `smb_key`-Attribut gesetzt ist, ist die Checkbox „Passwort merken" aktiv.
6. Checkbox ankreuzen und absenden – ab jetzt wird das SMB-Passwort-Formular bei weiteren Anmeldungen übersprungen.

### Fehlersuche

| Symptom | Mögliche Ursache |
|---------|-----------------|
| „Identity provider unavailable" | `OIDC_DISCOVERY_URL` nicht erreichbar, oder SSL-Zertifikat nicht vertrauenswürdig. Root-CA in den Container mounten und `OIDC_SSL_CA_BUNDLE=/pfad/zur/root_ca.crt` setzen |
| „Login was cancelled or denied" | Authentik hat den Zugriff verweigert (Application-Policy und Benutzergruppe prüfen) |
| „Login response was invalid" | Redirect-URI stimmt nicht überein, oder `OIDC_CLIENT_SECRET` ist falsch |
| Checkbox „Passwort merken" deaktiviert | `smb_key`-Attribut oder Property Mapping nicht eingerichtet (Schritte 3–4), oder `OIDC_SCOPES` enthält den Scope-Namen nicht (Schritt 5) |
| SMB-Passwort nach dem Speichern abgelehnt | SMB-Passwort auf dem Server geändert; erneut eingeben, um den gespeicherten Wert zu aktualisieren |

### Volumes

| Host-Pfad | Container-Pfad | Zweck |
|-----------|---------------|-------|
| `./conf` | `/var/www/conf` | Generierte `config.json` (Lesen/Schreiben) |
| `./data` | `/var/www/data` | SQLite-Datenbank für eigene Shares und zusätzliche Server |
| `./log` | `/var/www/log` | Anwendungs-Logdateien |

> **Berechtigungen**: Kein manuelles `chown` auf dem Docker-Host erforderlich. Das Container-Entrypoint läuft als `root` und setzt die Eigentümerschaft aller drei Verzeichnisse beim Start auf `nobody:nogroup` (UID/GID 65534). Da es sich um Bind-Mounts handelt, wird die Eigentümerschaft auch auf dem Host automatisch angepasst.

---

## Architektur

```
Browser
  │
  ▼
┌─────────────────────────┐
│  filebrowser-ng          │  PHP 8.4 + Nginx + PHP-FPM
│  (Docker-Container)      │
│                          │
│  Router → Controller     │  Tailwind CSS + Alpine.js
│  Session (PHP)           │  Kein JavaScript-Build-Schritt
│  I18n (PHP-Arrays)       │
│  SQLite (eigene Shares)  │
└────────────┬────────────┘
             │ icewind/smb → smbclient-Binary
             ▼
     ┌──────────────┐
     │  Samba/SMB   │
     │  Server      │
     └──────────────┘
```

### Multi-Server-Unterstützung (ab v0.3)

Benutzer können weitere SMB-Server direkt aus der Seitenleiste hinzufügen, ohne den Dateimanager zu verlassen. Der Standard-Server wird über `SMB_SERVER` definiert. Zusätzliche Server werden benutzerspezifisch in SQLite gespeichert.

- **Gleiche Zugangsdaten**: Die App prüft zunächst die Session-Zugangsdaten. Bei Erfolg ist der Server sofort erreichbar.
- **Andere Zugangsdaten**: Schlägt die Verbindung fehl, erscheint inline in der Seitenleiste ein Eingabefeld für benutzerspezifische Zugangsdaten. Diese werden ausschließlich in der PHP-Session gespeichert – niemals in der Datenbank oder auf dem Datenträger.
- Das Entfernen eines Servers löscht automatisch alle zugehörigen eigenen Shares (Cascade Delete).

### Erweiterbare Speicherschicht

SMB ist die erste Implementierung von `StorageProviderInterface`. Die Architektur ist von Anfang an für weitere Protokolle ausgelegt:

```
StorageProviderInterface
├── SmbProvider   ← v1
├── FtpProvider   ← zukünftig
└── SftpProvider  ← zukünftig
```

---

## Entwicklung

### Voraussetzungen

- PHP 8.4 mit Extensions: `pdo`, `pdo_sqlite`, `mbstring`
- Composer 2
- `smbclient`-Binary (für SMB-Integrationstests gegen einen echten Server)

### Setup

```bash
git clone https://github.com/csoscd/filebrowser-ng.git
cd filebrowser-ng
composer install
```

### Tests ausführen

```bash
./vendor/bin/phpunit --testsuite unit,integration
```

### Lokale Konfiguration (Entwicklung)

`conf/config.json` anlegen (oder aus Beispiel kopieren):

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
        "default_language": "de"
    },
    "database": {
        "path": "/var/www/data/filebrowser.sqlite"
    }
}
```

---

## CI / CD

| Workflow | Auslöser | Zweck |
|----------|----------|-------|
| `php-tests.yml` | Push + PR | PHPUnit Unit- und Integrationstests (PHP 8.4) |
| `security.yml` | Push + PR | Dependency-Audit, Semgrep SAST, Gitleaks Secret-Scan |
| `docker-dev.yml` | Push (nicht main) | Build `cs1711/filebrowser-ng:dev` |
| `docker-release.yml` | Release veröffentlicht | Multi-Arch-Build → Docker Hub (Semver + `latest`) |
| `sbom.yml` | Release veröffentlicht | CycloneDX JSON SBOM als Release-Asset |
| `auto-patch-release.yml` | `workflow_dispatch` / `repository_dispatch` | Automatisches Patch-Release bei Base-Image-Update |

**Erforderliche Secrets:** `DOCKERHUB_USERNAME`, `DOCKERHUB_TOKEN`

---

## Lizenz

GNU General Public License v3.0 oder später. Details in [LICENSE](LICENSE).

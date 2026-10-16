<?php

declare(strict_types=1);

return [
    'app.name'                    => 'filebrowser-ng',

    // Login
    'login.title'                 => 'Anmelden',
    'login.username'              => 'Benutzername',
    'login.password'              => 'Passwort',
    'login.submit'                => 'Anmelden',
    'login.error.credentials_required' => 'Bitte Benutzername und Passwort eingeben.',
    'login.error.invalid_credentials'  => 'Ungültige Anmeldedaten. Bitte erneut versuchen.',
    'login.error.oidc_unavailable'     => 'Der Identitätsanbieter ist derzeit nicht erreichbar. Bitte später erneut versuchen.',
    'login.error.oidc_denied'          => 'Die Anmeldung wurde abgebrochen oder vom Identitätsanbieter abgelehnt.',
    'login.error.oidc_invalid'         => 'Die Anmeldeantwort war ungültig. Bitte erneut versuchen.',

    // OIDC-Anmelde-Schaltfläche
    'login.oidc.submit'                => 'Mit SSO anmelden',
    'login.or'                         => 'oder',

    // SMB-Zugangsdaten-Formular (nach OIDC-Anmeldung)
    'login.smb.subtitle'               => 'Dateiserver-Passwort eingeben',
    'login.smb.password'               => 'Dateiserver-Passwort (SMB)',
    'login.smb.save_credentials'       => 'Passwort speichern (verschlüsselt)',
    'login.smb.save_disabled'          => 'Passwort speichern (kein Schlüssel – smb_key-Claim konfigurieren)',
    'login.smb.submit'                 => 'Verbinden',
    'login.smb.error.password_required' => 'Bitte Dateiserver-Passwort eingeben.',
    'login.smb.error.invalid_credentials' => 'Dateiserver-Passwort falsch. Bitte erneut versuchen.',

    // Browser
    'browser.shares'              => 'Freigaben',
    'browser.no_share'            => 'Freigabe aus der Seitenleiste auswählen',
    'browser.new_folder'          => 'Neuer Ordner',
    'browser.refresh'             => 'Aktualisieren',
    'browser.upload'              => 'Hochladen',
    'browser.download'            => 'Herunterladen',
    'browser.download_zip'        => 'ZIP herunterladen',
    'browser.open_hint'           => 'Doppelklick zum Öffnen',
    'browser.selected'            => 'ausgewählt',
    'browser.deselect_all'        => 'Auswahl aufheben',
    'browser.rename'              => 'Umbenennen',
    'browser.move_copy'           => 'Verschieben / Kopieren',
    'browser.delete'              => 'Löschen',
    'browser.loading'             => 'Wird geladen…',
    'browser.empty'               => 'Dieser Ordner ist leer.',

    // Share-Speicherplatz
    'share.disk.free_of'          => 'frei von',

    // File table
    'file.name'                   => 'Name',
    'file.size'                   => 'Größe',
    'file.modified'               => 'Geändert',
    'file.actions'                => 'Aktionen',

    // Upload
    'upload.drop'                 => 'Dateien hier ablegen oder klicken zum Hochladen',
    'upload.uploading'            => 'Wird hochgeladen…',
    'upload.success'              => ':count Datei(en) hochgeladen.',
    'upload.failed'               => 'Fehlgeschlagen: :files',
    'upload.cancel'               => 'Abbrechen',
    'upload.cancelled'            => 'Upload abgebrochen.',
    'upload.finalizing'           => 'Wird gespeichert…',
    'upload.panel.title'          => 'Uploads',
    'upload.panel.clear'          => 'Erledigte löschen',
    'upload.status.pending'       => 'Wartend…',
    'upload.status.done'          => 'Fertig',
    'upload.status.failed'        => 'Fehlgeschlagen',
    'upload.status.cancelled'     => 'Abgebrochen',

    // Dialogs
    'dialog.movecopy.title'        => 'Verschieben / Kopieren',
    'dialog.movecopy.label'        => 'Zielordner',
    'dialog.movecopy.hint'         => 'Der Dateiname bleibt unverändert.',
    'dialog.movecopy.hint_dir'     => 'Ordner können nur verschoben, nicht kopiert werden.',
    'dialog.movecopy.move'         => 'Verschieben',
    'dialog.movecopy.copy'         => 'Kopieren',
    'dialog.movecopy.cancel'       => 'Abbrechen',
    'dialog.rename.title'         => 'Umbenennen',
    'dialog.rename.label'         => 'Neuer Name',
    'dialog.rename.confirm'       => 'Umbenennen',
    'dialog.rename.cancel'        => 'Abbrechen',
    'dialog.delete.title'         => 'Löschen',
    'dialog.delete.message'       => '":name" löschen? Dies kann nicht rückgängig gemacht werden.',
    'dialog.delete.message_multi' => ':count Elemente löschen? Dies kann nicht rückgängig gemacht werden.',
    'dialog.delete.confirm'       => 'Löschen',
    'dialog.delete.cancel'        => 'Abbrechen',
    'dialog.mkdir.title'          => 'Neuer Ordner',
    'dialog.mkdir.label'          => 'Ordnername',
    'dialog.mkdir.confirm'        => 'Erstellen',
    'dialog.mkdir.cancel'         => 'Abbrechen',

    // Nav
    'nav.logout'                  => 'Abmelden',
    'nav.logged_in_as'            => 'Angemeldet als :user',

    // Ordner-Picker-Dialog
    'dialog.picker.title'     => 'Verschieben / Kopieren',
    'dialog.picker.select_hint' => 'Zielordner navigieren, dann Aktion wählen.',
    'dialog.picker.current'   => 'Ziel:',
    'dialog.picker.empty'     => 'Keine Unterordner',
    'dialog.picker.move_here' => 'Hierhin verschieben',
    'dialog.picker.copy_here' => 'Hierhin kopieren',
    'dialog.picker.cancel'    => 'Abbrechen',
    'dialog.picker.root'      => 'Wurzel',
    'dialog.picker.shares'    => 'Freigaben',
    'dialog.picker.executing' => 'Wird ausgeführt…',

    // Server-Verwaltung
    'server.add'                  => 'Server hinzufügen',
    'server.add_title'            => 'SMB-Server hinzufügen',
    'server.host'                 => 'Host / IP',
    'server.display_name'         => 'Anzeigename (optional)',
    'server.domain'               => 'Domain / Workgroup (optional)',
    'server.remove'               => 'Server entfernen',
    'server.remove_confirm'       => 'Server „:name" und alle gespeicherten Freigaben entfernen?',
    'server.credentials_required' => 'Anmeldedaten erforderlich',
    'server.enter_credentials'    => 'Anmeldedaten für :host eingeben',
    'server.connecting'           => 'Verbinde…',
    'server.connect_failed'       => 'Verbindung fehlgeschlagen. Anmeldedaten prüfen.',
    'server.already_added'        => 'Dieser Server wurde bereits hinzugefügt.',
    'server.default_label'        => '(Standard)',

    // JS error messages
    'js.error.load_shares'        => 'Freigaben konnten nicht geladen werden',
    'js.error.add_server'         => 'Server konnte nicht hinzugefügt werden',
    'js.error.credentials'        => 'Anmeldedaten konnten nicht gespeichert werden',
    'js.error.load_files'         => 'Dateien konnten nicht geladen werden',
    'js.error.chunk_upload'       => 'Upload-Fragment fehlgeschlagen',
    'js.error.download_zip'       => 'ZIP-Download fehlgeschlagen',
    'js.error.rename'             => 'Umbenennen fehlgeschlagen',
    'js.error.move'               => 'Verschieben fehlgeschlagen',
    'js.error.copy'               => 'Kopieren fehlgeschlagen',
    'js.error.mkdir'              => 'Ordner konnte nicht erstellt werden',
    'js.error.delete_partial'     => 'Löschen fehlgeschlagen: :files',
    'js.error.operation_partial'  => 'Vorgang fehlgeschlagen für: :files',
];

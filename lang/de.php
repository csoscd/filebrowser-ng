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

    // Browser
    'browser.shares'              => 'Freigaben',
    'browser.no_share'            => 'Freigabe aus der Seitenleiste auswählen',
    'browser.new_folder'          => 'Neuer Ordner',
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

    // JS error messages
    'js.error.load_shares'        => 'Freigaben konnten nicht geladen werden',
    'js.error.load_files'         => 'Dateien konnten nicht geladen werden',
    'js.error.chunk_upload'       => 'Upload-Fragment fehlgeschlagen',
    'js.error.download_zip'       => 'ZIP-Download fehlgeschlagen',
    'js.error.rename'             => 'Umbenennen fehlgeschlagen',
    'js.error.move'               => 'Verschieben fehlgeschlagen',
    'js.error.copy'               => 'Kopieren fehlgeschlagen',
    'js.error.mkdir'              => 'Ordner konnte nicht erstellt werden',
    'js.error.delete_partial'     => 'Löschen fehlgeschlagen: :files',
];

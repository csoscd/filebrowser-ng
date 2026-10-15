<?php

declare(strict_types=1);

return [
    'app.name'                    => 'filebrowser-ng',

    // Login
    'login.title'                 => 'Connexion',
    'login.username'              => 'Nom d\'utilisateur',
    'login.password'              => 'Mot de passe',
    'login.submit'                => 'Se connecter',
    'login.error.credentials_required' => 'Veuillez saisir un nom d\'utilisateur et un mot de passe.',
    'login.error.invalid_credentials'  => 'Identifiants invalides. Veuillez réessayer.',

    // Browser
    'browser.shares'              => 'Partages',
    'browser.no_share'            => 'Sélectionnez un partage dans la barre latérale',
    'browser.new_folder'          => 'Nouveau dossier',
    'browser.upload'              => 'Envoyer',
    'browser.download'            => 'Télécharger',
    'browser.download_zip'        => 'Télécharger en ZIP',
    'browser.open_hint'           => 'Double-clic pour ouvrir',
    'browser.selected'            => 'sélectionné(s)',
    'browser.deselect_all'        => 'Tout désélectionner',
    'browser.rename'              => 'Renommer',
    'browser.move_copy'           => 'Déplacer / Copier',
    'browser.delete'              => 'Supprimer',
    'browser.loading'             => 'Chargement…',
    'browser.empty'               => 'Ce dossier est vide.',

    // Espace disque du partage
    'share.disk.free_of'          => 'libre sur',

    // File table
    'file.name'                   => 'Nom',
    'file.size'                   => 'Taille',
    'file.modified'               => 'Modifié',
    'file.actions'                => 'Actions',

    // Upload
    'upload.drop'                 => 'Déposez des fichiers ici ou cliquez pour envoyer',
    'upload.uploading'            => 'Envoi en cours…',
    'upload.success'              => ':count fichier(s) envoyé(s).',
    'upload.failed'               => 'Échec : :files',

    // Dialogs
    'dialog.movecopy.title'        => 'Déplacer / Copier',
    'dialog.movecopy.label'        => 'Dossier de destination',
    'dialog.movecopy.hint'         => 'Le nom du fichier sera conservé.',
    'dialog.movecopy.hint_dir'     => 'Les dossiers ne peuvent être que déplacés, pas copiés.',
    'dialog.movecopy.move'         => 'Déplacer',
    'dialog.movecopy.copy'         => 'Copier',
    'dialog.movecopy.cancel'       => 'Annuler',
    'dialog.rename.title'         => 'Renommer',
    'dialog.rename.label'         => 'Nouveau nom',
    'dialog.rename.confirm'       => 'Renommer',
    'dialog.rename.cancel'        => 'Annuler',
    'dialog.delete.title'         => 'Supprimer',
    'dialog.delete.message'       => 'Supprimer « :name » ? Cette action est irréversible.',
    'dialog.delete.message_multi' => 'Supprimer :count élément(s) ? Cette action est irréversible.',
    'dialog.delete.confirm'       => 'Supprimer',
    'dialog.delete.cancel'        => 'Annuler',
    'dialog.mkdir.title'          => 'Nouveau dossier',
    'dialog.mkdir.label'          => 'Nom du dossier',
    'dialog.mkdir.confirm'        => 'Créer',
    'dialog.mkdir.cancel'         => 'Annuler',

    // Nav
    'nav.logout'                  => 'Déconnexion',
    'nav.logged_in_as'            => 'Connecté en tant que :user',

    // Boîte de dialogue de sélection de dossier
    'dialog.picker.title'     => 'Déplacer / Copier',
    'dialog.picker.select_hint' => 'Naviguez vers le dossier cible, puis choisissez une action.',
    'dialog.picker.current'   => 'Destination :',
    'dialog.picker.empty'     => 'Aucun sous-dossier',
    'dialog.picker.move_here' => 'Déplacer ici',
    'dialog.picker.copy_here' => 'Copier ici',
    'dialog.picker.cancel'    => 'Annuler',
    'dialog.picker.root'      => 'Racine',
    'dialog.picker.shares'    => 'Partages',
    'dialog.picker.executing' => 'En cours…',

    // JS error messages
    'js.error.load_shares'        => 'Impossible de charger les partages',
    'js.error.load_files'         => 'Impossible de charger les fichiers',
    'js.error.chunk_upload'       => 'Échec de l\'envoi du fragment',
    'js.error.download_zip'       => 'Échec du téléchargement ZIP',
    'js.error.rename'             => 'Échec du renommage',
    'js.error.move'               => 'Échec du déplacement',
    'js.error.copy'               => 'Échec de la copie',
    'js.error.mkdir'              => 'Impossible de créer le dossier',
    'js.error.delete_partial'     => 'Échec de la suppression : :files',
    'js.error.operation_partial'  => 'Opération échouée pour : :files',
];

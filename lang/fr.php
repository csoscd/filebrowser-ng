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
    'login.error.oidc_unavailable'     => 'Le fournisseur d\'identité est actuellement indisponible. Veuillez réessayer plus tard.',
    'login.error.oidc_denied'          => 'La connexion a été annulée ou refusée par le fournisseur d\'identité.',
    'login.error.oidc_invalid'         => 'La réponse de connexion était invalide. Veuillez réessayer.',

    // Bouton de connexion OIDC
    'login.oidc.submit'                => 'Se connecter avec SSO',
    'login.or'                         => 'ou',

    // Formulaire SMB (après connexion OIDC)
    'login.smb.subtitle'               => 'Saisissez votre mot de passe serveur de fichiers',
    'login.smb.password'               => 'Mot de passe du serveur de fichiers (SMB)',
    'login.smb.save_credentials'       => 'Mémoriser le mot de passe (chiffré)',
    'login.smb.save_disabled'          => 'Mémoriser le mot de passe (aucune clé – configurer le claim smb_key)',
    'login.smb.submit'                 => 'Connecter',
    'login.smb.error.password_required' => 'Veuillez saisir votre mot de passe serveur de fichiers.',
    'login.smb.error.invalid_credentials' => 'Mot de passe du serveur de fichiers incorrect. Veuillez réessayer.',

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
    'upload.cancel'               => 'Annuler',
    'upload.cancelled'            => 'Envoi annulé.',
    'upload.finalizing'           => 'Enregistrement…',
    'upload.panel.title'          => 'Envois',
    'upload.panel.clear'          => 'Effacer terminés',
    'upload.status.pending'       => 'En attente…',
    'upload.status.done'          => 'Terminé',
    'upload.status.failed'        => 'Échec',
    'upload.status.cancelled'     => 'Annulé',

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

    // Gestion des serveurs
    'server.add'                  => 'Ajouter un serveur',
    'server.add_title'            => 'Ajouter un serveur SMB',
    'server.host'                 => 'Hôte / IP',
    'server.display_name'         => 'Nom d\'affichage (optionnel)',
    'server.domain'               => 'Domaine / Groupe de travail (optionnel)',
    'server.remove'               => 'Supprimer le serveur',
    'server.remove_confirm'       => 'Supprimer le serveur « :name » et tous ses partages enregistrés ?',
    'server.credentials_required' => 'Identifiants requis',
    'server.enter_credentials'    => 'Saisissez les identifiants pour :host',
    'server.connecting'           => 'Connexion…',
    'server.connect_failed'       => 'Échec de connexion. Vérifiez les identifiants.',
    'server.already_added'        => 'Ce serveur a déjà été ajouté.',
    'server.default_label'        => '(par défaut)',

    // JS error messages
    'js.error.load_shares'        => 'Impossible de charger les partages',
    'js.error.add_server'         => 'Impossible d\'ajouter le serveur',
    'js.error.credentials'        => 'Impossible de sauvegarder les identifiants',
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

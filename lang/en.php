<?php

declare(strict_types=1);

return [
    'app.name'                    => 'filebrowser-ng',

    // Login
    'login.title'                 => 'Sign in',
    'login.username'              => 'Username',
    'login.password'              => 'Password',
    'login.submit'                => 'Sign in',
    'login.error.credentials_required' => 'Please enter username and password.',
    'login.error.invalid_credentials'  => 'Invalid credentials. Please try again.',

    // Browser
    'browser.shares'              => 'Shares',
    'browser.no_share'            => 'Select a share from the sidebar',
    'browser.new_folder'          => 'New folder',
    'browser.upload'              => 'Upload',
    'browser.download'            => 'Download',
    'browser.download_zip'        => 'Download ZIP',
    'browser.open_hint'           => 'Double-click to open',
    'browser.selected'            => 'selected',
    'browser.deselect_all'        => 'Deselect all',
    'browser.rename'              => 'Rename',
    'browser.move_copy'           => 'Move / Copy',
    'browser.delete'              => 'Delete',
    'browser.loading'             => 'Loading…',
    'browser.empty'               => 'This folder is empty.',

    // File table
    'file.name'                   => 'Name',
    'file.size'                   => 'Size',
    'file.modified'               => 'Modified',
    'file.actions'                => 'Actions',

    // Upload
    'upload.drop'                 => 'Drop files here or click to upload',
    'upload.uploading'            => 'Uploading…',
    'upload.success'              => ':count file(s) uploaded.',
    'upload.failed'               => 'Failed: :files',

    // Dialogs
    'dialog.movecopy.title'        => 'Move / Copy',
    'dialog.movecopy.label'        => 'Destination folder',
    'dialog.movecopy.hint'         => 'The filename will be preserved.',
    'dialog.movecopy.hint_dir'     => 'Folders can only be moved, not copied.',
    'dialog.movecopy.move'         => 'Move',
    'dialog.movecopy.copy'         => 'Copy',
    'dialog.movecopy.cancel'       => 'Cancel',
    'dialog.rename.title'         => 'Rename',
    'dialog.rename.label'         => 'New name',
    'dialog.rename.confirm'       => 'Rename',
    'dialog.rename.cancel'        => 'Cancel',
    'dialog.delete.title'         => 'Delete',
    'dialog.delete.message'       => 'Delete ":name"? This cannot be undone.',
    'dialog.delete.message_multi' => 'Delete :count items? This cannot be undone.',
    'dialog.delete.confirm'       => 'Delete',
    'dialog.delete.cancel'        => 'Cancel',
    'dialog.mkdir.title'          => 'New folder',
    'dialog.mkdir.label'          => 'Folder name',
    'dialog.mkdir.confirm'        => 'Create',
    'dialog.mkdir.cancel'         => 'Cancel',

    // Nav
    'nav.logout'                  => 'Logout',
    'nav.logged_in_as'            => 'Signed in as :user',
];

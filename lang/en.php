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
    'login.error.oidc_unavailable'     => 'The identity provider is currently unavailable. Please try again later.',
    'login.error.oidc_denied'          => 'Login was cancelled or denied by the identity provider.',
    'login.error.oidc_invalid'         => 'The login response was invalid. Please try again.',

    // OIDC login button
    'login.oidc.submit'                => 'Sign in with SSO',
    'login.or'                         => 'or',

    // SMB credential form (shown after OIDC login)
    'login.smb.subtitle'               => 'Enter your file server password',
    'login.smb.password'               => 'File server password (SMB)',
    'login.smb.save_credentials'       => 'Remember password (encrypted)',
    'login.smb.save_disabled'          => 'Remember password (no encryption key – configure smb_key claim)',
    'login.smb.submit'                 => 'Connect',
    'login.smb.error.password_required' => 'Please enter your file server password.',
    'login.smb.error.invalid_credentials' => 'File server password incorrect. Please try again.',

    // Browser
    'browser.shares'              => 'Shares',
    'browser.no_share'            => 'Select a share from the sidebar',
    'browser.new_folder'          => 'New folder',
    'browser.refresh'             => 'Refresh',
    'browser.filter_placeholder'  => 'Filter…',
    'browser.filter_no_results'   => 'No files match your filter.',
    'browser.filter_clear'        => 'Clear filter',
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

    // Share disk info
    'share.disk.free_of'          => 'free of',

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
    'upload.cancel'               => 'Cancel',
    'upload.cancelled'            => 'Upload cancelled.',
    'upload.finalizing'           => 'Saving…',
    'upload.panel.title'          => 'Uploads',
    'upload.panel.clear'          => 'Clear done',
    'upload.status.pending'       => 'Waiting…',
    'upload.status.done'          => 'Done',
    'upload.status.failed'        => 'Failed',
    'upload.status.cancelled'     => 'Cancelled',

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

    // Folder picker dialog
    'dialog.picker.title'     => 'Move / Copy',
    'dialog.picker.select_hint' => 'Navigate to the destination folder, then choose an action.',
    'dialog.picker.current'   => 'Destination:',
    'dialog.picker.empty'     => 'No subfolders',
    'dialog.picker.move_here' => 'Move here',
    'dialog.picker.copy_here' => 'Copy here',
    'dialog.picker.cancel'    => 'Cancel',
    'dialog.picker.root'      => 'Root',
    'dialog.picker.shares'    => 'Shares',
    'dialog.picker.executing' => 'Working…',

    // Server management
    'server.add'                  => 'Add server',
    'server.add_title'            => 'Add SMB Server',
    'server.host'                 => 'Host / IP',
    'server.display_name'         => 'Display name (optional)',
    'server.domain'               => 'Domain / Workgroup (optional)',
    'server.remove'               => 'Remove server',
    'server.remove_confirm'       => 'Remove server ":name" and all its saved shares?',
    'server.credentials_required' => 'Credentials required',
    'server.enter_credentials'    => 'Enter credentials for :host',
    'server.connecting'           => 'Connecting…',
    'server.connect_failed'       => 'Connection failed. Check credentials.',
    'server.already_added'        => 'This server is already added.',
    'server.default_label'        => '(default)',

    // JS error messages
    'js.error.load_shares'        => 'Failed to load shares',
    'js.error.add_server'         => 'Failed to add server',
    'js.error.credentials'        => 'Failed to store credentials',
    'js.error.load_files'         => 'Failed to load files',
    'js.error.chunk_upload'       => 'Chunk upload failed',
    'js.error.download_zip'       => 'ZIP download failed',
    'js.error.rename'             => 'Rename failed',
    'js.error.move'               => 'Move failed',
    'js.error.copy'               => 'Copy failed',
    'js.error.mkdir'              => 'Create folder failed',
    'js.error.delete_partial'     => 'Failed to delete: :files',
    'js.error.operation_partial'  => 'Operation failed for: :files',
];

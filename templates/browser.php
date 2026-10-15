<div
    x-data="fileBrowser()"
    x-init="init()"
    class="flex flex-col h-screen"
>
    <!-- ── Top navigation ──────────────────────────────────────────────────── -->
    <header class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between shadow-sm flex-shrink-0">
        <div class="flex items-center gap-3">
            <!-- Hamburger (mobile only) -->
            <button @click="sidebarOpen = !sidebarOpen"
                    class="md:hidden p-1 -ml-1 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 7a2 2 0 012-2h3.586a1 1 0 01.707.293L11 7h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                </svg>
            </div>
            <span class="font-semibold text-gray-900"><?= htmlspecialchars($t->t('app.name'), ENT_QUOTES) ?></span>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-500 hidden sm:block">
                <?= htmlspecialchars($t->t('nav.logged_in_as', ['user' => $username ?? '']), ENT_QUOTES) ?>
            </span>
            <a href="/auth/logout"
               class="text-sm text-gray-600 hover:text-red-600 font-medium transition">
                <?= htmlspecialchars($t->t('nav.logout'), ENT_QUOTES) ?>
            </a>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden relative">

        <!-- ── Backdrop (mobile only) ──────────────────────────────────────── -->
        <div x-show="sidebarOpen"
             x-transition:enter="transition-opacity ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 z-20 bg-black/40 md:hidden"
             x-cloak></div>

        <!-- ── Sidebar: shares ─────────────────────────────────────────────── -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
               class="fixed md:relative md:transform-none inset-y-0 left-0 z-30
                      w-64 md:w-56 bg-white border-r border-gray-200
                      flex flex-col flex-shrink-0 overflow-y-auto
                      transform transition-transform duration-200 ease-in-out">
            <div class="px-4 pt-4 pb-2">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    <?= htmlspecialchars($t->t('browser.shares'), ENT_QUOTES) ?>
                </p>
            </div>

            <template x-if="loadingShares">
                <div class="px-4 py-3 text-sm text-gray-400">
                    <?= htmlspecialchars($t->t('browser.loading'), ENT_QUOTES) ?>
                </div>
            </template>

            <nav class="flex-1 px-2 pb-2 overflow-y-auto">
                <template x-for="srv in servers" :key="srv.id">
                    <div class="mb-2">
                        <!-- Server header -->
                        <div class="flex items-center gap-1 px-2 py-1 group">
                            <button @click="srv.expanded = !srv.expanded"
                                    class="flex-1 flex items-center gap-1.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hover:text-gray-700 text-left truncate">
                                <svg class="w-3 h-3 flex-shrink-0 transition-transform" :class="srv.expanded ? 'rotate-90' : ''"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                <span x-text="srv.display_name" class="truncate"></span>
                                <span x-show="srv.is_default" class="text-gray-400 font-normal normal-case tracking-normal">
                                    <?= htmlspecialchars($t->t('server.default_label'), ENT_QUOTES) ?>
                                </span>
                            </button>
                            <button x-show="!srv.is_default"
                                    @click="confirmRemoveServer(srv)"
                                    class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-500 transition p-0.5 rounded flex-shrink-0"
                                    title="<?= htmlspecialchars($t->t('server.remove'), ENT_QUOTES) ?>">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Expanded: shares list or credential prompt -->
                        <div x-show="srv.expanded">
                            <!-- Credential prompt -->
                            <template x-if="srv.needs_credentials">
                                <div class="px-3 py-2">
                                    <p class="text-xs text-gray-500 mb-2">
                                        <?= htmlspecialchars($t->t('server.credentials_required'), ENT_QUOTES) ?>
                                    </p>
                                    <input type="text" :placeholder="'<?= addcslashes($t->t('login.username'), "\\'") ?>'"
                                           x-model="srv._credUser"
                                           class="w-full text-xs border border-gray-300 rounded px-2 py-1 mb-1 focus:outline-none focus:ring-1 focus:ring-blue-400"/>
                                    <input type="password" :placeholder="'<?= addcslashes($t->t('login.password'), "\\'") ?>'"
                                           x-model="srv._credPass"
                                           @keydown.enter="submitServerCredentials(srv)"
                                           class="w-full text-xs border border-gray-300 rounded px-2 py-1 mb-1 focus:outline-none focus:ring-1 focus:ring-blue-400"/>
                                    <template x-if="srv._credError">
                                        <p class="text-xs text-red-500 mb-1" x-text="srv._credError"></p>
                                    </template>
                                    <button @click="submitServerCredentials(srv)"
                                            :disabled="srv._credLoading"
                                            class="w-full text-xs bg-blue-600 text-white rounded px-2 py-1 hover:bg-blue-700 disabled:opacity-50">
                                        <span x-show="!srv._credLoading"><?= htmlspecialchars($t->t('login.submit'), ENT_QUOTES) ?></span>
                                        <span x-show="srv._credLoading"><?= htmlspecialchars($t->t('server.connecting'), ENT_QUOTES) ?></span>
                                    </button>
                                </div>
                            </template>

                            <!-- Shares -->
                            <template x-if="!srv.needs_credentials">
                                <div>
                                    <template x-for="share in (srv.shares ?? [])" :key="share.name">
                                        <button
                                            @click="openShare(share.name, srv.host); sidebarOpen = false"
                                            :class="currentShare === share.name && currentServer === srv.host
                                                ? 'bg-blue-50 text-blue-700 font-medium'
                                                : 'text-gray-700 hover:bg-gray-100'"
                                            class="w-full text-left px-3 py-2 rounded-lg text-sm transition mb-0.5"
                                        >
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M3 7a2 2 0 012-2h3.586a1 1 0 01.707.293L11 7h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                                                </svg>
                                                <span x-text="share.name" class="truncate flex-1"></span>
                                            </div>
                                            <template x-if="share.disk">
                                                <div class="mt-1.5 pl-6">
                                                    <div class="w-full bg-gray-200 rounded-full h-1"
                                                         :class="currentShare === share.name && currentServer === srv.host ? 'bg-blue-100' : 'bg-gray-200'">
                                                        <div class="h-1 rounded-full transition-all"
                                                             :class="((share.disk.total - share.disk.free) / share.disk.total) > 0.9
                                                                 ? 'bg-red-500'
                                                                 : ((share.disk.total - share.disk.free) / share.disk.total) > 0.75
                                                                     ? 'bg-yellow-400'
                                                                     : 'bg-blue-400'"
                                                             :style="'width:' + Math.round(((share.disk.total - share.disk.free) / share.disk.total) * 100) + '%'">
                                                        </div>
                                                    </div>
                                                    <p class="text-xs mt-0.5 tabular-nums"
                                                       :class="currentShare === share.name && currentServer === srv.host ? 'text-blue-500' : 'text-gray-400'"
                                                       x-text="formatSize(share.disk.free) + ' <?= addcslashes($t->t('share.disk.free_of'), "\\'") ?> ' + formatSize(share.disk.total)">
                                                    </p>
                                                </div>
                                            </template>
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Add server button -->
                <div class="px-2 pt-1 pb-2">
                    <button @click="showAddServerModal = true"
                            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <?= htmlspecialchars($t->t('server.add'), ENT_QUOTES) ?>
                    </button>
                </div>
            </nav>
        </aside>

        <!-- ── Main content ────────────────────────────────────────────────── -->
        <main class="flex-1 flex flex-col overflow-hidden bg-gray-50">

            <!-- No share selected -->
            <template x-if="!currentShare">
                <div class="flex-1 flex items-center justify-center text-gray-400">
                    <div class="text-center">
                        <svg class="w-16 h-16 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M3 7a2 2 0 012-2h3.586a1 1 0 01.707.293L11 7h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                        </svg>
                        <p><?= htmlspecialchars($t->t('browser.no_share'), ENT_QUOTES) ?></p>
                    </div>
                </div>
            </template>

            <template x-if="currentShare">
                <div class="flex-1 flex flex-col overflow-hidden">

                    <!-- Toolbar -->
                    <div class="bg-white border-b border-gray-200 px-4 py-2 flex items-center gap-2 flex-wrap flex-shrink-0">
                        <!-- Breadcrumb -->
                        <nav class="flex items-center gap-1 text-sm flex-1 min-w-0">
                            <button @click="navigate('')"
                                    class="text-blue-600 hover:text-blue-800 font-medium truncate max-w-[6rem]"
                                    x-text="currentShare"></button>
                            <template x-for="(crumb, i) in breadcrumbs" :key="i">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3 h-3 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    <button @click="navigate(crumb.path)"
                                            class="text-blue-600 hover:text-blue-800 truncate max-w-[8rem]"
                                            x-text="crumb.name"></button>
                                </span>
                            </template>
                        </nav>

                        <!-- Action buttons -->
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button @click="showMkdirDialog = true"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-white border border-gray-300
                                           rounded-lg hover:bg-gray-50 transition font-medium text-gray-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                <span class="hidden sm:inline"><?= htmlspecialchars($t->t('browser.new_folder'), ENT_QUOTES) ?></span>
                            </button>
                            <label class="relative inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-blue-600 text-white
                                          rounded-lg hover:bg-blue-700 transition font-medium cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                <span class="hidden sm:inline"><?= htmlspecialchars($t->t('browser.upload'), ENT_QUOTES) ?></span>
                                <template x-if="uploadQueue.filter(u => u.status === 'uploading' || u.status === 'pending').length > 0">
                                    <span class="absolute -top-1.5 -right-1.5 min-w-[1.1rem] h-[1.1rem] flex items-center justify-center
                                                 rounded-full bg-yellow-400 text-gray-900 text-[10px] font-bold leading-none px-1"
                                          x-text="uploadQueue.filter(u => u.status === 'uploading' || u.status === 'pending').length"></span>
                                </template>
                                <input type="file" multiple class="hidden" @change="uploadFiles($event)">
                            </label>
                        </div>
                    </div>

                    <!-- Flash message -->
                    <template x-if="flash">
                        <div x-text="flash"
                             :class="flashType === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'"
                             class="border-b px-4 py-2 text-sm flex-shrink-0">
                        </div>
                    </template>

                    <!-- Contextual action bar -->
                    <template x-if="selected.length > 0">
                        <div class="bg-blue-50 border-b border-blue-200 px-4 py-2 flex items-center gap-1.5 flex-shrink-0 flex-wrap">
                            <span class="text-sm font-medium text-blue-700 mr-1"
                                  x-text="selected.length + ' <?= $t->t('browser.selected') ?>'"></span>
                            <button @click="clearSelection()"
                                    class="p-1 rounded text-blue-500 hover:text-blue-700 hover:bg-blue-100 transition mr-2"
                                    title="<?= htmlspecialchars($t->t('browser.deselect_all'), ENT_QUOTES) ?>">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                            <div class="w-px h-4 bg-blue-300 mr-1 flex-shrink-0"></div>

                            <!-- Download single file -->
                            <template x-if="singleSelected && singleSelected.type !== 'dir'">
                                <button @click="downloadSelected()"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    <?= htmlspecialchars($t->t('browser.download'), ENT_QUOTES) ?>
                                </button>
                            </template>

                            <!-- Download ZIP (multiple files only) -->
                            <template x-if="selected.length > 1 && selectionIsAllFiles">
                                <button @click="downloadZip()"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    <?= htmlspecialchars($t->t('browser.download_zip'), ENT_QUOTES) ?>
                                </button>
                            </template>

                            <!-- Move/Copy (all selections) -->
                            <button @click="openPicker()"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                                <?= htmlspecialchars($t->t('browser.move_copy'), ENT_QUOTES) ?>
                            </button>

                            <!-- Rename (single item) -->
                            <template x-if="singleSelected">
                                <button @click="startRename(singleSelected)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    <?= htmlspecialchars($t->t('browser.rename'), ENT_QUOTES) ?>
                                </button>
                            </template>

                            <!-- Delete (always visible) -->
                            <button @click="showDeleteDialog = true"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition ml-auto">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                <?= htmlspecialchars($t->t('browser.delete'), ENT_QUOTES) ?>
                            </button>
                        </div>
                    </template>

                    <!-- Drop zone + file list -->
                    <div
                        class="flex-1 overflow-y-auto"
                        @dragover.prevent="dragOver = true"
                        @dragleave="dragOver = false"
                        @drop.prevent="handleDrop($event)"
                        :class="dragOver ? 'bg-blue-50 ring-2 ring-inset ring-blue-400' : ''"
                    >
                        <!-- Loading -->
                        <template x-if="loadingFiles">
                            <div class="flex items-center justify-center py-20 text-gray-400 text-sm">
                                <?= htmlspecialchars($t->t('browser.loading'), ENT_QUOTES) ?>
                            </div>
                        </template>

                        <!-- Empty -->
                        <template x-if="!loadingFiles && files.length === 0">
                            <div class="flex flex-col items-center justify-center py-20 text-gray-400">
                                <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M3 7a2 2 0 012-2h3.586a1 1 0 01.707.293L11 7h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                                </svg>
                                <p class="text-sm"><?= htmlspecialchars($t->t('browser.empty'), ENT_QUOTES) ?></p>
                                <p class="text-xs mt-1 text-gray-300"><?= htmlspecialchars($t->t('upload.drop'), ENT_QUOTES) ?></p>
                            </div>
                        </template>

                        <!-- File table -->
                        <template x-if="!loadingFiles && files.length > 0">
                            <table class="w-full text-sm">
                                <thead class="bg-white border-b border-gray-200 sticky top-0">
                                    <tr>
                                        <th class="px-3 py-2.5 w-10" @click.stop>
                                            <input type="checkbox"
                                                   :checked="allSelected"
                                                   :indeterminate.prop="selected.length > 0 && !allSelected"
                                                   @change="toggleSelectAll()"
                                                   class="rounded border-gray-300 text-blue-600 cursor-pointer focus:ring-blue-500">
                                        </th>
                                        <th class="text-left px-4 py-2.5 font-medium text-gray-600 w-full cursor-pointer select-none hover:text-gray-900 transition-colors"
                                            @click="setSort('name')">
                                            <?= htmlspecialchars($t->t('file.name'), ENT_QUOTES) ?>
                                            <span x-show="sortColumn === 'name'" x-text="sortDir === 'asc' ? ' ↑' : ' ↓'" class="text-blue-500"></span>
                                        </th>
                                        <th class="text-right px-4 py-2.5 font-medium text-gray-600 whitespace-nowrap hidden sm:table-cell cursor-pointer select-none hover:text-gray-900 transition-colors"
                                            @click="setSort('size')">
                                            <?= htmlspecialchars($t->t('file.size'), ENT_QUOTES) ?>
                                            <span x-show="sortColumn === 'size'" x-text="sortDir === 'asc' ? ' ↑' : ' ↓'" class="text-blue-500"></span>
                                        </th>
                                        <th class="text-right px-4 py-2.5 font-medium text-gray-600 whitespace-nowrap hidden md:table-cell cursor-pointer select-none hover:text-gray-900 transition-colors"
                                            @click="setSort('modified')">
                                            <?= htmlspecialchars($t->t('file.modified'), ENT_QUOTES) ?>
                                            <span x-show="sortColumn === 'modified'" x-text="sortDir === 'asc' ? ' ↑' : ' ↓'" class="text-blue-500"></span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="file in sortedFiles" :key="file.path">
                                        <tr @dblclick="file.type === 'dir' ? navigate(file.path) : openFile(file)"
                                            :class="selected.includes(file.path) ? 'bg-blue-50' : 'hover:bg-white'"
                                            class="border-b border-gray-100 transition cursor-pointer select-none">
                                            <td class="px-3 py-2.5 w-10" @click.stop>
                                                <input type="checkbox"
                                                       :checked="selected.includes(file.path)"
                                                       @change="toggleSelect(file.path)"
                                                       class="rounded border-gray-300 text-blue-600 cursor-pointer focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <div class="flex items-center gap-2.5 font-medium text-gray-800">
                                                    <!-- Folder icon -->
                                                    <template x-if="file.type === 'dir'">
                                                        <svg class="w-5 h-5 text-yellow-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                                                        </svg>
                                                    </template>
                                                    <!-- File icon -->
                                                    <template x-if="file.type !== 'dir'">
                                                        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                  d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                                        </svg>
                                                    </template>
                                                    <span x-text="file.name" class="truncate"></span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-2.5 text-right text-gray-500 whitespace-nowrap hidden sm:table-cell"
                                                x-text="file.type === 'dir' ? '—' : formatSize(file.size)"></td>
                                            <td class="px-4 py-2.5 text-right text-gray-500 whitespace-nowrap hidden md:table-cell"
                                                x-text="formatDate(file.modified)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </template>
                    </div>
                </div>
            </template>
        </main>
    </div>

    <!-- ── Upload Manager Panel ───────────────────────────────────────────── -->
    <div x-show="showUploadPanel" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-4 right-4 z-40 w-80 bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden">

        <!-- Panel header -->
        <div class="flex items-center justify-between px-4 py-2.5 bg-gray-50 border-b border-gray-200">
            <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-gray-800" x-text="i18n.uploadPanelTitle"></span>
                <template x-if="uploadQueue.filter(u => u.status === 'uploading' || u.status === 'pending').length > 0">
                    <span class="min-w-[1.2rem] h-5 flex items-center justify-center rounded-full
                                 bg-blue-100 text-blue-700 text-xs font-bold px-1.5"
                          x-text="uploadQueue.filter(u => u.status === 'uploading' || u.status === 'pending').length"></span>
                </template>
            </div>
            <div class="flex items-center gap-1">
                <!-- Clear done -->
                <button @click="uploadQueue = uploadQueue.filter(u => u.status === 'pending' || u.status === 'uploading')"
                        x-show="uploadQueue.some(u => u.status === 'done' || u.status === 'failed' || u.status === 'cancelled')"
                        class="text-xs text-gray-400 hover:text-gray-600 px-1.5 py-0.5 rounded hover:bg-gray-100 transition"
                        x-text="i18n.uploadPanelClear"></button>
                <!-- Collapse -->
                <button @click="uploadPanelCollapsed = !uploadPanelCollapsed"
                        class="p-1 rounded text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition">
                    <svg class="w-4 h-4 transition-transform" :class="uploadPanelCollapsed ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <!-- Close -->
                <button @click="showUploadPanel = false"
                        class="p-1 rounded text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- File list -->
        <div x-show="!uploadPanelCollapsed" class="max-h-72 overflow-y-auto divide-y divide-gray-100">
            <template x-for="item in uploadQueue" :key="item.id">
                <div class="px-4 py-2.5">
                    <div class="flex items-start gap-2">
                        <!-- Status icon -->
                        <div class="flex-shrink-0 mt-0.5">
                            <template x-if="item.status === 'done'">
                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </template>
                            <template x-if="item.status === 'failed'">
                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </template>
                            <template x-if="item.status === 'cancelled'">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                            </template>
                            <template x-if="item.status === 'uploading'">
                                <svg class="w-4 h-4 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </template>
                            <template x-if="item.status === 'pending'">
                                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </template>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1">
                                <span class="truncate text-sm font-medium text-gray-700 flex-1" x-text="item.name"></span>
                                <!-- Cancel button -->
                                <button x-show="item.status === 'pending' || item.status === 'uploading'"
                                        @click="cancelQueueItem(item)"
                                        class="flex-shrink-0 p-0.5 rounded text-gray-400 hover:text-red-500 hover:bg-red-50 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Progress bar (uploading only) -->
                            <template x-if="item.status === 'uploading'">
                                <div class="mt-1">
                                    <div class="flex justify-between text-xs text-gray-400 mb-0.5 tabular-nums">
                                        <span x-text="item.finalizing ? formatSize(item.totalBytes) : formatSize(item.uploadedBytes) + ' / ' + formatSize(item.totalBytes)"></span>
                                        <span class="flex gap-1.5 items-center">
                                            <template x-if="item.finalizing">
                                                <span class="text-blue-500 animate-pulse"><?= htmlspecialchars($t->t('upload.finalizing')) ?></span>
                                            </template>
                                            <template x-if="!item.finalizing">
                                                <span class="flex gap-1.5 items-center">
                                                    <span x-show="item.speed > 0" x-text="formatSpeed(item.speed)" class="text-blue-400"></span>
                                                    <span x-text="item.progress + '%'"></span>
                                                </span>
                                            </template>
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-1">
                                        <div class="h-1 rounded-full transition-all duration-150"
                                             :class="item.finalizing ? 'bg-blue-400 animate-pulse w-full' : 'bg-blue-500'"
                                             :style="item.finalizing ? '' : 'width:' + item.progress + '%'"></div>
                                    </div>
                                </div>
                            </template>

                            <!-- Status text (not uploading) -->
                            <template x-if="item.status !== 'uploading'">
                                <p class="text-xs mt-0.5"
                                   :class="{
                                       'text-green-600':  item.status === 'done',
                                       'text-red-500':    item.status === 'failed',
                                       'text-gray-400':   item.status === 'cancelled' || item.status === 'pending',
                                   }"
                                   x-text="item.status === 'done'      ? i18n.uploadStatusDone
                                         : item.status === 'failed'    ? i18n.uploadStatusFailed
                                         : item.status === 'cancelled' ? i18n.uploadStatusCancelled
                                         : i18n.uploadStatusPending">
                                </p>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- ── Modal: Rename ────────────────────────────────────────────────────── -->
    <div x-show="showRenameDialog" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
         @keydown.escape.window="showRenameDialog = false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6" @click.stop>
            <h2 class="text-lg font-semibold mb-4">
                <?= htmlspecialchars($t->t('dialog.rename.title'), ENT_QUOTES) ?>
            </h2>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                <?= htmlspecialchars($t->t('dialog.rename.label'), ENT_QUOTES) ?>
            </label>
            <input type="text" x-model="renameValue"
                   @keydown.enter="confirmRename()"
                   x-ref="renameInput"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4">
            <div class="flex justify-end gap-2">
                <button @click="showRenameDialog = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    <?= htmlspecialchars($t->t('dialog.rename.cancel'), ENT_QUOTES) ?>
                </button>
                <button @click="confirmRename()"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
                    <?= htmlspecialchars($t->t('dialog.rename.confirm'), ENT_QUOTES) ?>
                </button>
            </div>
        </div>
    </div>

    <!-- ── Modal: Delete ────────────────────────────────────────────────────── -->
    <div x-show="showDeleteDialog" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
         @keydown.escape.window="showDeleteDialog = false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6" @click.stop>
            <h2 class="text-lg font-semibold mb-2">
                <?= htmlspecialchars($t->t('dialog.delete.title'), ENT_QUOTES) ?>
            </h2>
            <p class="text-sm text-gray-600 mb-5"
               x-text="selected.length === 1
                   ? deleteMessage.replace(':name', singleSelected?.name ?? '')
                   : deleteMessageMulti.replace(':count', selected.length)">
            </p>
            <div class="flex justify-end gap-2">
                <button @click="showDeleteDialog = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    <?= htmlspecialchars($t->t('dialog.delete.cancel'), ENT_QUOTES) ?>
                </button>
                <button @click="confirmDelete()"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">
                    <?= htmlspecialchars($t->t('dialog.delete.confirm'), ENT_QUOTES) ?>
                </button>
            </div>
        </div>
    </div>

    <!-- ── Modal: New folder ────────────────────────────────────────────────── -->
    <div x-show="showMkdirDialog" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
         @keydown.escape.window="showMkdirDialog = false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6" @click.stop>
            <h2 class="text-lg font-semibold mb-4">
                <?= htmlspecialchars($t->t('dialog.mkdir.title'), ENT_QUOTES) ?>
            </h2>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                <?= htmlspecialchars($t->t('dialog.mkdir.label'), ENT_QUOTES) ?>
            </label>
            <input type="text" x-model="mkdirValue"
                   @keydown.enter="confirmMkdir()"
                   x-ref="mkdirInput"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4">
            <div class="flex justify-end gap-2">
                <button @click="showMkdirDialog = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    <?= htmlspecialchars($t->t('dialog.mkdir.cancel'), ENT_QUOTES) ?>
                </button>
                <button @click="confirmMkdir()"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
                    <?= htmlspecialchars($t->t('dialog.mkdir.confirm'), ENT_QUOTES) ?>
                </button>
            </div>
        </div>
    </div>
    <!-- ── Modal: Folder Picker (Move / Copy) ──────────────────────────────── -->
    <div x-show="showPickerDialog" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
         @keydown.escape.window="!pickerExecuting && (showPickerDialog = false)">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm flex flex-col overflow-hidden" style="max-height:80vh" @click.stop>
            <!-- Header -->
            <div class="px-5 pt-5 pb-3 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
                <h2 class="text-lg font-semibold text-gray-900">
                    <?= htmlspecialchars($t->t('dialog.picker.title'), ENT_QUOTES) ?>
                </h2>
                <button x-show="!pickerExecuting" @click="showPickerDialog = false"
                        class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Folder browser (shown while not executing) -->
            <div x-show="!pickerExecuting" class="flex flex-col flex-1 overflow-hidden min-h-0">
                <!-- Breadcrumb -->
                <div class="px-4 py-2 flex items-center gap-1 text-sm border-b border-gray-100 bg-gray-50 flex-wrap flex-shrink-0">
                    <button @click="pickerGoToShares()"
                            class="font-medium text-blue-600 hover:text-blue-800 transition">
                        <?= htmlspecialchars($t->t('dialog.picker.shares'), ENT_QUOTES) ?>
                    </button>
                    <template x-for="(crumb, i) in pickerBreadcrumbs" :key="i">
                        <span class="flex items-center gap-1">
                            <svg class="w-3 h-3 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            <button @click="crumb.isShare ? pickerOpenShare(crumb.name) : pickerNavigate(crumb.path)"
                                    class="text-blue-600 hover:text-blue-800 transition truncate max-w-[8rem]"
                                    x-text="crumb.name"></button>
                        </span>
                    </template>
                </div>

                <!-- List: shares (top level) or folders -->
                <div class="flex-1 overflow-y-auto">
                    <template x-if="pickerLoading">
                        <div class="px-4 py-4 text-sm text-gray-400">
                            <?= htmlspecialchars($t->t('browser.loading'), ENT_QUOTES) ?>
                        </div>
                    </template>

                    <!-- Shares list -->
                    <template x-if="!pickerLoading && pickerShare === ''">
                        <div>
                            <template x-for="s in pickerShares" :key="s._server + '::' + s.name">
                                <button @click="pickerOpenShare(s.name, s._server)"
                                        class="w-full text-left flex items-center gap-2.5 px-4 py-2.5 hover:bg-blue-50 text-sm text-gray-700 transition">
                                    <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M3 7a2 2 0 012-2h3.586a1 1 0 01.707.293L11 7h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                                    </svg>
                                    <span x-text="s.name" class="truncate flex-1"></span>
                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </template>

                    <!-- Folders list -->
                    <template x-if="!pickerLoading && pickerShare !== ''">
                        <div>
                            <template x-if="pickerFolders.length === 0">
                                <div class="px-4 py-4 text-sm text-gray-400 italic">
                                    <?= htmlspecialchars($t->t('dialog.picker.empty'), ENT_QUOTES) ?>
                                </div>
                            </template>
                            <template x-for="folder in pickerFolders" :key="folder.path">
                                <button @click="pickerNavigate(folder.path)"
                                        class="w-full text-left flex items-center gap-2.5 px-4 py-2.5 hover:bg-blue-50 text-sm text-gray-700 transition">
                                    <svg class="w-4 h-4 text-yellow-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                                    </svg>
                                    <span x-text="folder.name" class="truncate flex-1"></span>
                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- Current destination (only shown when inside a share) -->
                <template x-if="pickerShare !== ''">
                    <div class="px-4 py-2.5 border-t border-gray-100 bg-gray-50 text-xs text-gray-500 flex-shrink-0">
                        <?= htmlspecialchars($t->t('dialog.picker.current'), ENT_QUOTES) ?>
                        <span class="font-medium text-gray-700 ml-1"
                              x-text="pickerShare + (pickerPath !== '' ? '/' + pickerPath : '')"></span>
                    </div>
                </template>

                <!-- Hint -->
                <p class="px-4 pb-1 pt-1 text-xs text-gray-400">
                    <?= htmlspecialchars($t->t('dialog.picker.select_hint'), ENT_QUOTES) ?>
                </p>

                <!-- Action buttons (disabled at shares level) -->
                <div class="px-5 py-4 border-t border-gray-100 flex gap-2 justify-end flex-shrink-0">
                    <button @click="showPickerDialog = false"
                            class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition">
                        <?= htmlspecialchars($t->t('dialog.picker.cancel'), ENT_QUOTES) ?>
                    </button>
                    <button @click="executeFromPicker('copy')"
                            :disabled="pickerShare === ''"
                            :class="pickerShare === '' ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-50'"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg transition">
                        <?= htmlspecialchars($t->t('dialog.picker.copy_here'), ENT_QUOTES) ?>
                    </button>
                    <button @click="executeFromPicker('move')"
                            :disabled="pickerShare === ''"
                            :class="pickerShare === '' ? 'opacity-40 cursor-not-allowed' : 'hover:bg-blue-700'"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg transition">
                        <?= htmlspecialchars($t->t('dialog.picker.move_here'), ENT_QUOTES) ?>
                    </button>
                </div>
            </div>

            <!-- Progress (shown while executing) -->
            <div x-show="pickerExecuting" class="flex flex-col items-center justify-center py-12 px-6 gap-4">
                <svg class="animate-spin w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <p class="text-sm text-gray-600 text-center font-medium"
                   x-text="'<?= addcslashes($t->t('dialog.picker.executing'), "\\'") ?>'"></p>
                <p class="text-xs text-gray-400 text-center truncate max-w-full" x-text="pickerExecuteStatus"></p>
            </div>
        </div>
    </div>

<!-- ── Add Server Modal ──────────────────────────────────────────────────── -->
<div x-show="showAddServerModal" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
     @keydown.escape.window="showAddServerModal = false">
    <div @click.stop
         class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">
                <?= htmlspecialchars($t->t('server.add_title'), ENT_QUOTES) ?>
            </h2>
            <button @click="showAddServerModal = false"
                    class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <!-- Body -->
        <div class="px-6 py-5 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <?= htmlspecialchars($t->t('server.host'), ENT_QUOTES) ?> *
                </label>
                <input type="text" x-model="addServerHost"
                       @keydown.enter="addServer()"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"/>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <?= htmlspecialchars($t->t('server.display_name'), ENT_QUOTES) ?>
                </label>
                <input type="text" x-model="addServerDisplayName"
                       @keydown.enter="addServer()"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"/>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <?= htmlspecialchars($t->t('server.domain'), ENT_QUOTES) ?>
                </label>
                <input type="text" x-model="addServerDomain"
                       @keydown.enter="addServer()"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"/>
            </div>
            <template x-if="addServerError">
                <p class="text-sm text-red-600" x-text="addServerError"></p>
            </template>
        </div>
        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-100 flex gap-2 justify-end">
            <button @click="showAddServerModal = false"
                    class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition">
                <?= htmlspecialchars($t->t('dialog.rename.cancel'), ENT_QUOTES) ?>
            </button>
            <button @click="addServer()"
                    :disabled="addServerLoading || !addServerHost.trim()"
                    :class="(addServerLoading || !addServerHost.trim()) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-700'"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg transition">
                <span x-show="!addServerLoading"><?= htmlspecialchars($t->t('server.add'), ENT_QUOTES) ?></span>
                <span x-show="addServerLoading"><?= htmlspecialchars($t->t('server.connecting'), ENT_QUOTES) ?></span>
            </button>
        </div>
    </div>
</div>
</div>

<script>
const i18n = {
    errorLoadShares:    '<?= addcslashes($t->t('js.error.load_shares'),    "\\'") ?>',
    errorLoadFiles:     '<?= addcslashes($t->t('js.error.load_files'),     "\\'") ?>',
    errorChunkUpload:   '<?= addcslashes($t->t('js.error.chunk_upload'),   "\\'") ?>',
    errorDownloadZip:   '<?= addcslashes($t->t('js.error.download_zip'),   "\\'") ?>',
    errorRename:        '<?= addcslashes($t->t('js.error.rename'),         "\\'") ?>',
    errorMove:          '<?= addcslashes($t->t('js.error.move'),           "\\'") ?>',
    errorCopy:          '<?= addcslashes($t->t('js.error.copy'),           "\\'") ?>',
    errorMkdir:         '<?= addcslashes($t->t('js.error.mkdir'),          "\\'") ?>',
    errorDeletePartial:    '<?= addcslashes($t->t('js.error.delete_partial'),    "\\'") ?>',
    errorOperationPartial: '<?= addcslashes($t->t('js.error.operation_partial'), "\\'") ?>',
    errorAddServer:     '<?= addcslashes($t->t('js.error.add_server'),     "\\'") ?>',
    errorCredentials:   '<?= addcslashes($t->t('js.error.credentials'),    "\\'") ?>',
    serverConnectFailed: '<?= addcslashes($t->t('server.connect_failed'),  "\\'") ?>',
    serverAlreadyAdded: '<?= addcslashes($t->t('server.already_added'),    "\\'") ?>',
    uploadSuccess:        '<?= addcslashes($t->t('upload.success'),          "\\'") ?>',
    uploadFailed:         '<?= addcslashes($t->t('upload.failed'),           "\\'") ?>',
    uploadCancelled:      '<?= addcslashes($t->t('upload.cancelled'),        "\\'") ?>',
    uploadPanelTitle:     '<?= addcslashes($t->t('upload.panel.title'),      "\\'") ?>',
    uploadPanelClear:     '<?= addcslashes($t->t('upload.panel.clear'),      "\\'") ?>',
    uploadStatusPending:  '<?= addcslashes($t->t('upload.status.pending'),   "\\'") ?>',
    uploadStatusDone:     '<?= addcslashes($t->t('upload.status.done'),      "\\'") ?>',
    uploadStatusFailed:   '<?= addcslashes($t->t('upload.status.failed'),    "\\'") ?>',
    uploadStatusCancelled:'<?= addcslashes($t->t('upload.status.cancelled'), "\\'") ?>',
};

function fileBrowser() {
    return {
        servers: [],
        loadingShares: true,
        sidebarOpen: false,
        currentShare: null,
        currentServer: null,

        showAddServerModal: false,
        addServerHost: '',
        addServerDisplayName: '',
        addServerDomain: '',
        addServerError: '',
        addServerLoading: false,
        currentPath: '',
        files: [],
        loadingFiles: false,
        dragOver: false,
        uploadQueue: [],
        uploadWorkerRunning: false,
        uploadNextId: 0,
        showUploadPanel: false,
        uploadPanelCollapsed: false,
        chunkSize: <?= (int) ($chunkSize ?? 10485760) ?>,
        flash: null,
        flashType: 'success',
        flashTimer: null,

        sortColumn: 'name',
        sortDir: 'asc',

        showRenameDialog: false,
        renameTarget: null,
        renameValue: '',

        selected: [],

        showDeleteDialog: false,
        deleteMessage: '<?= addcslashes($t->t('dialog.delete.message'), "\\'") ?>',
        deleteMessageMulti: '<?= addcslashes($t->t('dialog.delete.message_multi'), "\\'") ?>',

        showMkdirDialog: false,
        mkdirValue: '',

        showPickerDialog: false,
        pickerShare: '',
        pickerPath: '',
        pickerShares: [],
        pickerFolders: [],
        pickerLoading: false,
        pickerExecuting: false,
        pickerExecuteStatus: '',

        get sortedFiles() {
            return [...this.files].sort((a, b) => {
                if (a.type !== b.type) return a.type === 'dir' ? -1 : 1;
                let cmp = 0;
                if (this.sortColumn === 'name') {
                    cmp = a.name.localeCompare(b.name, undefined, { sensitivity: 'base', numeric: true });
                } else if (this.sortColumn === 'size') {
                    cmp = (a.size ?? 0) - (b.size ?? 0);
                } else if (this.sortColumn === 'modified') {
                    cmp = (a.modified ?? 0) - (b.modified ?? 0);
                }
                return this.sortDir === 'asc' ? cmp : -cmp;
            });
        },

        get allSelected() {
            return this.sortedFiles.length > 0 && this.selected.length === this.sortedFiles.length;
        },
        get selectedFiles() {
            return this.sortedFiles.filter(f => this.selected.includes(f.path));
        },
        get singleSelected() {
            return this.selectedFiles.length === 1 ? this.selectedFiles[0] : null;
        },
        get selectionIsAllFiles() {
            return this.selectedFiles.length > 0 && this.selectedFiles.every(f => f.type !== 'dir');
        },

        get pickerBreadcrumbs() {
            if (!this.pickerShare) return [];
            const crumbs = [{ name: this.pickerShare, path: '', isShare: true }];
            if (this.pickerPath) {
                const parts = this.pickerPath.split('/').filter(Boolean);
                parts.forEach((name, i) => crumbs.push({
                    name,
                    path: parts.slice(0, i + 1).join('/'),
                    isShare: false,
                }));
            }
            return crumbs;
        },

        get breadcrumbs() {
            if (!this.currentPath) return [];
            const parts = this.currentPath.split('/').filter(Boolean);
            return parts.map((name, i) => ({
                name,
                path: parts.slice(0, i + 1).join('/'),
            }));
        },

        async init() {
            await this.loadShares();
            window.addEventListener('beforeunload', (e) => {
                if (this.uploadWorkerRunning) {
                    e.preventDefault();
                }
            });
        },

        async loadShares() {
            this.loadingShares = true;
            try {
                const res  = await fetch('/api/shares');
                const data = await res.json();
                const incoming = data.servers ?? [];
                // Merge: preserve expanded/credential state for existing servers
                this.servers = incoming.map(srv => {
                    const existing = this.servers.find(s => s.id === srv.id);
                    return {
                        ...srv,
                        expanded:    existing ? existing.expanded    : true,
                        _credUser:   existing ? existing._credUser   : '',
                        _credPass:   existing ? existing._credPass   : '',
                        _credError:  existing ? existing._credError  : '',
                        _credLoading: existing ? existing._credLoading : false,
                    };
                });
            } catch (e) {
                this.showFlash(i18n.errorLoadShares, 'error');
            } finally {
                this.loadingShares = false;
            }
        },

        async openShare(name, serverHost) {
            this.currentShare  = name;
            this.currentServer = serverHost ?? null;
            this.currentPath   = '';
            await this.loadFiles();
        },

        async navigate(path) {
            this.currentPath = path;
            await this.loadFiles();
        },

        async loadFiles() {
            if (!this.currentShare) return;
            this.loadingFiles = true;
            this.selected = [];
            try {
                const url = '/api/files?share=' + encodeURIComponent(this.currentShare)
                          + '&path='   + encodeURIComponent(this.currentPath)
                          + (this.currentServer ? '&server=' + encodeURIComponent(this.currentServer) : '');
                const res  = await fetch(url);
                const data = await res.json();
                this.files = data.files ?? [];
                if (data.sort?.column) {
                    this.sortColumn = data.sort.column;
                    this.sortDir    = data.sort.dir ?? 'asc';
                } else {
                    this.sortColumn = 'name';
                    this.sortDir    = 'asc';
                }
            } catch (e) {
                this.showFlash(i18n.errorLoadFiles, 'error');
            } finally {
                this.loadingFiles = false;
            }
        },

        async uploadFiles(event) {
            this.queueFiles(event.target.files);
            event.target.value = '';
        },

        async handleDrop(event) {
            this.dragOver = false;
            this.queueFiles(event.dataTransfer.files);
        },

        queueFiles(fileList) {
            if (!fileList || fileList.length === 0 || !this.currentShare) return;
            for (const file of fileList) {
                this.uploadQueue.push({
                    id:            ++this.uploadNextId,
                    name:          file.name,
                    file,
                    share:         this.currentShare,
                    server:        this.currentServer ?? '',
                    path:          this.currentPath,
                    status:        'pending',
                    progress:      0,
                    uploadedBytes: 0,
                    totalBytes:    file.size,
                    speed:         0,
                    finalizing:    false,
                    abortController: null,
                });
            }
            this.showUploadPanel      = true;
            this.uploadPanelCollapsed = false;
            if (!this.uploadWorkerRunning) {
                this.runUploadWorker();
            }
        },

        async runUploadWorker() {
            this.uploadWorkerRunning = true;
            let anyDone = false;
            while (true) {
                const item = this.uploadQueue.find(u => u.status === 'pending');
                if (!item) break;
                item.status = 'uploading';
                try {
                    await this.uploadChunkedItem(item);
                    if (item.status !== 'cancelled') { item.status = 'done'; anyDone = true; }
                } catch (e) {
                    if (item.status !== 'cancelled') item.status = 'failed';
                }
                // Refresh file list if still in the same folder
                if (item.share === this.currentShare && item.path === this.currentPath) {
                    await this.loadFiles();
                }
            }
            this.uploadWorkerRunning = false;
            if (anyDone) await this.loadShares();
        },

        cancelQueueItem(item) {
            if (item.status === 'pending') {
                item.status = 'cancelled';
            } else if (item.status === 'uploading') {
                item.status = 'cancelled';
                item.abortController?.abort();
            }
        },

        async uploadChunkedItem(item) {
            const file  = item.file;
            const total = Math.max(1, Math.ceil(file.size / this.chunkSize));
            for (let i = 0; i < total; i++) {
                if (item.status === 'cancelled') return;
                const start      = i * this.chunkSize;
                const end        = Math.min(start + this.chunkSize, file.size);
                const chunkBytes = end - start;
                const fd         = new FormData();
                fd.append('share',       item.share);
                fd.append('server',      item.server ?? '');
                fd.append('path',        item.path);
                fd.append('filename',    file.name);
                fd.append('chunk_index', i);
                fd.append('chunk_total', total);
                fd.append('chunk',       file.slice(start, end), file.name);

                item.abortController = new AbortController();
                // Mark last chunk as "finalizing" before sending – server will
                // write the assembled file to SMB which can take a while.
                if (i === total - 1) item.finalizing = true;

                const t0  = performance.now();
                const res = await fetch('/upload/chunk', {
                    method: 'POST',
                    body:   fd,
                    signal: item.abortController.signal,
                });
                const elapsed = (performance.now() - t0) / 1000;
                item.finalizing = false;
                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    throw new Error(data.error ?? i18n.errorChunkUpload);
                }
                const data = await res.json();
                if (data.error) throw new Error(data.error);

                // Exponential moving average for smooth speed display
                const rawSpeed = elapsed > 0 ? chunkBytes / elapsed : 0;
                item.speed     = item.speed > 0
                    ? 0.3 * rawSpeed + 0.7 * item.speed
                    : rawSpeed;

                item.uploadedBytes = end;
                item.progress      = Math.round((i + 1) / total * 100);
            }
        },

        setSort(column) {
            if (this.sortColumn === column) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortColumn = column;
                this.sortDir    = 'asc';
            }
            this.persistSortPref();
        },

        persistSortPref() {
            if (!this.currentShare) return;
            fetch('/api/sort-pref', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({
                    share:  this.currentShare,
                    server: this.currentServer ?? '',
                    path:   this.currentPath,
                    column: this.sortColumn,
                    dir:    this.sortDir,
                }),
            }).catch(() => {});
        },

        openFile(file) {
            const url = '/browser/download?share=' + encodeURIComponent(this.currentShare)
                      + '&path=' + encodeURIComponent(file.path)
                      + (this.currentServer ? '&server=' + encodeURIComponent(this.currentServer) : '');
            window.open(url, '_blank');
        },

        toggleSelect(path) {
            const i = this.selected.indexOf(path);
            if (i === -1) this.selected.push(path);
            else this.selected.splice(i, 1);
        },

        toggleSelectAll() {
            if (this.allSelected) {
                this.selected = [];
            } else {
                this.selected = this.sortedFiles.map(f => f.path);
            }
        },

        clearSelection() {
            this.selected = [];
        },

        downloadSelected() {
            if (!this.singleSelected || this.singleSelected.type === 'dir') return;
            const url = '/browser/download?share=' + encodeURIComponent(this.currentShare)
                      + '&path=' + encodeURIComponent(this.singleSelected.path)
                      + (this.currentServer ? '&server=' + encodeURIComponent(this.currentServer) : '');
            window.open(url, '_blank');
        },

        async downloadZip() {
            const paths = this.selectedFiles.filter(f => f.type !== 'dir').map(f => f.path);
            if (paths.length === 0) return;
            try {
                const res = await fetch('/browser/download-zip', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ share: this.currentShare, server: this.currentServer ?? '', paths }),
                });
                if (!res.ok) { this.showFlash(i18n.errorDownloadZip, 'error'); return; }
                const blob = await res.blob();
                const url  = URL.createObjectURL(blob);
                const a    = document.createElement('a');
                a.href = url; a.download = 'download.zip';
                document.body.appendChild(a); a.click();
                document.body.removeChild(a); URL.revokeObjectURL(url);
            } catch (e) {
                this.showFlash(i18n.errorDownloadZip, 'error');
            }
        },

        async openPicker() {
            this.pickerShare         = '';
            this.pickerPath          = '';
            this.pickerExecuting     = false;
            this.pickerExecuteStatus = '';
            this.showPickerDialog    = true;
            await this.loadPickerContent();
        },

        async loadPickerContent() {
            this.pickerLoading = true;
            try {
                if (this.pickerShare === '') {
                    const res  = await fetch('/api/shares');
                    const data = await res.json();
                    // Build flat share list from all servers for the picker
                    const allShares = [];
                    for (const srv of (data.servers ?? [])) {
                        for (const s of (srv.shares ?? [])) {
                            allShares.push({ ...s, _server: srv.host });
                        }
                    }
                    this.pickerShares  = allShares;
                    this.pickerFolders = [];
                    this._pickerServerMap = {};
                    for (const s of allShares) this._pickerServerMap[s.name] = s._server;
                } else {
                    const serverParam = this._pickerServerHost ?? '';
                    const res  = await fetch('/api/files?share=' + encodeURIComponent(this.pickerShare)
                                           + '&path='   + encodeURIComponent(this.pickerPath)
                                           + (serverParam ? '&server=' + encodeURIComponent(serverParam) : ''));
                    const data = await res.json();
                    this.pickerFolders = (data.files ?? []).filter(f => f.type === 'dir');
                    this.pickerShares  = [];
                }
            } catch (e) {
                this.pickerShares  = [];
                this.pickerFolders = [];
            } finally {
                this.pickerLoading = false;
            }
        },

        async pickerOpenShare(name, server) {
            this.pickerShare = name;
            this.pickerPath  = '';
            this._pickerServerHost = server ?? (this._pickerServerMap ?? {})[name] ?? this.currentServer ?? '';
            await this.loadPickerContent();
        },

        async pickerNavigate(path) {
            this.pickerPath = path;
            await this.loadPickerContent();
        },

        async pickerGoToShares() {
            this.pickerShare = '';
            this.pickerPath  = '';
            await this.loadPickerContent();
        },

        async executeFromPicker(operation) {
            this.pickerExecuting = true;
            const items    = [...this.selectedFiles];
            const destDir  = this.pickerPath;
            const endpoint = operation === 'move' ? '/file/move' : '/file/copy';
            const failed   = [];

            for (let i = 0; i < items.length; i++) {
                const item = items[i];
                this.pickerExecuteStatus = items.length > 1
                    ? item.name + ' (' + (i + 1) + '/' + items.length + ')'
                    : item.name;
                try {
                    const destShareParam = this.pickerShare !== this.currentShare
                        ? this.pickerShare : undefined;
                    const payload = operation === 'move'
                        ? { share: this.currentShare, server: this.currentServer ?? '', old_path: item.path, dest_dir: destDir, dest_share: destShareParam }
                        : { share: this.currentShare, server: this.currentServer ?? '', src_path: item.path, dest_dir: destDir, dest_share: destShareParam };
                    const res  = await fetch(endpoint, {
                        method:  'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body:    JSON.stringify(payload),
                    });
                    const data = await res.json();
                    if (!data.success) failed.push(item.name);
                } catch (e) {
                    failed.push(item.name);
                }
            }

            this.showPickerDialog    = false;
            this.pickerExecuting     = false;
            this.pickerExecuteStatus = '';

            if (failed.length > 0) {
                this.showFlash(i18n.errorOperationPartial.replace(':files', failed.join(', ')), 'error');
            }
            await this.loadFiles();
        },

        startRename(file) {
            this.renameTarget = file;
            this.renameValue  = file.name;
            this.showRenameDialog = true;
            this.$nextTick(() => this.$refs.renameInput?.focus());
        },

        async confirmRename() {
            if (!this.renameValue.trim() || !this.renameTarget) return;
            this.showRenameDialog = false;
            try {
                const res = await fetch('/file/rename', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        share:    this.currentShare,
                        server:   this.currentServer ?? '',
                        old_path: this.renameTarget.path,
                        new_name: this.renameValue.trim(),
                    }),
                });
                const data = await res.json();
                if (data.success) {
                    await this.loadFiles();
                } else {
                    this.showFlash(data.error ?? i18n.errorRename, 'error');
                }
            } catch (e) {
                this.showFlash(i18n.errorRename, 'error');
            }
        },

        async confirmDelete() {
            const filesToDelete = [...this.selectedFiles];
            this.showDeleteDialog = false;
            const failed = [];
            for (const file of filesToDelete) {
                try {
                    const res = await fetch('/file/delete', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ share: this.currentShare, server: this.currentServer ?? '', path: file.path }),
                    });
                    const data = await res.json();
                    if (!data.success) failed.push(file.name);
                } catch (e) {
                    failed.push(file.name);
                }
            }
            if (failed.length > 0) {
                this.showFlash(i18n.errorDeletePartial.replace(':files', failed.join(', ')), 'error');
            }
            await this.loadFiles();
        },

        async confirmMkdir() {
            if (!this.mkdirValue.trim()) return;
            this.showMkdirDialog = false;
            try {
                const res = await fetch('/file/mkdir', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        share:  this.currentShare,
                        server: this.currentServer ?? '',
                        path:   this.currentPath,
                        name:   this.mkdirValue.trim(),
                    }),
                });
                const data = await res.json();
                if (data.success) {
                    this.mkdirValue = '';
                    await this.loadFiles();
                } else {
                    this.showFlash(data.error ?? i18n.errorMkdir, 'error');
                }
            } catch (e) {
                this.showFlash(i18n.errorMkdir, 'error');
            }
        },

        // ── Server management ──────────────────────────────────────────────
        async addServer() {
            const host = this.addServerHost.trim();
            if (!host) return;
            this.addServerError   = '';
            this.addServerLoading = true;
            try {
                const res  = await fetch('/api/servers', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify({
                        host:         host,
                        display_name: this.addServerDisplayName.trim() || host,
                        domain:       this.addServerDomain.trim(),
                    }),
                });
                const data = await res.json();
                if (data.ok) {
                    this.showAddServerModal  = false;
                    this.addServerHost        = '';
                    this.addServerDisplayName = '';
                    this.addServerDomain      = '';
                    await this.loadShares();
                } else if (data.error === 'server_already_added') {
                    this.addServerError = i18n.serverAlreadyAdded;
                } else if (data.error === 'connection_failed') {
                    this.addServerError = i18n.serverConnectFailed;
                } else {
                    this.addServerError = data.error ?? i18n.errorAddServer;
                }
            } catch (e) {
                this.addServerError = i18n.errorAddServer;
            } finally {
                this.addServerLoading = false;
            }
        },

        confirmRemoveServer(srv) {
            const name = srv.display_name ?? srv.host;
            const msg  = '<?= addcslashes($t->t('server.remove_confirm'), "\\'") ?>'.replace(':name', name);
            if (!confirm(msg)) return;
            this.removeServer(srv.id);
        },

        async removeServer(serverId) {
            try {
                const res = await fetch('/api/servers/' + encodeURIComponent(serverId), {
                    method: 'DELETE',
                });
                const data = await res.json();
                if (data.ok) {
                    if (this.servers.find(s => s.id === serverId && s.host === this.currentServer)) {
                        this.currentShare  = null;
                        this.currentServer = null;
                        this.files         = [];
                    }
                    await this.loadShares();
                }
            } catch (e) {
                this.showFlash(i18n.errorAddServer, 'error');
            }
        },

        async submitServerCredentials(srv) {
            srv._credError   = '';
            srv._credLoading = true;
            try {
                const res  = await fetch('/api/servers/' + encodeURIComponent(srv.id) + '/credentials', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify({ username: srv._credUser, password: srv._credPass }),
                });
                const data = await res.json();
                if (data.ok) {
                    srv._credUser = '';
                    srv._credPass = '';
                    await this.loadShares();
                } else if (data.error === 'connection_failed') {
                    srv._credError = i18n.serverConnectFailed;
                } else {
                    srv._credError = data.error ?? i18n.errorCredentials;
                }
            } catch (e) {
                srv._credError = i18n.errorCredentials;
            } finally {
                srv._credLoading = false;
            }
        },

        showFlash(msg, type = 'success') {
            this.flash     = msg;
            this.flashType = type;
            clearTimeout(this.flashTimer);
            this.flashTimer = setTimeout(() => { this.flash = null; }, 4000);
        },

        formatSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        },

        formatSpeed(bytesPerSec) {
            const mbits = bytesPerSec * 8 / 1_000_000;
            if (mbits < 1) return (bytesPerSec * 8 / 1000).toFixed(0) + ' Kbit/s';
            return mbits.toFixed(1) + ' Mbit/s';
        },

        formatDate(ts) {
            if (!ts) return '—';
            return new Date(ts * 1000).toLocaleString(undefined, {
                year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit',
            });
        },
    };
}
</script>

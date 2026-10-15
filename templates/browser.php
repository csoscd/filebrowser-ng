<div
    x-data="fileBrowser()"
    x-init="init()"
    class="flex flex-col h-screen"
>
    <!-- ── Top navigation ──────────────────────────────────────────────────── -->
    <header class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between shadow-sm flex-shrink-0">
        <div class="flex items-center gap-3">
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

    <div class="flex flex-1 overflow-hidden">
        <!-- ── Sidebar: shares ─────────────────────────────────────────────── -->
        <aside class="w-56 bg-white border-r border-gray-200 flex flex-col flex-shrink-0 overflow-y-auto">
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

            <nav class="flex-1 px-2 pb-4">
                <template x-for="share in shares" :key="share.name">
                    <button
                        @click="openShare(share.name)"
                        :class="currentShare === share.name
                            ? 'bg-blue-50 text-blue-700 font-medium'
                            : 'text-gray-700 hover:bg-gray-100'"
                        class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition mb-0.5"
                    >
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 7a2 2 0 012-2h3.586a1 1 0 01.707.293L11 7h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                        </svg>
                        <span x-text="share.name" class="truncate"></span>
                    </button>
                </template>
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
                            <label class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-blue-600 text-white
                                          rounded-lg hover:bg-blue-700 transition font-medium cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                <span class="hidden sm:inline"><?= htmlspecialchars($t->t('browser.upload'), ENT_QUOTES) ?></span>
                                <input type="file" multiple class="hidden" @change="uploadFiles($event)">
                            </label>
                        </div>
                    </div>

                    <!-- Upload progress -->
                    <template x-if="uploading">
                        <div class="bg-blue-50 border-b border-blue-200 px-4 py-2 flex-shrink-0">
                            <div class="flex items-center justify-between text-sm text-blue-700 mb-1.5">
                                <span class="truncate mr-2 min-w-0">
                                    <span x-show="uploadFileCount > 1"
                                          x-text="'(' + uploadFileIndex + '/' + uploadFileCount + ') '"></span>
                                    <span x-text="uploadCurrent"></span>
                                </span>
                                <span class="flex-shrink-0 font-medium tabular-nums" x-text="uploadProgress + '%'"></span>
                            </div>
                            <div class="w-full bg-blue-200 rounded-full h-1.5">
                                <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-150"
                                     :style="'width: ' + uploadProgress + '%'"></div>
                            </div>
                        </div>
                    </template>

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

                            <!-- Move/Copy (single item) -->
                            <template x-if="singleSelected">
                                <button @click="startMoveCopy(singleSelected)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                    <?= htmlspecialchars($t->t('browser.move_copy'), ENT_QUOTES) ?>
                                </button>
                            </template>

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
    <!-- ── Modal: Move / Copy ──────────────────────────────────────────────── -->
    <div x-show="showMoveCopyDialog" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
         @keydown.escape.window="showMoveCopyDialog = false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6" @click.stop>
            <h2 class="text-lg font-semibold mb-1">
                <?= htmlspecialchars($t->t('dialog.movecopy.title'), ENT_QUOTES) ?>
            </h2>
            <p class="text-sm text-gray-500 mb-4 truncate" x-text="moveCopyTarget?.name ?? ''"></p>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                <?= htmlspecialchars($t->t('dialog.movecopy.label'), ENT_QUOTES) ?>
            </label>
            <input type="text" x-model="moveCopyDestDir"
                   x-ref="moveCopyInput"
                   placeholder="<?= htmlspecialchars($t->t('browser.shares'), ENT_QUOTES) ?> root"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-500 mb-2">
            <p class="text-xs text-gray-400 mb-5"
               x-text="moveCopyTarget?.type === 'dir'
                   ? '<?= addcslashes($t->t('dialog.movecopy.hint_dir'), "\\'") ?>'
                   : '<?= addcslashes($t->t('dialog.movecopy.hint'), "\\'") ?>'">
            </p>
            <div class="flex justify-end gap-2">
                <button @click="showMoveCopyDialog = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    <?= htmlspecialchars($t->t('dialog.movecopy.cancel'), ENT_QUOTES) ?>
                </button>
                <button @click="confirmCopy()"
                        x-show="moveCopyTarget?.type !== 'dir'"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    <?= htmlspecialchars($t->t('dialog.movecopy.copy'), ENT_QUOTES) ?>
                </button>
                <button @click="confirmMove()"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
                    <?= htmlspecialchars($t->t('dialog.movecopy.move'), ENT_QUOTES) ?>
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
    errorDeletePartial: '<?= addcslashes($t->t('js.error.delete_partial'), "\\'") ?>',
    uploadSuccess:      '<?= addcslashes($t->t('upload.success'),          "\\'") ?>',
    uploadFailed:       '<?= addcslashes($t->t('upload.failed'),           "\\'") ?>',
};

function fileBrowser() {
    return {
        shares: [],
        loadingShares: true,
        currentShare: null,
        currentPath: '',
        files: [],
        loadingFiles: false,
        dragOver: false,
        uploading: false,
        uploadProgress: 0,
        uploadCurrent: '',
        uploadFileIndex: 0,
        uploadFileCount: 0,
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

        showMoveCopyDialog: false,
        moveCopyTarget: null,
        moveCopyDestDir: '',

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
        },

        async loadShares() {
            this.loadingShares = true;
            try {
                const res = await fetch('/api/shares');
                const data = await res.json();
                this.shares = data.auto ?? [];
            } catch (e) {
                this.showFlash(i18n.errorLoadShares, 'error');
            } finally {
                this.loadingShares = false;
            }
        },

        async openShare(name) {
            this.currentShare = name;
            this.currentPath  = '';
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
                          + '&path=' + encodeURIComponent(this.currentPath);
                const res  = await fetch(url);
                const data = await res.json();
                this.files = data.files ?? [];
            } catch (e) {
                this.showFlash(i18n.errorLoadFiles, 'error');
            } finally {
                this.loadingFiles = false;
            }
        },

        async uploadFiles(event) {
            await this.doUpload(event.target.files);
            event.target.value = '';
        },

        async handleDrop(event) {
            this.dragOver = false;
            await this.doUpload(event.dataTransfer.files);
        },

        async doUpload(fileList) {
            if (!fileList || fileList.length === 0 || !this.currentShare) return;
            this.uploading      = true;
            this.uploadFileCount = fileList.length;
            const uploaded = [], failed = [];

            for (let i = 0; i < fileList.length; i++) {
                const file = fileList[i];
                this.uploadFileIndex = i + 1;
                this.uploadCurrent   = file.name;
                this.uploadProgress  = 0;
                try {
                    await this.uploadChunked(file);
                    uploaded.push(file.name);
                } catch (e) {
                    failed.push(file.name);
                }
            }

            this.uploading = false;
            if (uploaded.length > 0) {
                this.showFlash(i18n.uploadSuccess.replace(':count', uploaded.length), 'success');
            }
            if (failed.length > 0) {
                this.showFlash(i18n.uploadFailed.replace(':files', failed.join(', ')), 'error');
            }
            await this.loadFiles();
        },

        async uploadChunked(file) {
            const total = Math.max(1, Math.ceil(file.size / this.chunkSize));
            for (let i = 0; i < total; i++) {
                const start = i * this.chunkSize;
                const end   = Math.min(start + this.chunkSize, file.size);
                const fd    = new FormData();
                fd.append('share',       this.currentShare);
                fd.append('path',        this.currentPath);
                fd.append('filename',    file.name);
                fd.append('chunk_index', i);
                fd.append('chunk_total', total);
                fd.append('chunk',       file.slice(start, end), file.name);

                const res = await fetch('/upload/chunk', { method: 'POST', body: fd });
                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    throw new Error(data.error ?? i18n.errorChunkUpload);
                }
                const data = await res.json();
                if (data.error) throw new Error(data.error);

                this.uploadProgress = Math.round((i + 1) / total * 100);
            }
        },

        setSort(column) {
            if (this.sortColumn === column) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortColumn = column;
                this.sortDir    = 'asc';
            }
        },

        openFile(file) {
            const url = '/browser/download?share=' + encodeURIComponent(this.currentShare)
                      + '&path=' + encodeURIComponent(file.path);
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
                      + '&path=' + encodeURIComponent(this.singleSelected.path);
            window.open(url, '_blank');
        },

        async downloadZip() {
            const paths = this.selectedFiles.filter(f => f.type !== 'dir').map(f => f.path);
            if (paths.length === 0) return;
            try {
                const res = await fetch('/browser/download-zip', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ share: this.currentShare, paths }),
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

        startMoveCopy(file) {
            this.moveCopyTarget  = file;
            this.moveCopyDestDir = this.currentPath;
            this.showMoveCopyDialog = true;
            this.$nextTick(() => this.$refs.moveCopyInput?.focus());
        },

        async confirmMove() {
            if (!this.moveCopyTarget) return;
            this.showMoveCopyDialog = false;
            try {
                const res = await fetch('/file/move', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        share:    this.currentShare,
                        old_path: this.moveCopyTarget.path,
                        dest_dir: this.moveCopyDestDir.trim(),
                    }),
                });
                const data = await res.json();
                if (data.success) {
                    await this.loadFiles();
                } else {
                    this.showFlash(data.error ?? i18n.errorMove, 'error');
                }
            } catch (e) {
                this.showFlash(i18n.errorMove, 'error');
            }
        },

        async confirmCopy() {
            if (!this.moveCopyTarget) return;
            this.showMoveCopyDialog = false;
            try {
                const res = await fetch('/file/copy', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        share:    this.currentShare,
                        src_path: this.moveCopyTarget.path,
                        dest_dir: this.moveCopyDestDir.trim(),
                    }),
                });
                const data = await res.json();
                if (data.success) {
                    await this.loadFiles();
                } else {
                    this.showFlash(data.error ?? i18n.errorCopy, 'error');
                }
            } catch (e) {
                this.showFlash(i18n.errorCopy, 'error');
            }
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
                        body: JSON.stringify({ share: this.currentShare, path: file.path }),
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
                        share: this.currentShare,
                        path:  this.currentPath,
                        name:  this.mkdirValue.trim(),
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

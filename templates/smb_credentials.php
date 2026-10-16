<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <!-- Logo / Title -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-600 mb-4 shadow-lg">
                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 7a2 2 0 012-2h3.586a1 1 0 01.707.293L11 7h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($t->t('app.name'), ENT_QUOTES) ?></h1>
            <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($t->t('login.smb.subtitle'), ENT_QUOTES) ?></p>
        </div>

        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-md p-8">
            <!-- Signed-in identity -->
            <div class="mb-5 flex items-center gap-3 px-3 py-2.5 rounded-lg bg-blue-50 border border-blue-100">
                <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span class="text-sm text-blue-700 font-medium"><?= htmlspecialchars($username ?? '', ENT_QUOTES) ?></span>
            </div>

            <?php if (!empty($error)): ?>
            <div class="mb-5 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                <?= htmlspecialchars($t->t('login.smb.error.' . $error), ENT_QUOTES) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="/auth/smb" class="space-y-5">
                <div>
                    <label for="smb_password" class="block text-sm font-medium text-gray-700 mb-1">
                        <?= htmlspecialchars($t->t('login.smb.password'), ENT_QUOTES) ?>
                    </label>
                    <input
                        type="password"
                        id="smb_password"
                        name="smb_password"
                        required
                        autofocus
                        autocomplete="current-password"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                               text-sm transition"
                    >
                </div>

                <?php if ($can_save ?? false): ?>
                <div class="flex items-start gap-3">
                    <input
                        type="checkbox"
                        id="save_credentials"
                        name="save_credentials"
                        value="1"
                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600
                               focus:ring-blue-500 cursor-pointer"
                    >
                    <label for="save_credentials" class="text-sm text-gray-600 cursor-pointer leading-5">
                        <?= htmlspecialchars($t->t('login.smb.save_credentials'), ENT_QUOTES) ?>
                    </label>
                </div>
                <?php else: ?>
                <div class="flex items-start gap-3 opacity-50 cursor-not-allowed">
                    <input
                        type="checkbox"
                        disabled
                        class="mt-0.5 h-4 w-4 rounded border-gray-300"
                    >
                    <span class="text-sm text-gray-500 leading-5">
                        <?= htmlspecialchars($t->t('login.smb.save_disabled'), ENT_QUOTES) ?>
                    </span>
                </div>
                <?php endif; ?>

                <button
                    type="submit"
                    class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold
                           rounded-lg shadow-sm transition text-sm focus:outline-none focus:ring-2
                           focus:ring-blue-500 focus:ring-offset-2"
                >
                    <?= htmlspecialchars($t->t('login.smb.submit'), ENT_QUOTES) ?>
                </button>
            </form>
        </div>
    </div>
</div>

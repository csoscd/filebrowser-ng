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
        </div>

        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-md p-8">
            <?php if (!empty($error)): ?>
            <div class="mb-5 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                <?= htmlspecialchars($t->t('login.error.' . $error), ENT_QUOTES) ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($oidc_enabled)): ?>
            <!-- OIDC login button -->
            <form method="POST" action="/auth/oidc" class="mb-5">
                <button
                    type="submit"
                    class="w-full flex items-center justify-center gap-2 py-2.5 px-4
                           bg-blue-600 hover:bg-blue-700 text-white font-semibold
                           rounded-lg shadow-sm transition text-sm focus:outline-none focus:ring-2
                           focus:ring-blue-500 focus:ring-offset-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    <?= htmlspecialchars($t->t('login.oidc.submit'), ENT_QUOTES) ?>
                </button>
            </form>

            <!-- Divider -->
            <div class="relative mb-5">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-200"></div>
                </div>
                <div class="relative flex justify-center text-xs text-gray-400 uppercase tracking-wide">
                    <span class="bg-white px-2"><?= htmlspecialchars($t->t('login.or'), ENT_QUOTES) ?></span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Classic SMB login (always shown) -->
            <form method="POST" action="/auth/login" class="space-y-5">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                        <?= htmlspecialchars($t->t('login.username'), ENT_QUOTES) ?>
                    </label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        required
                        <?= empty($oidc_enabled) ? 'autofocus' : '' ?>
                        autocomplete="username"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                               text-sm transition"
                    >
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        <?= htmlspecialchars($t->t('login.password'), ENT_QUOTES) ?>
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                               text-sm transition"
                    >
                </div>

                <button
                    type="submit"
                    class="w-full py-2.5 px-4 <?= !empty($oidc_enabled) ? 'bg-gray-100 hover:bg-gray-200 text-gray-700' : 'bg-blue-600 hover:bg-blue-700 text-white' ?> font-semibold
                           rounded-lg shadow-sm transition text-sm focus:outline-none focus:ring-2
                           focus:ring-blue-500 focus:ring-offset-2"
                >
                    <?= htmlspecialchars($t->t('login.submit'), ENT_QUOTES) ?>
                </button>
            </form>
        </div>
    </div>
</div>

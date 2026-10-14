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
                        autofocus
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
                    class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold
                           rounded-lg shadow-sm transition text-sm focus:outline-none focus:ring-2
                           focus:ring-blue-500 focus:ring-offset-2"
                >
                    <?= htmlspecialchars($t->t('login.submit'), ENT_QUOTES) ?>
                </button>
            </form>
        </div>
    </div>
</div>

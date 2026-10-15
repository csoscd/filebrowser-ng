<!DOCTYPE html>
<html lang="<?= htmlspecialchars($t->getLanguage(), ENT_QUOTES) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($t->t('app.name'), ENT_QUOTES) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .drop-active { @apply border-blue-500 bg-blue-50; }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 min-h-screen antialiased flex flex-col">
<div class="flex-1">
<?= $content ?>
</div>
<footer class="py-2 px-4 flex items-center justify-between">
    <span class="text-xs text-gray-400">filebrowser-ng v<?= htmlspecialchars($appVersion, ENT_QUOTES) ?></span>
    <div class="flex items-center gap-0.5">
        <?php foreach (['en', 'de', 'fr'] as $lang): ?>
        <a href="/lang/<?= $lang ?>"
           class="text-xs px-2 py-0.5 rounded font-medium transition
                  <?= $t->getLanguage() === $lang
                      ? 'bg-blue-100 text-blue-700'
                      : 'text-gray-400 hover:text-gray-600 hover:bg-gray-100' ?>">
            <?= strtoupper($lang) ?>
        </a>
        <?php endforeach ?>
    </div>
</footer>
</body>
</html>

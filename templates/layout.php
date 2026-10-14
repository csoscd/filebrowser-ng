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
<body class="bg-gray-100 text-gray-900 min-h-screen antialiased">
<?= $content ?>
</body>
</html>

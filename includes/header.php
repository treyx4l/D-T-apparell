<?php

declare(strict_types=1);

/** @var string $pageTitle */
/** @var string|null $activeNav */
$pageTitle = $pageTitle ?? APP_NAME;
$activeNav = $activeNav ?? '';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="<?= favicon_url() ?>">
  <title><?= escape_output($pageTitle) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <script>
    window.DT_APPARELS = <?= json_encode([
        'baseUrl' => url(),
        'csrfToken' => csrf_token(),
        'supabase' => supabase_public_config(),
        'whatsappNumber' => WHATSAPP_NUMBER,
        'storageBucket' => STORAGE_BUCKET,
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
  </script>
  <?php require __DIR__ . '/oauth-recovery.php'; ?>
</head>
<body class="bg-white text-ink antialiased dark:bg-gray-950 dark:text-gray-100 min-h-screen flex flex-col">
  <nav class="sticky top-0 z-50 border-b border-gray-100 bg-white/95 backdrop-blur dark:border-gray-800 dark:bg-gray-950/95">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
      <a href="<?= url('index.php') ?>" class="flex items-center gap-3 group">
        <img src="<?= logo_url() ?>" alt="<?= escape_output(APP_NAME) ?>" class="h-10 w-10 rounded-xl object-contain shadow-sm" width="40" height="40">
        <span class="hidden sm:block text-lg font-semibold tracking-tight"><?= escape_output(APP_NAME) ?></span>
      </a>
      <div class="flex items-center gap-2 sm:gap-4">
        <a href="<?= url('store.php') ?>" class="nav-link <?= $activeNav === 'store' ? 'nav-link-active' : '' ?>">
          <i class="fas fa-store"></i><span class="hidden sm:inline ml-2">Store</span>
        </a>

        <button type="button" onclick="toggleTheme()" class="icon-btn" aria-label="Toggle theme">
          <i class="fas fa-moon dark:hidden"></i>
          <i class="fas fa-sun hidden dark:inline"></i>
        </button>
      </div>
    </div>
  </nav>
  <main class="flex-1">

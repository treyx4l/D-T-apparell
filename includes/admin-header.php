<?php

declare(strict_types=1);

/** @var string $pageTitle */
/** @var string|null $activeAdminNav */
$pageTitle = $pageTitle ?? 'Admin | ' . APP_NAME;
$activeAdminNav = $activeAdminNav ?? '';
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
        'isAdmin' => is_admin(),
        'accessToken' => $_SESSION['access_token'] ?? '',
        'refreshToken' => $_SESSION['refresh_token'] ?? '',
        'storageBucket' => STORAGE_BUCKET,
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
  </script>
  <?php require __DIR__ . '/oauth-recovery.php'; ?>
</head>
<body class="bg-gray-50 text-ink antialiased dark:bg-gray-950 dark:text-gray-100 min-h-screen flex flex-col">
  <nav class="sticky top-0 z-50 border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
      <a href="<?= url(ADMIN_PATH . '/dashboard.php') ?>" class="flex items-center gap-3">
        <img src="<?= logo_url() ?>" alt="D&T Admin" class="h-10 w-10 rounded-xl object-contain" width="40" height="40">
        <span class="font-semibold">D&T Admin</span>
      </a>
      <div class="flex items-center gap-2 sm:gap-4">
        <a href="<?= url(ADMIN_PATH . '/dashboard.php') ?>" class="nav-link <?= $activeAdminNav === 'products' ? 'nav-link-active' : '' ?>">
          <i class="fas fa-box"></i><span class="hidden sm:inline ml-2">Products</span>
        </a>
        <a href="<?= url(ADMIN_PATH . '/orders.php') ?>" class="nav-link <?= $activeAdminNav === 'orders' ? 'nav-link-active' : '' ?>">
          <i class="fas fa-shopping-cart"></i><span class="hidden sm:inline ml-2">Orders</span>
        </a>
        <button type="button" onclick="toggleTheme()" class="icon-btn" aria-label="Toggle theme">
          <i class="fas fa-moon dark:hidden"></i>
          <i class="fas fa-sun hidden dark:inline"></i>
        </button>
        <a href="<?= url(ADMIN_PATH . '/logout.php') ?>" class="nav-link text-red-600 dark:text-red-400">
          <i class="fas fa-sign-out-alt"></i><span class="hidden sm:inline ml-2">Logout</span>
        </a>
      </div>
    </div>
  </nav>
  <main class="flex-1">

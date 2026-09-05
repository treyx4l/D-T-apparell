<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/config.php';

redirect_if_admin_authenticated();

$error = sanitize_input($_GET['error'] ?? '');
$pageTitle = 'Admin Login — ' . APP_NAME;
$extraScripts = ['js/auth.js'];
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="<?= favicon_url() ?>">
  <title><?= escape_output($pageTitle) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <script>
    window.DT_APPARELS = <?= json_encode([
        'baseUrl' => url(),
        'csrfToken' => csrf_token(),
        'supabase' => supabase_public_config(),
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
  </script>
</head>
<body class="flex min-h-screen items-center justify-center bg-gray-50 px-4 dark:bg-gray-950">
  <button type="button" onclick="toggleTheme()" class="icon-btn fixed right-4 top-4"><i class="fas fa-moon dark:hidden"></i><i class="fas fa-sun hidden dark:inline"></i></button>

  <div class="w-full max-w-md">
    <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900">
      <div class="mb-8 text-center">
        <img src="<?= logo_url() ?>" alt="D&T Admin" class="mx-auto mb-4 h-14 w-14 rounded-xl object-contain">
        <h1 class="text-2xl font-bold">Admin Access</h1>
        <p class="mt-2 text-sm text-muted">Sign in with email or Google</p>
      </div>

      <?php if ($error === 'access_denied'): ?>
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300">
          <i class="fas fa-exclamation-circle mr-1"></i> Access denied. This account does not have admin privileges.
        </div>
      <?php endif; ?>

      <div id="authAlert" class="hidden" role="alert"></div>

      <form onsubmit="handleAdminLogin(event)" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= escape_output(csrf_token()) ?>">
        <div>
          <label class="label-field" for="email">Email</label>
          <input type="email" id="email" class="input-field" placeholder="admin@example.com" required autocomplete="email">
        </div>
        <div>
          <label class="label-field" for="password">Password</label>
          <input type="password" id="password" class="input-field" placeholder="Password" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn-primary w-full"><i class="fas fa-sign-in-alt"></i> Sign In</button>
      </form>

      <div class="my-6 flex items-center gap-4">
        <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
        <span class="text-xs text-muted">OR</span>
        <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
      </div>

      <button type="button" onclick="handleAdminGoogleAuth()" class="btn-secondary w-full">
        <i class="fab fa-google"></i> Continue with Google
      </button>

      <p class="mt-6 text-center text-xs text-muted">
        <i class="fas fa-shield-alt mr-1"></i> Authorized admin accounts only
      </p>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
  <script src="<?= asset('js/supabase-client.js') ?>"></script>
  <script src="<?= asset('js/theme.js') ?>"></script>
  <script src="<?= asset('js/auth.js') ?>"></script>
</body>
</html>

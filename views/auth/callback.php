<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/config.php';

$pageTitle = 'Signing in... — ' . APP_NAME;
$next = sanitize_input($_GET['next'] ?? '/index.php');
$isAdminLogin = ($_GET['admin'] ?? '') === '1';
if (!str_starts_with($next, '/')) {
    $next = '/index.php';
}

$checkEmailUrl = url('auth/check-email.php');
$extraScripts = ['js/auth-callback.js'];

require __DIR__ . '/../../includes/header.php';
?>

<section class="flex min-h-[50vh] items-center justify-center px-4 py-16">
  <div class="text-center">
    <div class="mx-auto mb-4 h-10 w-10 animate-spin rounded-full border-4 border-accent border-t-transparent"></div>
    <p class="text-muted">Completing sign in...</p>
  </div>
</section>

<script>
window.DT_CALLBACK = <?= json_encode([
    'next' => url(ltrim($next, '/')),
    'isAdminLogin' => $isAdminLogin,
    'accountUrl' => url('auth/account.php'),
    'adminDashboard' => url(ADMIN_PATH . '/dashboard.php'),
    'adminLogin' => url(ADMIN_PATH . '/index.php'),
    'customerLogin' => url('auth/login.php'),
    'checkEmailUrl' => $checkEmailUrl,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>

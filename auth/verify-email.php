<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

$pageTitle = 'Email Verified — ' . APP_NAME;
$activeNav = 'login';
$extraScripts = ['js/auth-verify-email.js'];

require __DIR__ . '/../includes/header.php';
?>

<section class="flex min-h-[50vh] items-center justify-center px-4 py-16">
  <div class="w-full max-w-md text-center">
    <div id="verifyStatus">
      <div class="mx-auto mb-4 h-10 w-10 animate-spin rounded-full border-4 border-accent border-t-transparent"></div>
      <p class="text-muted">Confirming your email...</p>
    </div>
  </div>
</section>

<script>
window.DT_VERIFY_EMAIL = <?= json_encode([
    'logoUrl' => logo_url(),
    'loginUrl' => url('auth/login.php'),
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>

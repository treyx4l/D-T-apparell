<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/config.php';

$email = sanitize_email($_GET['email'] ?? '');
$pageTitle = 'Verify Your Email — ' . APP_NAME;
$activeNav = 'login';
$extraScripts = ['js/auth.js'];

require __DIR__ . '/../../includes/header.php';
?>

<section class="flex min-h-[70vh] items-center justify-center px-4 py-16">
  <div class="w-full max-w-md text-center">
    <div class="rounded-2xl border border-gray-100 bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900">
      <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-accent/30">
        <img src="<?= logo_url() ?>" alt="" class="h-12 w-12 rounded-lg object-contain">
      </div>
      <h1 class="text-2xl font-bold">Check your email</h1>
      <?php if ($email !== ''): ?>
        <p class="mt-3 text-sm text-muted">We sent a verification message to</p>
        <p class="mt-1 font-semibold text-ink dark:text-white"><?= escape_output($email) ?></p>
      <?php else: ?>
        <p class="mt-3 text-sm text-muted">We sent a verification message to your email address.</p>
      <?php endif; ?>

      <div id="waitingNotice" class="mt-6 rounded-xl bg-surface p-4 text-left text-sm text-muted dark:bg-gray-800">
        <p class="font-medium text-ink dark:text-white">Keep this tab open</p>
        <ol class="mt-3 list-decimal space-y-2 pl-5">
          <li>Open your <strong>inbox</strong> and click the verification link</li>
          <li>If you don't see it, check your <strong>spam or junk</strong> folder</li>
          <li>This page will sign you in automatically once verified</li>
        </ol>
      </div>

      <div id="verifiedNotice" class="hidden mt-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200">
        <i class="fas fa-check-circle mr-1"></i> Email verified! Signing you in...
      </div>

      <a href="<?= url('auth/verify-code.php' . ($email ? '?email=' . urlencode($email) : '')) ?>" class="btn-secondary mt-4 inline-flex w-full justify-center text-sm">
        Have a 6-digit code? Enter it here
      </a>
    </div>
  </div>
</section>

<script>
(function () {
  function goToAccount() {
    document.getElementById('waitingNotice')?.classList.add('hidden');
    document.getElementById('verifiedNotice')?.classList.remove('hidden');
    setTimeout(() => { window.location.href = resolveAppUrl('/auth/account.php'); }, 800);
  }

  listenForAuthEvent('verified', goToAccount);

  setInterval(async () => {
    try {
      const res = await fetch(resolveAppUrl('/api/session-status.php'), { credentials: 'same-origin' });
      const data = await res.json();
      if (data.loggedIn) goToAccount();
    } catch (e) { /* ignore */ }
  }, 2000);
})();
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>

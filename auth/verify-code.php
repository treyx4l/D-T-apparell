<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

$email = sanitize_email($_GET['email'] ?? '');
$pageTitle = 'Enter Verification Code — ' . APP_NAME;
$activeNav = 'login';
$extraScripts = ['js/auth.js'];

require __DIR__ . '/../includes/header.php';
?>

<section class="flex min-h-[70vh] items-center justify-center px-4 py-16">
  <div class="w-full max-w-md">
    <div class="rounded-2xl border border-gray-100 bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900">
      <div class="mb-6 text-center">
        <img src="<?= logo_url() ?>" alt="" class="mx-auto mb-4 h-14 w-14 rounded-xl object-contain">
        <h1 class="text-2xl font-bold">Enter verification code</h1>
        <p class="mt-2 text-sm text-muted">Enter the 6-digit code sent to your email<?= $email ? ' (' . escape_output($email) . ')' : '' ?></p>
      </div>

      <div id="authAlert" class="hidden" role="alert"></div>

      <form onsubmit="handleVerifyCode(event)" class="space-y-4">
        <?= csrf_field() ?>
        <?php if ($email === ''): ?>
          <div>
            <label class="label-field" for="verifyEmail">Email</label>
            <input type="email" id="verifyEmail" class="input-field" required placeholder="you@example.com" value="">
          </div>
        <?php else: ?>
          <input type="hidden" id="verifyEmail" value="<?= escape_output($email) ?>">
        <?php endif; ?>
        <div>
          <label class="label-field" for="verifyCode">6-digit code</label>
          <input type="text" id="verifyCode" class="input-field text-center text-2xl tracking-[0.5em]" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code" required placeholder="000000">
        </div>
        <button type="submit" class="btn-primary w-full">Verify &amp; Sign In</button>
      </form>

      <p class="mt-4 text-center text-xs text-muted">Check your inbox and spam folder for the code.</p>
      <a href="<?= url('auth/login.php') ?>" class="mt-4 block text-center text-sm text-muted underline">Back to Sign In</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>

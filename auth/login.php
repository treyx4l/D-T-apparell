<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

redirect_if_customer_authenticated('auth/account.php');

$verified = sanitize_input($_GET['verified'] ?? '') === '1';
$pageTitle = 'Sign In — ' . APP_NAME;
$activeNav = 'login';
$extraScripts = ['js/auth.js'];

require __DIR__ . '/../includes/header.php';
?>

<section class="flex min-h-[70vh] items-center justify-center px-4 py-16">
  <div class="w-full max-w-md">
    <div class="rounded-2xl border border-gray-100 bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900">
      <div class="mb-8 text-center">
        <img src="<?= logo_url() ?>" alt="<?= escape_output(APP_NAME) ?>" class="mx-auto mb-4 h-14 w-14 rounded-xl object-contain">
        <h1 class="text-2xl font-bold">Welcome Back</h1>
        <p class="mt-2 text-sm text-muted">Sign in to your D&amp;T Apparels account</p>
      </div>

      <?php if ($verified): ?>
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200">
          <i class="fas fa-check-circle mr-1"></i> Your email is verified! You can now sign in below.
        </div>
      <?php endif; ?>

      <div id="authAlert" class="hidden" role="alert"></div>

      <form onsubmit="handleCustomerLogin(event)" class="space-y-4">
        <?= csrf_field() ?>
        <div>
          <label class="label-field" for="email">Email</label>
          <input type="email" id="email" class="input-field" placeholder="you@example.com" required autocomplete="email">
        </div>
        <div>
          <label class="label-field" for="password">Password</label>
          <input type="password" id="password" class="input-field" placeholder="Your password" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn-primary w-full"><i class="fas fa-sign-in-alt"></i> Sign In</button>
      </form>

      <div class="my-6 flex items-center gap-4">
        <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
        <span class="text-xs text-muted">OR</span>
        <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
      </div>

      <button type="button" onclick="handleGoogleAuth('/auth/account.php')" class="btn-secondary w-full">
        <i class="fab fa-google"></i> Continue with Google
      </button>

      <p class="mt-6 text-center text-sm text-muted">
        Don't have an account? <a href="<?= url('auth/signup.php') ?>" class="font-medium text-ink underline dark:text-accent">Sign up</a>
      </p>
      <p class="mt-4 text-center text-xs text-muted">Check your <strong>inbox and spam folder</strong> to verify your email before signing in.</p>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>

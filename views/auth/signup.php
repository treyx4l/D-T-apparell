<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/config.php';

redirect_if_customer_authenticated('auth/account.php');
$pageTitle = 'Sign Up — ' . APP_NAME;
$activeNav = 'login';
$extraScripts = ['js/auth.js'];

require __DIR__ . '/../../includes/header.php';
?>

<section class="flex min-h-[70vh] items-center justify-center px-4 py-16">
  <div class="w-full max-w-md">
    <div class="rounded-2xl border border-gray-100 bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900">
      <div class="mb-8 text-center">
        <img src="<?= logo_url() ?>" alt="<?= escape_output(APP_NAME) ?>" class="mx-auto mb-4 h-14 w-14 rounded-xl object-contain">
        <h1 class="text-2xl font-bold">Create Account</h1>
        <p class="mt-2 text-sm text-muted">Join D&amp;T Apparels today</p>
      </div>

      <div id="authAlert" class="hidden" role="alert"></div>

      <form onsubmit="handleCustomerSignup(event)" class="space-y-4">
        <?= csrf_field() ?>
        <div>
          <label class="label-field" for="fullName">Full Name</label>
          <input type="text" id="fullName" class="input-field" placeholder="Your full name" required maxlength="100" autocomplete="name">
        </div>
        <div>
          <label class="label-field" for="email">Email</label>
          <input type="email" id="email" class="input-field" placeholder="you@example.com" required autocomplete="email">
        </div>
        <div>
          <label class="label-field" for="password">Password</label>
          <input type="password" id="password" class="input-field" placeholder="Min. 6 characters" required minlength="6" autocomplete="new-password">
        </div>
        <button type="submit" class="btn-primary w-full"><i class="fas fa-user-plus"></i> Create Account</button>
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
        Already have an account? <a href="<?= url('auth/login.php') ?>" class="font-medium text-ink underline dark:text-accent">Sign in</a>
      </p>
      <p class="mt-4 text-center text-xs text-muted">After signing up, check your <strong>inbox and spam folder</strong> for a verification email before signing in.</p>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>

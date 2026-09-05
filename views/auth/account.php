<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/config.php';

require_customer_auth();
$pageTitle = 'My Account — ' . APP_NAME;
$activeNav = 'account';

require __DIR__ . '/../../includes/header.php';
?>

<section class="mx-auto max-w-2xl px-4 py-16 sm:px-6">
  <h1 class="text-3xl font-bold">My Account</h1>
  <p class="mt-2 text-muted">Manage your D&amp;T Apparels profile</p>

  <div class="mt-8 rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
    <dl class="space-y-4">
      <div>
        <dt class="text-sm text-muted">Email</dt>
        <dd class="font-medium"><?= escape_output($_SESSION['user_email'] ?? '') ?></dd>
      </div>
      <div>
        <dt class="text-sm text-muted">Name</dt>
        <dd class="font-medium"><?= escape_output($_SESSION['user_name'] ?? '—') ?></dd>
      </div>
      <div>
        <dt class="text-sm text-muted">Account Type</dt>
        <dd class="font-medium capitalize"><?= escape_output(current_user_role()) ?></dd>
      </div>
    </dl>
    <div class="mt-6 flex gap-3">
      <a href="<?= url('store.php') ?>" class="btn-primary">Continue Shopping</a>
      <a href="<?= url('auth/logout.php') ?>" class="btn-secondary">Sign Out</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>

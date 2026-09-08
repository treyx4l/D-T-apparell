  </main>
  <footer class="border-t border-gray-100 bg-surface py-10 dark:border-gray-800 dark:bg-gray-900">
    <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
      <p class="text-lg font-semibold text-ink dark:text-white">&copy; <?= date('Y') ?> <?= escape_output(APP_NAME) ?></p>
      <p class="mt-2 text-sm text-muted">Premium apparel &middot; Nationwide delivery &middot; Best prices</p>
    </div>
  </footer>
  <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
  <script src="<?= asset('js/supabase-client.js') ?>"></script>
  <script src="<?= asset('js/theme.js') ?>"></script>
  <?php if (!empty($extraScripts)): ?>
    <?php foreach ($extraScripts as $script): ?>
      <script src="<?= asset($script) ?>"></script>
    <?php endforeach; ?>
  <?php endif; ?>
</body>
</html>

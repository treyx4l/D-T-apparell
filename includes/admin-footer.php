  </main>
  <footer class="border-t border-gray-200 py-6 text-center text-sm text-muted dark:border-gray-800">
    <p>&copy; <?= date('Y') ?> <?= escape_output(APP_NAME) ?> &middot; Admin Dashboard</p>
  </footer>
  <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
  <script src="<?= asset('js/supabase-client.js') ?>"></script>
  <script src="<?= asset('js/auth-channel.js') ?>"></script>
  <script src="<?= asset('js/theme.js') ?>"></script>
  <?php if (!empty($extraScripts)): ?>
    <?php foreach ($extraScripts as $script): ?>
      <script src="<?= asset($script) ?>"></script>
    <?php endforeach; ?>
  <?php endif; ?>
</body>
</html>

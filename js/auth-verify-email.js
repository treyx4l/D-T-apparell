(function () {
  'use strict';

  const cfg = window.DT_VERIFY_EMAIL;
  if (!cfg) return;

  async function run() {
    const statusEl = document.getElementById('verifyStatus');
    if (!statusEl) return;

    function showDone(title, message) {
      statusEl.innerHTML = `
        <div class="rounded-2xl border border-gray-100 bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <img src="${cfg.logoUrl}" alt="" class="mx-auto mb-4 h-14 w-14 rounded-xl object-contain">
          <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-green-100 text-green-700">
            <i class="fas fa-check-circle text-2xl"></i>
          </div>
          <h1 class="text-xl font-bold">${title}</h1>
          <p class="mt-3 text-sm text-muted">${message}</p>
          <p class="mt-4 text-xs text-muted">You can close this tab and return to the app.</p>
        </div>`;
    }

    function showError(message) {
      statusEl.innerHTML = `
        <div class="rounded-2xl border border-red-200 bg-red-50 p-8 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300">
          <i class="fas fa-exclamation-circle mr-1"></i> ${message}
          <a href="${cfg.loginUrl}" class="btn-primary mt-4 inline-flex w-full justify-center">Back to Sign In</a>
        </div>`;
    }

    try {
      if (!window.dtSupabase) throw new Error('Authentication service unavailable.');

      const hash = window.location.hash.substring(1);
      const hashParams = new URLSearchParams(hash);
      const code = new URLSearchParams(window.location.search).get('code');
      let session = null;

      if (code) {
        const { data, error } = await window.dtSupabase.auth.exchangeCodeForSession(code);
        if (error) throw error;
        session = data.session;
      } else if (hashParams.get('access_token') && hashParams.get('refresh_token')) {
        const { data, error } = await window.dtSupabase.auth.setSession({
          access_token: hashParams.get('access_token'),
          refresh_token: hashParams.get('refresh_token'),
        });
        if (error) throw error;
        session = data.session;
      } else if (hashParams.get('error_description')) {
        throw new Error(hashParams.get('error_description'));
      } else {
        throw new Error('Invalid or expired verification link.');
      }

      const result = await syncServerSession(session);
      if (!result.success) throw new Error(result.error || 'Could not sign you in.');

      if (typeof broadcastAuthEvent === 'function') {
        broadcastAuthEvent('verified', { email: result.email || '' });
      }

      showDone('Email verified!', 'Your original tab should sign you in automatically.');
      setTimeout(() => { window.close(); }, 1500);
    } catch (err) {
      showError(err.message || 'Verification failed.');
    }
  }

  run();
})();

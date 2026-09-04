(function () {
  'use strict';

  const cfg = window.DT_CALLBACK;
  if (!cfg) return;

  async function run() {
    let next = cfg.next;
    let isAdminLogin = cfg.isAdminLogin;

    try {
      const stored = sessionStorage.getItem('dt-oauth-return');
      if (stored) {
        const o = JSON.parse(stored);
        sessionStorage.removeItem('dt-oauth-return');
        if (o.next) next = resolveAppUrl(o.next);
        if (o.admin) isAdminLogin = true;
      }
    } catch (e) { /* ignore */ }

    async function finishSession(session, user) {
      const result = await syncServerSession(session, { adminLogin: isAdminLogin });
      if (!result.success) {
        if (isAdminLogin) {
          await fetch(resolveAppUrl('/api/logout.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ csrf_token: window.DT_APPARELS.csrfToken }),
          });
          window.location.href = cfg.adminLogin + '?error=access_denied';
          return;
        }
        if ((result.error || '').toLowerCase().includes('verify')) {
          window.location.href = cfg.checkEmailUrl + '?email=' + encodeURIComponent(user?.email || '');
          return;
        }
        throw new Error(result.error || 'Session sync failed');
      }

      if (isAdminLogin) {
        if (result.role !== 'admin') {
          window.location.href = cfg.adminLogin + '?error=access_denied';
          return;
        }
        window.location.href = cfg.adminDashboard;
        return;
      }

      window.location.href = resolveAppUrl(next || '/auth/account.php');
    }

    function showError(message) {
      if (typeof showToast === 'function') {
        showToast(message, 'error');
      }
      const section = document.querySelector('section p.text-muted');
      if (section) section.textContent = message;
    }

    if (!window.dtSupabase) {
      showError('Authentication service unavailable.');
      setTimeout(() => {
        window.location.href = isAdminLogin ? cfg.adminLogin : cfg.customerLogin;
      }, 2000);
      return;
    }

    try {
      const hash = window.location.hash.substring(1);
      const params = new URLSearchParams(hash);
      const accessToken = params.get('access_token');
      const refreshToken = params.get('refresh_token');

      if (accessToken && refreshToken && window.location.hash) {
        history.replaceState(null, '', window.location.pathname + window.location.search);
      }

      if (!accessToken || !refreshToken) {
        const code = new URLSearchParams(window.location.search).get('code');
        if (code) {
          const { data, error } = await window.dtSupabase.auth.exchangeCodeForSession(code);
          if (error) throw error;
          await finishSession(data.session, data.session?.user);
          return;
        }
        throw new Error('Missing authentication tokens');
      }

      const { data, error } = await window.dtSupabase.auth.setSession({
        access_token: accessToken,
        refresh_token: refreshToken,
      });
      if (error) throw error;
      await finishSession(data.session, data.session?.user);
    } catch (err) {
      showError(err.message || 'Authentication failed');
      setTimeout(() => {
        window.location.href = isAdminLogin ? cfg.adminLogin : cfg.customerLogin;
      }, 2000);
    }
  }

  run();
})();

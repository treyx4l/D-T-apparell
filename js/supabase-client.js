(function () {
  'use strict';

  const config = window.DT_APPARELS || {};
  const { url: supabaseUrl, anonKey } = config.supabase || {};

  if (!supabaseUrl || !anonKey || typeof supabase === 'undefined') {
    console.warn('Supabase is not configured.');
    window.dtSupabase = null;
    return;
  }

  window.dtSupabase = supabase.createClient(supabaseUrl, anonKey, {
    auth: {
      persistSession: false,
      autoRefreshToken: false,
      detectSessionInUrl: false,
    },
  });

  window.syncServerSession = async function syncServerSession(session, options = {}) {
    if (!session?.access_token || !session?.refresh_token) {
      return { success: false, error: 'No session' };
    }

    const response = await fetch(
      typeof resolveAppUrl === 'function' ? resolveAppUrl('/api/session.php') : (config.baseUrl || '') + '/api/session.php',
      {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({
          access_token: session.access_token,
          refresh_token: session.refresh_token,
          csrf_token: config.csrfToken,
          admin_login: !!options.adminLogin,
        }),
      }
    );

    let data = {};
    try {
      data = await response.json();
    } catch (e) {
      return { success: false, error: 'Unable to sign in. Please try again.' };
    }

    if (!response.ok || !data.success) {
      return {
        success: false,
        error: data.error || 'Unable to sign in. Please try again.',
      };
    }

    return data;
  };

  window.signInWithGoogle = async function signInWithGoogle(redirectPath, options = {}) {
    const redirectTo = resolveAppUrl('/auth/callback.php');
    const params = new URLSearchParams();
    if (redirectPath) params.set('next', redirectPath);
    if (options.admin) params.set('admin', '1');
    const query = params.toString() ? '?' + params.toString() : '';

    try {
      sessionStorage.setItem('dt-oauth-return', JSON.stringify({
        next: redirectPath || '/auth/account.php',
        admin: !!options.admin,
      }));
    } catch (e) { /* private mode */ }

    const { data, error } = await window.dtSupabase.auth.signInWithOAuth({
      provider: 'google',
      options: {
        redirectTo: redirectTo + query,
        skipBrowserRedirect: false,
      },
    });

    if (error) throw error;
    return data;
  };

  window.showToast = function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `<i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i><span>${message}</span>`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
  };

  window.escapeHtml = function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
  };
})();

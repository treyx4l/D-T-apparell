(function () {
  'use strict';

  const config = window.DT_APPARELS || {};

  function verificationRedirect(email) {
    const base = typeof resolveAppUrl === 'function'
      ? resolveAppUrl('/auth/check-email.php')
      : (config.baseUrl || '') + '/auth/check-email.php';
    window.location.href = email ? `${base}?email=${encodeURIComponent(email)}` : base;
  }

  function isEmailNotConfirmed(error) {
    const msg = (error?.message || error?.error || '').toLowerCase();
    return msg.includes('email not confirmed') || msg.includes('email not verified');
  }

  function friendlyAuthError(error) {
    const msg = (error?.message || error?.error || String(error || '')).toLowerCase();
    if (msg.includes('invalid login credentials') || msg.includes('invalid credentials')) {
      return 'Incorrect email or password. Please check your details and try again.';
    }
    if (msg.includes('user not found')) {
      return 'No account found with this email address.';
    }
    if (msg.includes('email not confirmed') || msg.includes('email not verified')) {
      return 'Please verify your email first. Check your inbox and spam folder.';
    }
    return error?.message || error?.error || 'Something went wrong. Please try again.';
  }

  window.showAuthAlert = function showAuthAlert(message, type = 'error') {
    const alertEl = document.getElementById('authAlert');
    if (alertEl) {
      alertEl.className = type === 'success'
        ? 'mb-4 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200'
        : 'mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300';
      alertEl.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} mr-1"></i> ${message}`;
      alertEl.classList.remove('hidden');
      alertEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    if (typeof showToast === 'function') {
      showToast(message, type === 'success' ? 'success' : 'error');
    }
  };

  window.handleCustomerLogin = async function handleCustomerLogin(e) {
    e.preventDefault();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const submitBtn = e.target.querySelector('button[type="submit"]');

    if (!window.dtSupabase) {
      showAuthAlert('Authentication is not configured. Please contact support.');
      return;
    }

    try {
      if (submitBtn) submitBtn.disabled = true;
      document.getElementById('authAlert')?.classList.add('hidden');

      const { data, error } = await window.dtSupabase.auth.signInWithPassword({ email, password });
      if (error) {
        if (isEmailNotConfirmed(error)) {
          verificationRedirect(email);
          return;
        }
        throw error;
      }

      const result = await syncServerSession(data.session);
      if (!result.success) {
        if ((result.error || '').toLowerCase().includes('verify')) {
          verificationRedirect(email);
          return;
        }
        throw new Error(result.error || 'Session sync failed');
      }

      window.location.href = resolveAppUrl('/auth/account.php');
    } catch (err) {
      showAuthAlert(friendlyAuthError(err));
    } finally {
      if (submitBtn) submitBtn.disabled = false;
    }
  };

  window.handleCustomerSignup = async function handleCustomerSignup(e) {
    e.preventDefault();
    const fullName = document.getElementById('fullName').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const verifyUrl = resolveAppUrl('/auth/verify-email.php');
    const submitBtn = e.target.querySelector('button[type="submit"]');

    if (!window.dtSupabase) {
      showAuthAlert('Authentication is not configured. Please contact support.');
      return;
    }

    try {
      if (submitBtn) submitBtn.disabled = true;
      document.getElementById('authAlert')?.classList.add('hidden');

      const { data, error } = await window.dtSupabase.auth.signUp({
        email,
        password,
        options: {
          data: { full_name: fullName, role: 'customer' },
          emailRedirectTo: verifyUrl,
        },
      });
      if (error) throw error;

      // Always send to check-email page when confirmation is required
      if (!data.session || !data.user?.email_confirmed_at) {
        try {
          await window.dtSupabase.auth.signInWithOtp({
            email,
            options: { shouldCreateUser: false },
          });
        } catch (otpErr) { /* signup email may already include link */ }
        verificationRedirect(email);
        return;
      }

      const result = await syncServerSession(data.session);
      if (result.success) {
        window.location.href = resolveAppUrl('/auth/account.php');
        return;
      }

      verificationRedirect(email);
    } catch (err) {
      showAuthAlert(friendlyAuthError(err));
    } finally {
      if (submitBtn) submitBtn.disabled = false;
    }
  };

  window.handleAdminLogin = async function handleAdminLogin(e) {
    e.preventDefault();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const submitBtn = e.target.querySelector('button[type="submit"]');

    if (!window.dtSupabase) {
      showAuthAlert('Authentication is not configured.');
      return;
    }

    try {
      if (submitBtn) submitBtn.disabled = true;
      document.getElementById('authAlert')?.classList.add('hidden');

      const { data, error } = await window.dtSupabase.auth.signInWithPassword({ email, password });
      if (error) throw error;

      const result = await syncServerSession(data.session, { adminLogin: true });
      if (!result.success) {
        await fetch((config.baseUrl || '') + '/api/logout.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ csrf_token: config.csrfToken }),
        });
        throw new Error(result.error || 'Access denied. Admin privileges required.');
      }

      if (result.role !== 'admin') {
        await fetch((config.baseUrl || '') + '/api/logout.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ csrf_token: config.csrfToken }),
        });
        throw new Error('Access denied. Admin privileges required.');
      }

      window.location.href = (config.baseUrl || '') + '/dt-admin/dashboard.php';
    } catch (err) {
      showAuthAlert(friendlyAuthError(err));
    } finally {
      if (submitBtn) submitBtn.disabled = false;
    }
  };

  window.handleAdminGoogleAuth = async function handleAdminGoogleAuth() {
    try {
      await signInWithGoogle('/dt-admin/dashboard.php', { admin: true });
    } catch (err) {
      showAuthAlert(friendlyAuthError(err));
    }
  };

  window.handleGoogleAuth = async function handleGoogleAuth(redirectPath) {
    try {
      await signInWithGoogle(redirectPath || '/auth/account.php', { admin: false });
    } catch (err) {
      showAuthAlert(friendlyAuthError(err));
    }
  };

  window.handleVerifyCode = async function handleVerifyCode(e) {
    e.preventDefault();
    const email = document.getElementById('verifyEmail').value.trim();
    const token = document.getElementById('verifyCode').value.trim().replace(/\s/g, '');
    const submitBtn = e.target.querySelector('button[type="submit"]');

    if (!window.dtSupabase) {
      showAuthAlert('Authentication is not configured.');
      return;
    }

    if (!/^\d{6}$/.test(token)) {
      showAuthAlert('Please enter a valid 6-digit code.');
      return;
    }

    try {
      if (submitBtn) submitBtn.disabled = true;
      document.getElementById('authAlert')?.classList.add('hidden');

      const { data, error } = await window.dtSupabase.auth.verifyOtp({
        email,
        token,
        type: 'email',
      });
      if (error) throw error;

      const result = await syncServerSession(data.session);
      if (!result.success) throw new Error(result.error || 'Session sync failed');

      if (typeof broadcastAuthEvent === 'function') {
        broadcastAuthEvent('verified', { email });
      }

      window.location.href = resolveAppUrl('/auth/account.php');
    } catch (err) {
      showAuthAlert(friendlyAuthError(err));
    } finally {
      if (submitBtn) submitBtn.disabled = false;
    }
  };
})();

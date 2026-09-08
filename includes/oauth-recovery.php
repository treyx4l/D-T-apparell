<?php
declare(strict_types=1);
/** Early OAuth return handler — only redirects for admin OAuth flows. */
?>
<script>
(function () {
  var hash = window.location.hash || '';
  var search = window.location.search || '';
  if (hash.indexOf('access_token=') === -1 && search.indexOf('code=') === -1) return;

  var path = window.location.pathname || '/';
  if (path.indexOf('/auth/callback.php') !== -1) return;
  if (path.indexOf('/auth/verify-email.php') !== -1) return;

  // Only redirect if this is an admin OAuth flow
  var stored = null;
  try { stored = JSON.parse(sessionStorage.getItem('dt-oauth-return') || 'null'); } catch (e) {}
  if (!stored || !stored.admin) return;

  var base = (window.DT_APPARELS && window.DT_APPARELS.baseUrl) ? window.DT_APPARELS.baseUrl : '';
  var target;
  if (/^https?:\/\//i.test(base)) {
    target = base.replace(/\/$/, '') + '/auth/callback.php';
  } else {
    target = window.location.origin + (base || '') + '/auth/callback.php';
  }
  var extra = '';
  try {
    var parts = [];
    if (stored.next) parts.push('next=' + encodeURIComponent(stored.next));
    if (stored.admin) parts.push('admin=1');
    if (parts.length) extra = '?' + parts.join('&');
  } catch (e) {}

  if (!extra && search) extra = search;
  window.location.replace(target + extra + hash);
})();
</script>

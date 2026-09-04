<?php
declare(strict_types=1);
/** Early OAuth return handler — runs before page render when Supabase lands on the wrong URL. */
?>
<script>
(function () {
  var hash = window.location.hash || '';
  var search = window.location.search || '';
  if (hash.indexOf('access_token=') === -1 && search.indexOf('code=') === -1) return;

  var path = window.location.pathname || '/';
  if (path.indexOf('/auth/callback.php') !== -1) return;
  if (path.indexOf('/auth/verify-email.php') !== -1) return;

  var base = (window.DT_APPARELS && window.DT_APPARELS.baseUrl) ? window.DT_APPARELS.baseUrl : '';
  var target;
  if (/^https?:\/\//i.test(base)) {
    target = base.replace(/\/$/, '') + '/auth/callback.php';
  } else {
    target = window.location.origin + (base || '') + '/auth/callback.php';
  }
  var extra = '';

  try {
    var stored = sessionStorage.getItem('dt-oauth-return');
    if (stored) {
      var o = JSON.parse(stored);
      var parts = [];
      if (o.next) parts.push('next=' + encodeURIComponent(o.next));
      if (o.admin) parts.push('admin=1');
      if (parts.length) extra = '?' + parts.join('&');
    }
  } catch (e) { /* ignore */ }

  if (!extra && search) extra = search;
  window.location.replace(target + extra + hash);
})();
</script>

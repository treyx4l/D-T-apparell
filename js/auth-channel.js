(function () {
  'use strict';

  const CHANNEL_NAME = 'dt-apparels-auth';
  const STORAGE_KEY = 'dt-auth-event';

  window.broadcastAuthEvent = function broadcastAuthEvent(type, payload = {}) {
    try {
      const channel = new BroadcastChannel(CHANNEL_NAME);
      channel.postMessage({ type, ...payload });
      channel.close();
    } catch (e) { /* unsupported */ }

    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify({ type, at: Date.now(), ...payload }));
      localStorage.removeItem(STORAGE_KEY);
    } catch (e) { /* private mode */ }
  };

  window.listenForAuthEvent = function listenForAuthEvent(type, callback) {
    let handled = false;
    const runOnce = (data) => {
      if (handled || !data || data.type !== type) return;
      handled = true;
      callback(data);
    };

    try {
      const channel = new BroadcastChannel(CHANNEL_NAME);
      channel.onmessage = (event) => runOnce(event.data);
    } catch (e) { /* unsupported */ }

    window.addEventListener('storage', (event) => {
      if (event.key !== STORAGE_KEY || !event.newValue) return;
      try {
        runOnce(JSON.parse(event.newValue));
      } catch (e) { /* ignore */ }
    });
  };

  window.normalizeStorageUrl = function normalizeStorageUrl(url) {
    if (!url || typeof url !== 'string') return url;
    if (url.includes('/storage/v1/object/public/')) return url;
    return url.replace(
      /\/storage\/v1\/object\/(?!public\/)([^/?#]+)\/(.+)/,
      '/storage/v1/object/public/$1/$2'
    );
  };

  window.buildPublicStorageUrl = function buildPublicStorageUrl(supabaseUrl, bucket, path) {
    const base = (supabaseUrl || '').replace(/\/$/, '');
    const cleanPath = (path || '').replace(/^\//, '');
    return `${base}/storage/v1/object/public/${bucket}/${cleanPath}`;
  };

  /** Build an absolute app URL from a path or full URL (handles BASE_URL as full origin). */
  window.resolveAppUrl = function resolveAppUrl(pathOrUrl) {
    const base = window.DT_APPARELS?.baseUrl || '';
    if (!pathOrUrl) {
      return /^https?:\/\//i.test(base) ? base.replace(/\/$/, '') + '/' : window.location.origin + (base || '') + '/';
    }
    if (/^https?:\/\//i.test(pathOrUrl)) return pathOrUrl;
    const path = pathOrUrl.startsWith('/') ? pathOrUrl : '/' + pathOrUrl;
    if (/^https?:\/\//i.test(base)) {
      return base.replace(/\/$/, '') + path;
    }
    return window.location.origin + (base ? base.replace(/\/$/, '') : '') + path;
  };

  window.normalizeProductImage = function normalizeProductImage(product) {
    if (!product || !product.image) return product;
    if (typeof normalizeStorageUrl === 'function') {
      product.image = normalizeStorageUrl(product.image);
    }
    return product;
  };
})();

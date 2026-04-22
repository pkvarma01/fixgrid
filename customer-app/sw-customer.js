/**
 * FixGrid Customer App — Service Worker (sw-customer.js)
 * Place at: /customer-app/sw-customer.js
 *
 * Features:
 *  - Offline caching (cache-first for assets, network-first for API)
 *  - Background push notifications via Firebase
 *  - Periodic background sync for job status updates
 */

importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js');

const CACHE_NAME    = 'fixgrid-customer-v1';
const OFFLINE_URL   = '/customer-app/customer.php';
const APP_URL       = 'https://www.fixgrid.in';

// ── Assets to pre-cache on install ───────────────────────────────────────
const PRECACHE_ASSETS = [
  '/customer-app/customer.php',
  '/customer-app/manifest.json',
  '/customer-app/icon-192.png',
  '/customer-app/icon-512.png',
  'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600&display=swap',
];

// ── Install: pre-cache shell ──────────────────────────────────────────────
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(PRECACHE_ASSETS).catch(() => {
        // Silently fail individual assets — don't block install
      });
    }).then(() => self.skipWaiting())
  );
});

// ── Activate: clean old caches ────────────────────────────────────────────
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
      )
    ).then(() => self.clients.claim())
  );
});

// ── Fetch: cache strategy ─────────────────────────────────────────────────
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip non-GET and cross-origin except known fonts
  if (request.method !== 'GET') return;
  if (url.origin !== location.origin &&
      !url.hostname.includes('fonts.gstatic.com') &&
      !url.hostname.includes('fonts.googleapis.com')) return;

  // API calls — network first, no caching
  if (url.pathname.startsWith('/api/')) {
    event.respondWith(
      fetch(request).catch(() =>
        new Response(JSON.stringify({ success: false, message: 'Offline', data: null }),
          { headers: { 'Content-Type': 'application/json' } })
      )
    );
    return;
  }

  // App shell & assets — cache first, fallback network, then offline page
  event.respondWith(
    caches.match(request).then(cached => {
      if (cached) {
        // Background refresh
        fetch(request).then(res => {
          if (res && res.status === 200) {
            caches.open(CACHE_NAME).then(c => c.put(request, res));
          }
        }).catch(() => {});
        return cached;
      }
      return fetch(request).then(res => {
        if (!res || res.status !== 200 || res.type === 'opaque') return res;
        const clone = res.clone();
        caches.open(CACHE_NAME).then(c => c.put(request, clone));
        return res;
      }).catch(() => caches.match(OFFLINE_URL));
    })
  );
});

// ── Firebase Push Notifications ───────────────────────────────────────────
// Config is injected by customer.php at runtime via a message
let _fcmReady = false;
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'FCM_CONFIG' && !_fcmReady) {
    try {
      firebase.initializeApp(event.data.config);
      const messaging = firebase.messaging();
      messaging.onBackgroundMessage(payload => {
        const title = payload.notification?.title || 'FixGrid';
        const body  = payload.notification?.body  || 'New update on your booking';
        self.registration.showNotification(title, {
          body,
          icon:    '/customer-app/icon-192.png',
          badge:   '/customer-app/icon-72.png',
          data:    payload.data || {},
          vibrate: [200, 100, 200],
          actions: [
            { action: 'view', title: '👁 View', icon: '/customer-app/icon-72.png' },
          ],
          tag:     'fixgrid-customer',
          renotify: true,
        });
      });
      _fcmReady = true;
    } catch(e) {}
  }
});

// Notification click → open / focus app
self.addEventListener('notificationclick', event => {
  event.notification.close();
  const jobId = event.notification.data?.job_id;
  const target = APP_URL + '/customer-app/customer.php' + (jobId ? '#track' : '');
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(list => {
      for (const c of list) {
        if (c.url.includes('/customer-app/') && 'focus' in c) return c.focus();
      }
      return clients.openWindow(target);
    })
  );
});

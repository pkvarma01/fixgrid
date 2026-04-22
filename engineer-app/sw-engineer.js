/**
 * FixGrid Engineer App — Service Worker (sw-engineer.js)
 * Place at: /engineer-app/sw-engineer.js
 *
 * Features:
 *  - Offline caching (cache-first for assets, network-first for API)
 *  - Background push notifications via Firebase (new job alerts)
 *  - Notification click routing to correct screen
 */

importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js');

const CACHE_NAME  = 'fixgrid-engineer-v1';
const OFFLINE_URL = '/engineer-app/engineer.php';
const APP_URL     = 'https://www.fixgrid.in';

const PRECACHE_ASSETS = [
  '/engineer-app/engineer.php',
  '/engineer-app/manifest.json',
  '/engineer-app/icon-192.png',
  '/engineer-app/icon-512.png',
  'https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&family=Poppins:wght@700;800&display=swap',
];

// ── Install ───────────────────────────────────────────────────────────────
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache =>
      cache.addAll(PRECACHE_ASSETS).catch(() => {})
    ).then(() => self.skipWaiting())
  );
});

// ── Activate ──────────────────────────────────────────────────────────────
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

// ── Fetch ─────────────────────────────────────────────────────────────────
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);

  if (request.method !== 'GET') return;
  if (url.origin !== location.origin &&
      !url.hostname.includes('fonts.gstatic.com') &&
      !url.hostname.includes('fonts.googleapis.com')) return;

  // API calls — network only
  if (url.pathname.startsWith('/api/')) {
    event.respondWith(
      fetch(request).catch(() =>
        new Response(JSON.stringify({ success: false, message: 'Offline', data: null }),
          { headers: { 'Content-Type': 'application/json' } })
      )
    );
    return;
  }

  // Shell — cache first with background refresh
  event.respondWith(
    caches.match(request).then(cached => {
      if (cached) {
        fetch(request).then(res => {
          if (res && res.status === 200)
            caches.open(CACHE_NAME).then(c => c.put(request, res));
        }).catch(() => {});
        return cached;
      }
      return fetch(request).then(res => {
        if (!res || res.status !== 200 || res.type === 'opaque') return res;
        caches.open(CACHE_NAME).then(c => c.put(request, res.clone()));
        return res;
      }).catch(() => caches.match(OFFLINE_URL));
    })
  );
});

// ── Firebase Push ─────────────────────────────────────────────────────────
let _fcmReady = false;
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'FCM_CONFIG' && !_fcmReady) {
    try {
      firebase.initializeApp(event.data.config, 'fixgrid-engineer-sw');
      const messaging = firebase.messaging();
      messaging.onBackgroundMessage(payload => {
        const title  = payload.notification?.title || 'FixGrid Engineer';
        const body   = payload.notification?.body  || 'New job available near you';
        const isJob  = payload.data?.type === 'job_offer' || payload.data?.type === 'zone_job';
        self.registration.showNotification(title, {
          body,
          icon:    '/engineer-app/icon-192.png',
          badge:   '/engineer-app/icon-72.png',
          data:    payload.data || {},
          vibrate: [300, 150, 300, 150, 300],  // Stronger vibration for job alerts
          actions: isJob ? [
            { action: 'accept', title: '✅ View Job' },
            { action: 'dismiss', title: '✗ Dismiss'  },
          ] : [],
          tag:      isJob ? 'fixgrid-job-offer' : 'fixgrid-engineer',
          renotify: true,
        });
      });
      _fcmReady = true;
    } catch(e) {}
  }
});

// ── Notification click ────────────────────────────────────────────────────
self.addEventListener('notificationclick', event => {
  event.notification.close();
  const data   = event.notification.data || {};
  const action = event.action;
  const jobId  = data.job_id;

  // If dismissed — do nothing
  if (action === 'dismiss') return;

  const target = APP_URL + '/engineer-app/engineer.php' + (jobId ? '#jobdetail' : '#dashboard');

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(list => {
      for (const c of list) {
        if (c.url.includes('/engineer-app/') && 'focus' in c) {
          c.postMessage({ type: 'NOTIF_CLICK', jobId, action });
          return c.focus();
        }
      }
      return clients.openWindow(target);
    })
  );
});

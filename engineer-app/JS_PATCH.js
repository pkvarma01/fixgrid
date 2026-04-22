/**
 * ══════════════════════════════════════════════════════
 * PWA JS PATCH — engineer.php
 * ══════════════════════════════════════════════════════
 * Find the existing registerFcmToken() function (around
 * line 1564) and REPLACE its full body with this code.
 * Also add the initInstallBanner() call at the bottom
 * of the DOMContentLoaded / init block.
 * ══════════════════════════════════════════════════════
 */

// ── PWA Service Worker Registration ──────────────────
async function registerPWA(fcmConfig) {
  if (!('serviceWorker' in navigator)) return null;
  try {
    const reg = await navigator.serviceWorker.register(
      '/engineer-app/sw-engineer.js',
      { scope: '/engineer-app/' }
    );

    // Send FCM config into SW
    if (fcmConfig && fcmConfig.apiKey) {
      const sw = reg.installing || reg.waiting || reg.active;
      if (sw) sw.postMessage({ type: 'FCM_CONFIG', config: fcmConfig });
      reg.addEventListener('updatefound', () => {
        reg.installing?.postMessage({ type: 'FCM_CONFIG', config: fcmConfig });
      });
    }

    // Route notification clicks from SW → correct screen
    navigator.serviceWorker.addEventListener('message', event => {
      if (event.data?.type === 'NOTIF_CLICK') {
        const { jobId } = event.data;
        if (jobId) {
          // Show job detail for that job
          openJobDetail(jobId);
        } else {
          showScreen('s-dashboard');
        }
      }
    });

    return reg;
  } catch(e) {
    console.warn('[PWA] SW registration failed:', e);
    return null;
  }
}

// ── FCM Token Registration ────────────────────────────
// Replaces existing registerFcmToken() body entirely.
async function registerFcmToken() {
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;

  var s = window._appSettings || {};
  var fcmConfig = {
    apiKey:            s.fcm_api_key       || '',
    authDomain:       (s.fcm_project_id    || '') + '.firebaseapp.com',
    projectId:         s.fcm_project_id    || '',
    storageBucket:    (s.fcm_project_id    || '') + '.appspot.com',
    messagingSenderId: s.fcm_sender_id     || '',
    appId:             s.fcm_app_id        || '',
    vapidKey:          s.fcm_vapid_key     || '',
  };

  if (!fcmConfig.projectId) return;

  var reg = await registerPWA(fcmConfig);
  if (!reg) return;

  try {
    var { initializeApp }    = await import('https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js');
    var { getMessaging, getToken, onMessage } = await import('https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging.js');

    var fbApp   = initializeApp(fcmConfig, 'fixgrid-engineer');
    var msg     = getMessaging(fbApp);
    var token   = await getToken(msg, {
      vapidKey: fcmConfig.vapidKey,
      serviceWorkerRegistration: reg,
    });

    if (token) {
      var authToken = localStorage.getItem('eng_token');
      if (authToken) {
        await fetch('/api/auth/device-token', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + authToken },
          body: JSON.stringify({ token, platform: 'web' }),
        }).catch(() => {});
      }
    }

    // Foreground notifications — show toast + refresh jobs if needed
    onMessage(msg, payload => {
      var title = payload.notification?.title || 'FixGrid';
      var body  = payload.notification?.body  || '';
      showToast('🔔 ' + (body || title));

      // Auto-refresh dashboard job list on new job offer
      if (payload.data?.type === 'job_offer' || payload.data?.type === 'zone_job') {
        if (document.querySelector('#s-dashboard.active')) {
          loadJobs();
        }
      }
    });
  } catch(e) {
    console.warn('[FCM] engineer token error:', e);
  }
}

// ── PWA Install Prompt ────────────────────────────────
(function initInstallBanner() {
  let deferredPrompt = null;

  window.addEventListener('beforeinstallprompt', e => {
    e.preventDefault();
    deferredPrompt = e;
    if (window.matchMedia('(display-mode: standalone)').matches) return;
    if (localStorage.getItem('eng_pwa_dismissed')) return;

    const banner = document.createElement('div');
    banner.innerHTML = `
      <div id="pwa-banner" style="position:fixed;bottom:72px;left:50%;transform:translateX(-50%);
        width:calc(100% - 32px);max-width:398px;
        background:#112240;border:1.5px solid #1E3A5F;border-radius:18px;
        padding:14px 16px;display:flex;align-items:center;gap:12px;
        box-shadow:0 8px 32px rgba(0,0,0,.4);z-index:9998">
        <img src="/engineer-app/icon-72.png" style="width:44px;height:44px;border-radius:12px;flex-shrink:0">
        <div style="flex:1;min-width:0">
          <div style="font-size:13px;font-weight:700;color:#E8EAF6">Install Engineer App</div>
          <div style="font-size:11px;color:#8B90B0;margin-top:1px">Works offline · Job alerts</div>
        </div>
        <button id="pwa-install-btn" style="background:#1E88E5;color:#fff;border:none;border-radius:12px;
          padding:8px 14px;font-size:12px;font-weight:700;cursor:pointer;flex-shrink:0">Install</button>
        <button id="pwa-dismiss-btn" style="background:none;border:none;color:#8B90B0;
          cursor:pointer;font-size:18px;padding:4px;flex-shrink:0">×</button>
      </div>`;
    document.body.appendChild(banner);

    document.getElementById('pwa-install-btn').onclick = async () => {
      banner.remove();
      deferredPrompt.prompt();
      const { outcome } = await deferredPrompt.userChoice;
      if (outcome === 'accepted') showToast('✅ App installed!');
      deferredPrompt = null;
    };
    document.getElementById('pwa-dismiss-btn').onclick = () => {
      banner.remove();
      localStorage.setItem('eng_pwa_dismissed', '1');
    };
  });
})();

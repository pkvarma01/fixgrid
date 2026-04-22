/**
 * ══════════════════════════════════════════════════════
 * PWA JS PATCH — customer.php
 * ══════════════════════════════════════════════════════
 * Find the existing initPush() / serviceWorker.register() block
 * in customer.php (around line 2256) and REPLACE the entire
 * registerFcmToken / initPush function body with this code.
 * ══════════════════════════════════════════════════════
 */

// ── PWA Service Worker Registration ──────────────────
async function registerPWA(fcmConfig) {
  if (!('serviceWorker' in navigator)) return;

  try {
    // Register our custom SW (handles both offline cache + FCM background push)
    const reg = await navigator.serviceWorker.register(
      '/customer-app/sw-customer.js',
      { scope: '/customer-app/' }
    );

    // Send FCM config to SW so it can init Firebase background messaging
    if (fcmConfig && fcmConfig.apiKey) {
      const sw = reg.installing || reg.waiting || reg.active;
      if (sw) {
        sw.postMessage({ type: 'FCM_CONFIG', config: fcmConfig });
      }
      reg.addEventListener('updatefound', () => {
        reg.installing?.postMessage({ type: 'FCM_CONFIG', config: fcmConfig });
      });
    }

    // Listen for messages from SW (e.g. notification click routing)
    navigator.serviceWorker.addEventListener('message', event => {
      if (event.data?.type === 'NOTIF_CLICK') {
        const { jobId } = event.data;
        if (jobId) showScreen('s-track');
      }
    });

    return reg;
  } catch (e) {
    console.warn('[PWA] SW registration failed:', e);
  }
}

// ── FCM Foreground Push Registration ─────────────────
// REPLACE the body of your existing initPush() with this:
async function initPush() {
  if (!('serviceWorker' in navigator) || !('Notification' in window)) return;

  const s = window._appSettings || {};
  const fcmConfig = {
    apiKey:            s.fcm_api_key       || '',
    authDomain:       (s.fcm_project_id    || '') + '.firebaseapp.com',
    projectId:         s.fcm_project_id    || '',
    storageBucket:    (s.fcm_project_id    || '') + '.appspot.com',
    messagingSenderId: s.fcm_sender_id     || '',
    appId:             s.fcm_app_id        || '',
    vapidKey:          s.fcm_vapid_key     || '',
  };

  // Register SW first (needed before getToken)
  const reg = await registerPWA(fcmConfig);
  if (!reg || !fcmConfig.projectId) return;

  try {
    const { initializeApp }       = await import('https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js');
    const { getMessaging, getToken, onMessage } = await import('https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging.js');

    const fbApp   = initializeApp(fcmConfig, 'fixgrid-customer');
    const msg     = getMessaging(fbApp);
    const token   = await getToken(msg, {
      vapidKey: fcmConfig.vapidKey,
      serviceWorkerRegistration: reg,
    });

    if (token) {
      await api('POST', '/auth/device-token', { token, platform: 'web' });
    }

    // Foreground notifications (app is open)
    onMessage(msg, payload => {
      const title = payload.notification?.title || 'FixGrid';
      const body  = payload.notification?.body  || '';
      showToast('🔔 ' + (body || title));

      // If it's a job status update, silently refresh track screen
      if (payload.data?.type === 'job_update') {
        refreshJobStatus();
      }
    });
  } catch(e) {
    console.warn('[FCM] token error:', e);
  }
}

// ── PWA Install Prompt ────────────────────────────────
// Shows a bottom banner prompting user to install the app
(function initInstallBanner() {
  let deferredPrompt = null;

  window.addEventListener('beforeinstallprompt', e => {
    e.preventDefault();
    deferredPrompt = e;

    // Only show if not already installed
    if (window.matchMedia('(display-mode: standalone)').matches) return;
    if (localStorage.getItem('pwa_install_dismissed')) return;

    const banner = document.createElement('div');
    banner.id = 'pwa-banner';
    banner.innerHTML = `
      <div style="position:fixed;bottom:72px;left:50%;transform:translateX(-50%);
        width:calc(100% - 32px);max-width:398px;
        background:var(--card);border:1.5px solid var(--border);border-radius:18px;
        padding:14px 16px;display:flex;align-items:center;gap:12px;
        box-shadow:0 8px 32px rgba(0,0,0,.18);z-index:9998;animation:screenIn .3s ease">
        <img src="/customer-app/icon-72.png" style="width:44px;height:44px;border-radius:12px;flex-shrink:0">
        <div style="flex:1;min-width:0">
          <div style="font-size:13px;font-weight:700;color:var(--text)">Install FixGrid App</div>
          <div style="font-size:11px;color:var(--muted);margin-top:1px">Works offline · Faster booking</div>
        </div>
        <button id="pwa-install-btn" style="background:var(--accent);color:#fff;border:none;border-radius:12px;
          padding:8px 14px;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap;flex-shrink:0">
          Install
        </button>
        <button id="pwa-dismiss-btn" style="background:none;border:none;color:var(--muted);
          cursor:pointer;font-size:18px;line-height:1;flex-shrink:0;padding:4px">×</button>
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
      localStorage.setItem('pwa_install_dismissed', '1');
    };
  });
})();

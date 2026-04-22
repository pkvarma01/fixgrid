<?php
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FixGrid — Admin Panel</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Geist+Mono:wght@400;500&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="icon" type="image/x-icon" href="/logo.png">
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<meta name="theme-color" content="#0B3C5D">
<script>

// ─── Config ───────────────────────────────────────────────
const API = 'https://www.fixgrid.in/api';
var authToken = localStorage.getItem('fsm_token');
var currentAdmin = JSON.parse(localStorage.getItem('fsm_admin') || 'null');
var liveMapInterval = null;

// ─── API Helper ───────────────────────────────────────────
async function api(method, path, body = null, isForm = false) {
  const headers = {};
  if (authToken) headers['Authorization'] = 'Bearer ' + authToken;
  if (!isForm)   headers['Content-Type'] = 'application/json';

  const opts = { method, headers };
  if (body) opts.body = isForm ? body : JSON.stringify(body);

  // 15 second timeout so pages never hang on "Loading..."
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), 15000);
  opts.signal = controller.signal;

  try {
    const r = await fetch(API + path, opts);
    clearTimeout(timer);
    const text = await r.text();
    if (!text || !text.trim()) return { success:false, message:'Empty response from server' };
    try { return JSON.parse(text); }
    catch(e) { return { success:false, message:'Server error: ' + text.substring(0,100) }; }
  } catch(e) {
    clearTimeout(timer);
    if (e.name === 'AbortError') return { success:false, message:'Request timed out (15s). Check server.' };
    return { success:false, message:'Network error: ' + e.message };
  }
}

// ─── Auth ─────────────────────────────────────────────────
async function doLogin() {
  const email = document.getElementById('loginEmail').value.trim();
  const pass  = document.getElementById('loginPass').value;
  const errEl = document.getElementById('loginError');
  const btn   = document.getElementById('loginBtn');

  errEl.style.display = 'none';
  if (!email || !pass) {
    errEl.textContent = 'Please enter email and password';
    errEl.style.display = 'flex';
    return;
  }

  // Show loading state
  btn.disabled = true;
  btn.textContent = 'Signing in…';

  try {
    const r = await api('POST', '/auth/login', { email, password: pass });
    console.log('Login response:', r);
    if (r.success) {
      authToken    = r.data.token;
      currentAdmin = r.data.admin;
      localStorage.setItem('fsm_token', authToken);
      localStorage.setItem('fsm_admin', JSON.stringify(currentAdmin));
      initApp();
    } else {
      errEl.textContent = r.message || 'Login failed. Check your credentials.';
      errEl.style.display = 'flex';
    }
  } catch (e) {
    console.error('Login error:', e);
    errEl.textContent = 'Network error — could not reach the server. Check your internet connection.';
    errEl.style.display = 'flex';
  } finally {
    btn.disabled = false;
    btn.textContent = 'Sign In to Dashboard';
  }
}

function doLogout() {
  api('POST', '/auth/logout');
  localStorage.removeItem('fsm_token');
  localStorage.removeItem('fsm_admin');
  authToken = null; currentAdmin = null;
  document.getElementById('appShell').classList.add('app-hidden'); document.getElementById('appShell').style.display = 'none';
  document.getElementById('loginScreen').style.display = 'flex';
}

// ─── App Init ─────────────────────────────────────────────
function initApp() {
  document.getElementById('loginScreen').style.display = 'none';
  document.getElementById('appShell').classList.remove('app-hidden'); document.getElementById('appShell').style.display = 'flex';
  document.getElementById('adminName').textContent = currentAdmin?.name || 'Admin';
  document.getElementById('adminRole').textContent = currentAdmin?.role === 'superadmin' ? 'Super Admin' : 'Admin';
  document.getElementById('adminAvatar').textContent = (currentAdmin?.name || 'A')[0].toUpperCase();
  startClock();
  showPage('dashboard', document.querySelector('.nav-item[data-page="dashboard"]') || document.querySelector('.nav-item'));
  loadPendingCount();
  setInterval(loadPendingCount, 30000);
}

document.addEventListener('DOMContentLoaded', function() {
  // ALWAYS show login screen first — no flicker, no blank page
  document.getElementById('loginScreen').style.display = 'flex';
  document.getElementById('appShell').style.display = 'none';

  // If we have a stored token, validate it silently
  if (authToken && currentAdmin) {
    api('GET', '/admin/dashboard').then(function(r) {
      if (r.success) {
        // Valid token — go straight to app
        initApp();
      } else {
        // Expired/invalid — clear it, stay on login
        localStorage.removeItem('fsm_token');
        localStorage.removeItem('fsm_admin');
        authToken = null; currentAdmin = null;
      }
    }).catch(function() {
      // Network error — stay on login, clear bad token
      localStorage.removeItem('fsm_token');
      localStorage.removeItem('fsm_admin');
      authToken = null; currentAdmin = null;
    });
  }
});

function showAdminForgot() {
  document.getElementById('forgotAdminEmail').value = document.getElementById('loginEmail')?.value || '';
  document.getElementById('forgotModalMsg').style.display = 'none';
  document.getElementById('forgotModal').style.display = 'flex';
}

async function requestAdminReset(event) {
  var email = document.getElementById('forgotAdminEmail').value.trim();
  var msgEl = document.getElementById('forgotModalMsg');
  if (!email) { showAdminMsg(msgEl, 'Enter your email', 'error'); return; }
  var btn = event.target.closest('button')||event.target; btn.disabled=true; btn.textContent='Sending...';
  var res = await api('POST', '/auth/forgot-password', {email:email, user_type:'admin'});
  btn.disabled=false; btn.textContent='📧 Send Link';
  showAdminMsg(msgEl, res.message || (res.success ? 'Check your email!' : 'Error'), res.success ? 'success' : 'error');
}

async function submitAdminReset(event) {
  var pass1 = document.getElementById('adminNewPass1').value;
  var pass2 = document.getElementById('adminNewPass2').value;
  var msgEl = document.getElementById('resetModalMsg');
  if (!pass1 || pass1.length < 6) { showAdminMsg(msgEl, 'Password must be at least 6 characters', 'error'); return; }
  if (pass1 !== pass2) { showAdminMsg(msgEl, 'Passwords do not match', 'error'); return; }
  var btn = event.target.closest('button')||event.target; btn.disabled=true; btn.textContent='Saving...';
  var res = await api('POST', '/auth/reset-password', {token: window._adminResetToken||'', password: pass1});
  btn.disabled=false; btn.textContent='🔒 Set New Password';
  if (res.success) {
    showAdminMsg(msgEl, '✅ Password changed! Redirecting...', 'success');
    setTimeout(function(){ window.location.href = window.location.pathname; }, 2000);
  } else { showAdminMsg(msgEl, res.message || 'Error', 'error'); }
}

function showAdminMsg(el, msg, type) {
  el.textContent = msg; el.style.display = 'block';
  el.style.background = type==='error' ? 'rgba(239,68,68,.15)' : 'rgba(34,197,94,.15)';
  el.style.color = type==='error' ? 'var(--red)' : 'var(--green)';
  el.style.border = '1px solid ' + (type==='error' ? 'rgba(239,68,68,.3)' : 'rgba(34,197,94,.3)');
}

// Handle reset token from URL
var _adminUrlParams = new URLSearchParams(window.location.search);
var _adminResetTok  = _adminUrlParams.get('reset_token');
if (_adminResetTok) {
  window._adminResetToken = _adminResetTok;
  document.addEventListener('DOMContentLoaded', function(){
    document.getElementById('resetModal').style.display = 'flex';
  });
}

document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('loginPass').addEventListener('keydown', e => { if (e.key === 'Enter') doLogin(); });
});

// Tooltip system removed (mini sidebar not used)

// ─── Clock ────────────────────────────────────────────────
function startClock() {
  const el = document.getElementById('clockDisplay');
  setInterval(() => {
    el.textContent = new Date().toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  }, 1000);
}

async function loadPendingCount() {
  const r = await api('GET', '/admin/jobs?status=pending');
  if (r.success) {
    const count = Array.isArray(r.data) ? r.data.length : 0;
    const badge = document.getElementById('pendingBadge');
    if (badge) { badge.textContent = count; badge.style.display = count > 0 ? 'inline-block' : 'none'; }
  }
  const k = await api('GET', '/admin/kyc-review?status=submitted');
  if (k.success) {
    const kcount = Array.isArray(k.data) ? k.data.length : 0;
    const kbadge = document.getElementById('kycBadge');
    if (kbadge) { kbadge.textContent = kcount; kbadge.style.display = kcount > 0 ? 'inline-block' : 'none'; }
  }
}

// ─── Navigation ───────────────────────────────────────────
function showPage(page, el) {
  document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
  if (el) el.classList.add('active');
  if (liveMapInterval) { clearInterval(liveMapInterval); liveMapInterval = null; }
  const titles = { dashboard:'Dashboard', livemap:'Live Map', jobs:'Jobs', engineers:'Engineers', customers:'Customers', reports:'Reports', analytics:'Analytics', settings:'Settings', 'api-settings':'API Settings', wallets:'Wallets', 'platform-fees':'Platform Fees', website:'Website Content', skills:'Skills', services:'Services', disputes:'Disputes', contracts:'Contracts', promo:'Promo Codes', inventory:'Inventory', quotations:'Quotations', pickups:'Device Pickups', kyc:'KYC Review', zones:'Zone Management' };
  document.getElementById('pageTitle').textContent = titles[page] || page;
  const pages = { dashboard: renderDashboard, livemap: renderLiveMap, jobs: renderJobs, engineers: renderEngineers, services: renderServices, customers: renderCustomers, reports: renderReports, analytics: renderAnalytics, settings: renderSettings, 'api-settings': renderApiSettings, wallets: renderWallets, 'platform-fees': renderPlatformFees, disputes: renderDisputes, contracts: renderContracts, promo: renderPromo, inventory: renderInventory, quotations: renderQuotations, pickups: renderPickups, kyc: renderKycReview, zones: renderZones, website: renderWebsite };
  if (pages[page]) {
    const p = pages[page]();
    if (p && p.catch) p.catch(e => {
      console.error('Page render error [' + page + ']:', e);
      setContent('<div class="empty" style="padding:40px;text-align:center">'
        + '<div style="font-size:40px;margin-bottom:16px">⚠️</div>'
        + '<div style="font-weight:700;font-size:18px;margin-bottom:8px">Page failed to load</div>'
        + '<div style="color:var(--muted);font-size:13px;margin-bottom:20px">' + e.message + '</div>'
        + '<button class="btn btn-primary" onclick="showPage(\'' + page + '\',document.querySelector(\'.nav-item.active\'))">↺ Retry</button>'
        + '</div>');
    });
  }
}

function refreshPage() {
  const active = document.querySelector('.nav-item.active');
  if (active) active.click();
}

// Mobile sidebar with backdrop
window.toggleSidebar = function() {
  var sb = document.getElementById('sidebar');
  var bd = document.getElementById('sidebarBackdrop');
  var isOpen = sb.classList.toggle('open');
  if (bd) {
    if (isOpen) {
      bd.style.display = 'block';
      requestAnimationFrame(function(){ bd.classList.add('visible'); });
    } else {
      bd.classList.remove('visible');
      setTimeout(function(){ bd.style.display = 'none'; }, 350);
    }
  }
};
window.closeSidebar = function() {
  var sb = document.getElementById('sidebar');
  var bd = document.getElementById('sidebarBackdrop');
  if (sb) sb.classList.remove('open');
  if (bd) {
    bd.classList.remove('visible');
    setTimeout(function(){ bd.style.display = 'none'; }, 350);
  }
};
// Close sidebar when a nav item is clicked on mobile
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.nav-item').forEach(function(item) {
    item.addEventListener('click', function() {
      if (window.innerWidth <= 768) window.closeSidebar();
    });
  });
});

// ─── DASHBOARD ────────────────────────────────────────────
async function renderDashboard() {
  setContent('<div class="loading"><div class="spinner"></div> Loading dashboard...</div>');
  const r = await api('GET', '/admin/dashboard');
  if (!r.success) {
    setContent(
      '<div class="empty" style="padding:40px;text-align:center">' +
      '<div style="font-size:40px;margin-bottom:12px">\u26A0\uFE0F</div>' +
      '<div style="font-weight:700;font-size:16px;margin-bottom:8px">Dashboard failed to load</div>' +
      '<div style="color:var(--muted);font-size:13px;margin-bottom:20px">' + (r.message||'Unknown error') + '</div>' +
      '<button class="btn btn-primary btn-sm" onclick="renderDashboard()">\u21BA Retry</button>' +
      '</div>'
    );
    return;
  }

  // Defensive defaults — prevent .map() crashes if API fields are missing
  const d  = r.data || {};
  const rj = Array.isArray(d.recent_jobs)    ? d.recent_jobs    : [];
  const te = Array.isArray(d.top_engineers)  ? d.top_engineers  : [];
  const dy = Array.isArray(d.daily)          ? d.daily          : [];
  const fmt = n => Number(n||0).toLocaleString('en-IN');

  try {
    const html = `
  <div class="stat-grid">
    <div class="stat-card blue">
      <div class="stat-label">Engineers</div>
      <div class="stat-value">${d.total_engineers||0}</div>
      <div class="stat-sub" style="display:flex;gap:10px;margin-top:8px">
        <span style="color:var(--green);font-weight:600;font-size:11px">\u25CF ${d.available_engineers||0} free</span>
        <span style="color:var(--amber);font-weight:600;font-size:11px">\u25CF ${d.busy_engineers||0} busy</span>
      </div>
    </div>
    <div class="stat-card green">
      <div class="stat-label">Completed</div>
      <div class="stat-value">${d.completed_today||0}</div>
      <div class="stat-sub">Today \u00B7 <span style="font-weight:600">${d.completed_month||0}</span> this month</div>
    </div>
    <div class="stat-card amber">
      <div class="stat-label">Active Jobs</div>
      <div class="stat-value">${d.active_jobs||0}</div>
      <div class="stat-sub">${d.pending_jobs||0} pending assignment</div>
    </div>
    <div class="stat-card purple">
      <div class="stat-label">Quotations</div>
      <div class="stat-value">${d.quotation_requests||0}</div>
      <div class="stat-sub">Awaiting review</div>
    </div>
    <div class="stat-card cyan">
      <div class="stat-label">Pickups</div>
      <div class="stat-value">${d.pickup_requests||0}</div>
      <div class="stat-sub">Device pickups</div>
    </div>
    <div class="stat-card blue">
      <div class="stat-label">Customers</div>
      <div class="stat-value">${d.total_customers||0}</div>
      <div class="stat-sub">Total registered</div>
    </div>
    <div class="stat-card green">
      <div class="stat-label">Gross Revenue Today</div>
      <div class="stat-value" style="font-size:22px">\u20B9${fmt(d.revenue_today)}</div>
      <div class="stat-sub">\u20B9${fmt(d.revenue_month)} this month</div>
    </div>
    <div class="stat-card cyan">
      <div class="stat-label">Platform Fee Today</div>
      <div class="stat-value" style="font-size:22px">\u20B9${fmt(d.platform_fee_today)}</div>
      <div class="stat-sub">\u20B9${fmt(d.platform_fee_month)} this month
        ${d.cash_fee_outstanding ? ' \u00B7 <span style="color:var(--red);font-weight:600">\u20B9'+fmt(d.cash_fee_outstanding)+' cash pending</span>' : ''}
      </div>
    </div>
    <div class="stat-card amber">
      <div class="stat-label">Avg Rating</div>
      <div class="stat-value">${d.avg_rating||'\u2014'}</div>
      <div class="stat-sub">out of 5 \u2605</div>
    </div>
  </div>

  ${dy.length > 0 ? `
  <div style="margin:20px 0 10px;font-size:13px;font-weight:700;color:var(--text)">Last 7 Days</div>
  <div class="card" style="margin-bottom:18px;overflow-x:auto">
    <table>
      <thead><tr><th>Date</th><th>Jobs</th><th>Revenue</th><th class="hide-mobile">Platform Fee</th></tr></thead>
      <tbody>
        ${dy.map(d2 => `<tr>
          <td style="font-family:'Geist Mono',monospace;font-size:12px">${d2.date||''}</td>
          <td style="font-weight:600">${d2.jobs||0}</td>
          <td style="font-family:'Geist Mono',monospace">\u20B9${fmt(d2.revenue)}</td>
          <td class="hide-mobile" style="font-family:'Geist Mono',monospace">\u20B9${fmt(d2.platform_fee)}</td>
        </tr>`).join('')}
      </tbody>
    </table>
  </div>` : ''}

  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:10px">
    <div style="font-size:13px;font-weight:700;color:var(--text)">Recent Jobs</div>
    <button class="btn btn-ghost btn-sm" onclick="showPage('jobs',document.querySelector('.nav-item[data-page=jobs]'))">View all \u2192</button>
  </div>
  <div class="card" style="margin-bottom:${te.length > 0 ? '18px' : '0'};overflow-x:auto">
    ${rj.length === 0
      ? '<div style="padding:24px;text-align:center;color:var(--muted);font-size:13px">No jobs yet</div>'
      : `<table>
        <thead><tr><th>Job #</th><th>Service</th><th>Customer</th><th class="hide-mobile">Engineer</th><th>Status</th><th class="hide-mobile">Amount</th><th class="hide-mobile">Time</th><th>Action</th></tr></thead>
        <tbody>
          ${rj.map(j => `<tr>
            <td><span style="font-family:'Geist Mono',monospace;font-size:11px;font-weight:600;color:var(--accent)">${j.job_number||''}</span></td>
            <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${j.service||''}</td>
            <td>
              <div style="font-size:12.5px;font-weight:500">${j.customer||''}</div>
              <div style="font-size:11px;color:var(--muted)">${j.customer_phone||''}</div>
            </td>
            <td class="hide-mobile" style="font-size:12px">${j.engineer||'Unassigned'}</td>
            <td><span class="badge badge-${j.status||''}">${(j.status||'').replace(/_/g,' ')}</span></td>
            <td class="hide-mobile" style="font-family:'Geist Mono',monospace;font-size:12px">\u20B9${fmt(j.amount)}</td>
            <td class="hide-mobile" style="color:var(--muted);font-size:11px;white-space:nowrap">${timeAgo(j.created_at)}</td>
            <td>
              <div style="display:flex;gap:5px">
                <button class="btn btn-ghost btn-sm" onclick="openJobModal(${j.job_id})">Edit</button>
                ${j.status==='pending' ? '<button class="btn btn-success btn-sm" onclick="openAssign('+j.job_id+')">Assign</button>' : ''}
              </div>
            </td>
          </tr>`).join('')}
        </tbody>
      </table>`
    }
  </div>

  ${te.length > 0 ? `
  <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:10px">Top Engineers This Month</div>
  <div class="card" style="overflow-x:auto">
    <table>
      <thead><tr><th>Engineer</th><th>Jobs</th><th>Revenue</th><th class="hide-mobile">Platform Earned</th><th>Rating</th></tr></thead>
      <tbody>
        ${te.map(e => `<tr>
          <td style="font-weight:600">${e.name||''}</td>
          <td style="font-family:'Geist Mono',monospace">${e.jobs||0}</td>
          <td style="font-family:'Geist Mono',monospace">\u20B9${fmt(e.revenue)}</td>
          <td class="hide-mobile" style="font-family:'Geist Mono',monospace">\u20B9${fmt(e.platform_earned)}</td>
          <td style="color:var(--amber)">\u2605 ${parseFloat(e.rating||0).toFixed(1)}</td>
        </tr>`).join('')}
      </tbody>
    </table>
  </div>` : ''}`;

    setContent(html);
  } catch(err) {
    console.error('Dashboard render error:', err);
    setContent(
      '<div class="empty" style="padding:40px;text-align:center">' +
      '<div style="font-size:40px;margin-bottom:12px">\u26A0\uFE0F</div>' +
      '<div style="font-weight:700;font-size:16px;margin-bottom:8px">Dashboard render error</div>' +
      '<div style="color:var(--muted);font-size:13px;margin-bottom:20px">' + err.message + '</div>' +
      '<button class="btn btn-primary btn-sm" onclick="renderDashboard()">\u21BA Retry</button>' +
      '</div>'
    );
  }
}

// ─── LIVE MAP ─────────────────────────────────────────────
async function renderLiveMap() {
  setContent(`
  <style>
    #liveMapWrap{display:flex;gap:14px;height:calc(100vh - 180px);min-height:520px}
    #liveMapSidebar{width:300px;flex-shrink:0;display:flex;flex-direction:column;gap:0;overflow:hidden;border-radius:var(--radius);border:1px solid var(--border);background:var(--bg2)}
    #liveMapSidebar .lm-head{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
    #liveMapSidebar .lm-search{padding:10px 12px;border-bottom:1px solid var(--border);flex-shrink:0}
    #liveMapSidebar .lm-search input{width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:7px;padding:7px 10px;color:var(--text);font-size:12px;outline:none}
    #liveMapSidebar .lm-filters{padding:8px 12px;border-bottom:1px solid var(--border);display:flex;gap:5px;flex-shrink:0}
    #liveMapSidebar .lm-filters button{flex:1;padding:5px 4px;border-radius:6px;border:1px solid var(--border);font-size:11px;font-weight:600;cursor:pointer;background:transparent;color:var(--muted);font-family:inherit;transition:all .14s}
    #liveMapSidebar .lm-filters button.active{background:var(--nav-active-bg);border-color:var(--accent);color:var(--accent)}
    #lmEngineerList{flex:1;overflow-y:auto;padding:8px}
    .lm-eng-card{padding:10px 12px;border-radius:8px;border:1px solid var(--border);background:var(--bg3);margin-bottom:6px;cursor:pointer;transition:all .14s;display:flex;align-items:center;gap:10px}
    .lm-eng-card:hover,.lm-eng-card.selected{border-color:var(--accent);background:var(--nav-active-bg)}
    .lm-eng-card .lm-av{width:34px;height:34px;border-radius:50%;background:var(--accent);color:#fff;font-weight:700;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .lm-eng-card .lm-av.busy{background:var(--amber)}
    .lm-eng-card .lm-av.offline{background:var(--hint)}
    .lm-eng-card .lm-info{flex:1;min-width:0}
    .lm-eng-card .lm-name{font-size:12px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .lm-eng-card .lm-meta{font-size:11px;color:var(--muted);margin-top:1px;display:flex;align-items:center;gap:5px}
    .lm-eng-card .lm-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}
    .lm-eng-card .lm-dot.available{background:var(--green)}
    .lm-eng-card .lm-dot.busy{background:var(--amber)}
    .lm-eng-card .lm-dot.offline,.lm-eng-card .lm-dot.unavailable{background:var(--hint)}
    #liveMapMain{flex:1;border-radius:var(--radius);overflow:hidden;border:1px solid var(--border);position:relative;min-height:400px}
    #liveMapEl{width:100%;height:100%;min-height:480px}
    #liveMapEl{width:100%;height:100%}
    #lmNoGps{display:none;position:absolute;bottom:14px;left:50%;transform:translateX(-50%);background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:8px 14px;font-size:12px;color:var(--muted);white-space:nowrap;z-index:10}
    #lmTooltip{display:none;position:absolute;background:var(--bg2);border:1px solid var(--border);border-radius:10px;padding:12px 14px;min-width:200px;z-index:20;pointer-events:none;box-shadow:0 4px 20px rgba(0,0,0,.35)}
    #lmTooltip .tt-name{font-size:13px;font-weight:700;margin-bottom:4px}
    #lmTooltip .tt-row{font-size:11px;color:var(--muted);margin-top:2px}
    .lm-stat-bar{display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap}
    .lm-stat{background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:8px 14px;font-size:12px;display:flex;align-items:center;gap:7px}
    .lm-stat strong{font-size:16px;font-family:'Geist Mono',monospace}
    .lm-popup .leaflet-popup-content-wrapper{background:var(--bg2,#1a2035);color:var(--text,#e2e4f0);border:1px solid var(--border,#2e3550);border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,.5)}
    .lm-popup .leaflet-popup-tip{background:var(--bg2,#1a2035)}
    .lm-popup .leaflet-popup-close-button{color:var(--muted,#7e85a8)}
    @media(max-width:768px){#liveMapWrap{flex-direction:column;height:auto}#liveMapSidebar{width:100%;height:260px}#liveMapMain{height:340px}}
  </style>

  <div class="lm-stat-bar">
    <div class="lm-stat"><div class="lm-dot available" style="width:9px;height:9px;border-radius:50%;background:var(--green)"></div> Available <strong id="lmCntAvail">—</strong></div>
    <div class="lm-stat"><div class="lm-dot" style="width:9px;height:9px;border-radius:50%;background:var(--amber)"></div> Busy <strong id="lmCntBusy">—</strong></div>
    <div class="lm-stat"><div class="lm-dot" style="width:9px;height:9px;border-radius:50%;background:var(--hint)"></div> Offline <strong id="lmCntOff">—</strong></div>
    <div class="lm-stat">Total <strong id="lmCntAll">—</strong></div>
    <div style="flex:1"></div>
    <button class="btn btn-ghost btn-sm" onclick="loadLiveEngineers()" style="font-size:11px">↺ Refresh</button>
    <div style="font-size:11px;color:var(--muted);align-self:center" id="lmLastUpdate"></div>
  </div>

  <div id="liveMapWrap">
    <div id="liveMapSidebar">
      <div class="lm-head">
        <span style="font-size:13px;font-weight:700"><span class="live-dot"></span> Live Engineers</span>
      </div>
      <div class="lm-search">
        <input id="lmSearch" placeholder="🔍 Search name or phone..." oninput="lmFilterList()">
      </div>
      <div class="lm-filters">
        <button class="active" onclick="lmSetFilter('all',this)">All</button>
        <button onclick="lmSetFilter('available',this)" style="color:var(--green)">Available</button>
        <button onclick="lmSetFilter('busy',this)" style="color:var(--amber)">Busy</button>
        <button onclick="lmSetFilter('offline',this)">Offline</button>
      </div>
      <div id="lmEngineerList"></div>
    </div>
    <div id="liveMapMain">
      <div id="liveMapEl" style="width:100%;height:100%;min-height:480px"></div>
      <div id="lmNoGps">⚠ Some engineers have no GPS location</div>
      <div id="lmTooltip"></div>
    </div>
  </div>`);

  // Reset state each time Live Map page is opened
  _lmBoundsFit = false;
  _lmSelected = null;
  // Clear old markers from previous session
  if (_lmMap && typeof L !== 'undefined') {
    Object.values(_lmMarkers).forEach(m => { try { _lmMap.removeLayer(m); } catch(e2) {} });
  }
  _lmMarkers = {};
  // Destroy old map instance so Leaflet re-initialises cleanly
  if (_lmMap) {
    try { _lmMap.remove(); } catch(e2) {}
    _lmMap = null;
  }

  await initLiveMap();

  // Leaflet requires invalidateSize() when the map container was just inserted into the DOM
  // (otherwise tiles don't cover the full div and markers may be mispositioned)
  if (_lmMap && typeof L !== 'undefined') {
    setTimeout(() => {
      _lmMap.invalidateSize();
    }, 100);
  }

  await loadLiveEngineers();
  liveMapInterval = setInterval(loadLiveEngineers, 10000);
}

// ── Map state ─────────────────────────────────────────────
var _lmMap = null, _lmMarkers = {}, _lmFilter = 'all', _lmData = [], _lmSelected = null;
var _lmUsingGoogle = false, _lmBoundsFit = false;

async function initLiveMap() {
  const el = document.getElementById('liveMapEl');
  if (!el) return;

  // ── Always use Leaflet/OpenStreetMap for the Live Map ──────────────────
  // The global Google Maps script (used by Zones page) may be loaded with an
  // invalid key, causing Google's own error overlay to hijack any div that
  // calls new google.maps.Map(). We avoid this entirely by using Leaflet here.
  // Leaflet renders fine with no API key and no risk of Google's error overlay.

  await _lmLoadLeaflet();
  if (typeof L === 'undefined') {
    el.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--muted);flex-direction:column;gap:8px"><div style="font-size:32px">🌐</div><div style="font-size:13px">Map could not load. Check your internet connection.</div></div>';
    return;
  }

  el.style.background = '#0d1117';
  _lmMap = L.map(el, { zoomControl: true, attributionControl: true })
            .setView([28.4595, 77.0266], 11);

  // Dark tile layer (no API key needed)
  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> &copy; <a href="https://carto.com/" target="_blank">CARTO</a>',
    subdomains: 'abcd',
    maxZoom: 19
  }).addTo(_lmMap);

  _lmUsingGoogle = false;
}

async function _lmLoadLeaflet() {
  if (typeof L !== 'undefined') return; // already loaded
  await new Promise(resolve => {
    // CSS
    if (!document.querySelector('link[href*="leaflet"]')) {
      const lc = document.createElement('link');
      lc.rel = 'stylesheet';
      lc.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
      document.head.appendChild(lc);
    }
    // JS
    const ls = document.createElement('script');
    ls.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    ls.onload  = resolve;
    ls.onerror = resolve; // resolve even on failure so we can show fallback message
    document.head.appendChild(ls);
  });
}
async function loadLiveEngineers() {
  const r = await api('GET', '/admin/live-engineers');
  if (!r.success) return;
  _lmData = r.data || [];

  // Update stat counters
  const avail = _lmData.filter(e => e.status === 'available').length;
  const busy  = _lmData.filter(e => e.status === 'busy').length;
  const off   = _lmData.filter(e => e.status !== 'available' && e.status !== 'busy').length;
  const el_a  = document.getElementById('lmCntAvail'); if (el_a) el_a.textContent = avail;
  const el_b  = document.getElementById('lmCntBusy');  if (el_b) el_b.textContent = busy;
  const el_o  = document.getElementById('lmCntOff');   if (el_o) el_o.textContent = off;
  const el_t  = document.getElementById('lmCntAll');   if (el_t) el_t.textContent = _lmData.length;
  const el_u  = document.getElementById('lmLastUpdate');
  if (el_u) { const now = new Date(); el_u.textContent = 'Updated ' + now.toLocaleTimeString(); }

  // Check if any have no GPS
  const noGps = _lmData.some(e => !e.latitude || !e.longitude);
  const noGpsEl = document.getElementById('lmNoGps');
  if (noGpsEl) noGpsEl.style.display = noGps ? 'block' : 'none';

  lmRenderList();
  lmUpdateMapMarkers();
}

function lmRenderList() {
  const el = document.getElementById('lmEngineerList');
  if (!el) return;
  const q = (document.getElementById('lmSearch')?.value || '').toLowerCase();
  const filtered = _lmData.filter(e => {
    if (_lmFilter !== 'all' && e.status !== _lmFilter) return false;
    if (q && !e.name.toLowerCase().includes(q) && !e.phone.includes(q)) return false;
    return true;
  });
  if (!filtered.length) {
    el.innerHTML = '<div style="text-align:center;padding:24px;color:var(--muted);font-size:12px">No engineers match filter</div>';
    return;
  }
  el.innerHTML = filtered.map(e => {
    const avCls = e.status === 'available' ? 'available' : e.status === 'busy' ? 'busy' : 'offline';
    const since = e.last_online ? timeAgo(e.last_online) : 'unknown';
    const hasGps = e.latitude && e.longitude;
    return `<div class="lm-eng-card ${_lmSelected === e.id ? 'selected' : ''}" onclick="lmSelectEngineer(${e.id})" data-id="${e.id}">
      <div class="lm-av ${avCls}">${e.name[0].toUpperCase()}</div>
      <div class="lm-info">
        <div class="lm-name">${e.name}</div>
        <div class="lm-meta">
          <div class="lm-dot ${avCls}"></div>
          <span>${e.status}</span>
          ${e.active_jobs > 0 ? `<span style="color:var(--amber)">· ${e.active_jobs} job${e.active_jobs>1?'s':''}</span>` : ''}
        </div>
        <div class="lm-meta">${e.phone} ${hasGps ? '· 📍 GPS' : '· <span style="color:var(--red)">No GPS</span>'}</div>
      </div>
    </div>`;
  }).join('');
}

function lmUpdateMapMarkers() {
  if (!_lmMap || typeof L === 'undefined') return;
  const withGps = _lmData.filter(e => e.latitude && e.longitude);
  const seen = new Set();

  withGps.forEach(e => {
    seen.add(e.id);
    const lat = parseFloat(e.latitude);
    const lng = parseFloat(e.longitude);
    if (isNaN(lat) || isNaN(lng)) return;

    const color = e.status === 'available' ? '#22D99F'
                : e.status === 'busy'      ? '#F5A623'
                : '#7E85A8';

    const popupHtml =
      '<div style="font-family:sans-serif;min-width:180px">' +
        '<div style="font-weight:700;font-size:13px;margin-bottom:5px">' + e.name + '</div>' +
        '<div style="font-size:11px;color:#555;margin-bottom:3px">\uD83D\uDCDE ' + e.phone + '</div>' +
        '<div style="font-size:11px;margin-bottom:3px">Status: <b style="color:' + color + '">' + e.status + '</b></div>' +
        '<div style="font-size:11px;margin-bottom:3px">Active jobs: <b>' + e.active_jobs + '</b></div>' +
        '<div style="font-size:10px;color:#999;font-family:monospace">' + lat.toFixed(5) + ', ' + lng.toFixed(5) + '</div>' +
      '</div>';

    if (_lmMarkers[e.id]) {
      // Update position and color
      _lmMarkers[e.id].circle.setLatLng([lat, lng]);
      _lmMarkers[e.id].circle.setStyle({ color: color, fillColor: color });
      _lmMarkers[e.id].label.setLatLng([lat, lng]);
      _lmMarkers[e.id].circle.getPopup()?.setContent(popupHtml);
    } else {
      // Outer glow ring
      const ring = L.circleMarker([lat, lng], {
        radius: 18, color: color, fillColor: color,
        fillOpacity: 0.18, weight: 2, opacity: 0.5
      }).addTo(_lmMap);

      // Solid filled circle
      const circle = L.circleMarker([lat, lng], {
        radius: 13, color: '#fff', fillColor: color,
        fillOpacity: 1, weight: 2.5
      }).addTo(_lmMap);
      circle.bindPopup(popupHtml, { maxWidth: 240, className: 'lm-popup' });
      circle.on('click', () => lmSelectEngineer(e.id));

      // Initial label on top
      const initial = e.name[0].toUpperCase();
      const label = L.divIcon({
        html: '<div style="width:26px;height:26px;display:flex;align-items:center;justify-content:center;' +
              'font-size:11px;font-weight:800;color:#fff;font-family:sans-serif;' +
              'text-shadow:0 1px 2px rgba(0,0,0,.5);pointer-events:none">' + initial + '</div>',
        className: '',
        iconSize: [26, 26],
        iconAnchor: [13, 13]
      });
      const labelMarker = L.marker([lat, lng], { icon: label, interactive: false, zIndexOffset: 1000 }).addTo(_lmMap);

      _lmMarkers[e.id] = { circle, ring, label: labelMarker };
    }
  });

  // Remove stale markers
  Object.keys(_lmMarkers).forEach(id => {
    if (!seen.has(parseInt(id))) {
      try { _lmMap.removeLayer(_lmMarkers[id].circle); } catch(e2) {}
      try { _lmMap.removeLayer(_lmMarkers[id].ring);   } catch(e2) {}
      try { _lmMap.removeLayer(_lmMarkers[id].label);  } catch(e2) {}
      delete _lmMarkers[id];
    }
  });

  // Auto-fit on first load
  if (withGps.length > 0 && !_lmBoundsFit) {
    _lmBoundsFit = true;
    setTimeout(() => {
      if (!_lmMap) return;
      _lmMap.invalidateSize();
      if (withGps.length === 1) {
        _lmMap.setView([parseFloat(withGps[0].latitude), parseFloat(withGps[0].longitude)], 15);
      } else {
        const bounds = L.latLngBounds(withGps.map(e => [parseFloat(e.latitude), parseFloat(e.longitude)]));
        _lmMap.fitBounds(bounds, { padding: [60, 60], maxZoom: 15 });
      }
    }, 300);
  }
}
function lmSelectEngineer(id) {
  _lmSelected = id;
  const e = _lmData.find(x => x.id === id);
  if (!e) return;

  // Highlight card in sidebar list
  document.querySelectorAll('.lm-eng-card').forEach(c =>
    c.classList.toggle('selected', parseInt(c.dataset.id) === id)
  );

  // Pan map + open popup
  if (e.latitude && e.longitude && _lmMap && typeof L !== 'undefined') {
    const lat = parseFloat(e.latitude), lng = parseFloat(e.longitude);
    _lmMap.setView([lat, lng], 15, { animate: true });
    if (_lmMarkers[id] && _lmMarkers[id].circle) {
      setTimeout(() => _lmMarkers[id].circle.openPopup(), 350);
    }
  }
}
function lmShowTooltip() {}
function lmHideTooltip() {}

function lmSetFilter(f, btn) {
  _lmFilter = f;
  document.querySelectorAll('#liveMapSidebar .lm-filters button').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  lmRenderList();
}

function lmFilterList() {
  lmRenderList();
}

// ─── JOBS ─────────────────────────────────────────────────
async function renderJobs() {
  setContent('<div class="loading"><div class="spinner"></div> Loading jobs...</div>');
  const r = await api('GET', '/admin/jobs');
  if (!r.success) { setContent('<div class="empty">Failed to load jobs</div>'); return; }
  const statuses = ['all','scheduled','pending','assigned','accepted','on_the_way','arrived','working','completed','cancelled'];
  let html = `
  <div class="filters">
    ${statuses.map(s => `<button class="filter-chip ${s==='all'?'active':''}" onclick="filterJobs('${s}',this)">${s==='all'?'All Jobs':s.replace('_',' ')}</button>`).join('')}
  </div>
  <div class="card">
    <div class="card-header">
      <div class="card-title">All Jobs</div>
      <div class="card-actions">
        <input class="search-bar" placeholder="🔍 Search job / customer..." oninput="searchJobsTable(this.value)" style="width:min(200px,100%)">
      </div>
    </div>
    <div style="overflow-x:auto">
    <table id="jobsTable">
      <thead><tr><th>Job #</th><th>Service</th><th>Customer</th><th class="hide-mobile">Address</th><th class="hide-mobile">Engineer</th><th class="hide-mobile">Amount</th><th class="hide-mobile">Scheduled</th><th>Status</th><th class="hide-mobile">Created</th><th>Action</th></tr></thead>
      <tbody>
        ${r.data.map(j => {
          const isScheduledFuture = j.scheduled_date && j.scheduled_date > new Date().toISOString().slice(0,10) && j.status === 'pending';
          const rowStyle = isScheduledFuture ? 'background:rgba(245,158,11,.06);' : '';
          return `<tr data-status="${j.status}" data-scheduled="${isScheduledFuture?'true':'false'}" data-search="${(j.job_number+(j.customer||'')+(j.engineer||'')).toLowerCase()}" style="${rowStyle}">
          <td><span style="font-family:'Geist Mono',monospace;font-size:12px;color:var(--accent)">${j.job_number}</span></td>
          <td>${j.service}</td>
          <td><div>${j.customer}</div><div style="font-size:11px;color:var(--muted)">${j.customer_phone}</div></td>
          <td class="hide-mobile" style="max-width:160px;font-size:12px;color:var(--muted)">${j.address.substring(0,60)}${j.address.length>60?'…':''}</td>
          <td class="hide-mobile">${j.engineer}</td>
          <td class="hide-mobile" style="font-family:'Geist Mono',monospace">₹${Number(j.amount).toLocaleString()}</td>
          <td class="hide-mobile" style="font-size:12px">${j.scheduled_date ? `<div style="font-weight:600;color:var(--amber)">📅 ${j.scheduled_date}</div><div style="font-size:11px;color:var(--muted)">${j.slot_label||''}</div>` : '<span style="color:var(--muted)">ASAP</span>'}</td>
          <td><span class="badge badge-${j.status}">${j.status.replace('_',' ')}</span></td>
          <td class="hide-mobile" style="font-size:12px;color:var(--muted)">${timeAgo(j.created_at)}</td>
          <td><div style="display:flex;gap:6px;flex-wrap:wrap">
            <button class="btn btn-ghost btn-sm" onclick="openJobModal(${j.job_id})">✏ Edit</button>
            ${j.status==='pending'||j.engineer==='Unassigned'?`<button class="btn btn-success btn-sm" onclick="openAssign(${j.job_id})">Assign</button>`:''}
            ${isScheduledFuture?`<button class="btn btn-sm" style="background:rgba(245,158,11,.2);color:var(--amber);border:1px solid rgba(245,158,11,.3)" onclick="broadcastScheduledJob(${j.job_id},'${j.job_number}')">📢 Broadcast Now</button>`:''}
            ${j.status==='pending'?`<button class="btn btn-sm" style="background:rgba(79,124,255,.15);color:var(--accent);border:1px solid rgba(79,124,255,.3)" onclick="notifyZoneEngineers(${j.job_id},'${j.job_number}',this)" title="Notify engineers in matching zones">🗾 Zone Notify</button>`:''}
          </div></td>
        </tr>`;}).join('')}
      </tbody>
    </table>
    </div>
  </div>`;
  setContent(html);
}

function filterJobs(status, el) {
  document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
  el.classList.add('active');
  const today = new Date().toISOString().slice(0,10);
  const rows = document.querySelectorAll('#jobsTable tbody tr');
  rows.forEach(r => {
    if (status === 'all') { r.style.display = ''; return; }
    if (status === 'scheduled') {
      r.style.display = r.dataset.scheduled === 'true' ? '' : 'none';
      return;
    }
    r.style.display = r.dataset.status === status ? '' : 'none';
  });
}

function searchJobsTable(q) {
  const rows = document.querySelectorAll('#jobsTable tbody tr');
  rows.forEach(r => r.style.display = r.dataset.search.includes(q.toLowerCase()) ? '' : 'none');
}

</script>
<style>
*{margin:0;padding:0;box-sizing:border-box}

/* ══════════════════════════════════════════════════
   12 THEMES — zone-specific CSS vars
══════════════════════════════════════════════════ */
:root,[data-theme="dark-nebula"]{
  --card:var(--bg2);
  --bg:#09090E;--bg2:#0F1018;--bg3:#161824;--bg4:#1E2130;
  --border:#23283C;--border2:#2E3550;
  --text:#E2E4F0;--muted:#7E85A8;--hint:#424868;
  --accent:#4F7CFF;--accent2:#7B5FFF;--green:#22D99F;--amber:#F5A623;--red:#FF4D6A;--cyan:#22D3EE;
  --sidebar:248px;--radius:10px;
  --sidebar-bg:#0C0E1A;--sidebar-border:#1E2238;
  --topbar-bg:#0F1018;--topbar-border:#1E2338;
  --logo-grad:linear-gradient(135deg,#4F7CFF,#7B5FFF);
  --nav-active-bg:rgba(79,124,255,.12);--nav-active-color:#4F7CFF;--nav-bar-color:#4F7CFF;
  --nav-section-color:#353B5C;
  --s1:#4F7CFF;--s2:#22D99F;--s3:#F5A623;--s4:#A78BFA;--s5:#22D3EE;--s6:#FF6B9D;
  --card-accent:linear-gradient(90deg,#4F7CFF,#7B5FFF);
  --btn-primary-bg:#4F7CFF;--btn-primary-hover:#3D6BFF;
}
[data-theme="arctic-light"]{
  --card:var(--bg2);
  --bg:#EEF2F8;--bg2:#FFFFFF;--bg3:#F5F8FD;--bg4:#E8EEF7;
  --border:#DDE4EF;--border2:#C8D3E8;
  --text:#18213A;--muted:#637090;--hint:#9AA4BE;
  --accent:#1E6BF1;--accent2:#6C47FF;--green:#16A34A;--amber:#D97706;--red:#DC2626;--cyan:#0891B2;
  --sidebar:248px;--radius:10px;
  --sidebar-bg:#1A2540;--sidebar-border:#253560;
  --topbar-bg:#FFFFFF;--topbar-border:#DDE4EF;
  --logo-grad:linear-gradient(135deg,#1E6BF1,#6C47FF);
  --nav-active-bg:rgba(30,107,241,.12);--nav-active-color:#1E6BF1;--nav-bar-color:#1E6BF1;
  --nav-section-color:#5A6885;
  --s1:#1E6BF1;--s2:#16A34A;--s3:#D97706;--s4:#7C3AED;--s5:#0891B2;--s6:#DB2777;
  --card-accent:linear-gradient(90deg,#1E6BF1,#6C47FF);
  --btn-primary-bg:#1E6BF1;--btn-primary-hover:#1A5CD4;
}
[data-theme="arctic-light"] .sidebar{background:var(--sidebar-bg)!important;border-right:1px solid var(--sidebar-border)!important}
[data-theme="arctic-light"] .sidebar-logo{border-bottom-color:var(--sidebar-border)!important}
[data-theme="arctic-light"] .logo-text{color:#EEF2FF!important}
[data-theme="arctic-light"] .logo-text span{color:#8BA0CC!important}
[data-theme="arctic-light"] .nav-section{color:#506080!important}
[data-theme="arctic-light"] .nav-item{color:#8BA0CC!important}
[data-theme="arctic-light"] .nav-item:hover{background:rgba(138,160,204,.1)!important;color:#EEF2FF!important}
[data-theme="arctic-light"] .nav-item.active{background:rgba(30,107,241,.18)!important;color:#93C5FD!important}
[data-theme="arctic-light"] .nav-item.active::before{background:#1E6BF1!important}
[data-theme="arctic-light"] .sidebar-footer{border-top-color:#253560!important}
[data-theme="arctic-light"] .user-name{color:#EEF2FF!important}
[data-theme="arctic-light"] .user-role{color:#8BA0CC!important}
[data-theme="arctic-light"] .logout-btn{color:#8BA0CC!important}

[data-theme="forest-pro"]{
  --card:var(--bg2);
  --bg:#071510;--bg2:#0D2018;--bg3:#112A1E;--bg4:#163525;
  --border:#1E4A2E;--border2:#286040;
  --text:#DCFCE7;--muted:#86EFAC;--hint:#4ADE8066;
  --accent:#4ADE80;--accent2:#22C55E;--green:#86EFAC;--amber:#FCD34D;--red:#F87171;--cyan:#6EE7B7;
  --sidebar:248px;--radius:10px;
  --sidebar-bg:#061008;--sidebar-border:#193D22;
  --topbar-bg:#0D2018;--topbar-border:#1E4A2E;
  --logo-grad:linear-gradient(135deg,#22C55E,#4ADE80);
  --nav-active-bg:rgba(74,222,128,.14);--nav-active-color:#4ADE80;--nav-bar-color:#4ADE80;
  --nav-section-color:#286040;
  --s1:#4ADE80;--s2:#22D3EE;--s3:#FCD34D;--s4:#C084FC;--s5:#F87171;--s6:#FB923C;
  --card-accent:linear-gradient(90deg,#22C55E,#4ADE80);
  --btn-primary-bg:#22C55E;--btn-primary-hover:#16A34A;
}
[data-theme="crimson-dark"]{
  --card:var(--bg2);
  --bg:#0F0A0A;--bg2:#1A0F0F;--bg3:#220D0D;--bg4:#2B1010;
  --border:#4A1A1A;--border2:#6B2020;
  --text:#FEE2E2;--muted:#FCA5A5;--hint:#7F1D1D;
  --accent:#F87171;--accent2:#FB923C;--green:#6EE7B7;--amber:#FCD34D;--red:#EF4444;--cyan:#67E8F9;
  --sidebar:248px;--radius:10px;
  --sidebar-bg:#0A0505;--sidebar-border:#3D1010;
  --topbar-bg:#1A0F0F;--topbar-border:#4A1A1A;
  --logo-grad:linear-gradient(135deg,#EF4444,#FB923C);
  --nav-active-bg:rgba(248,113,113,.15);--nav-active-color:#FCA5A5;--nav-bar-color:#F87171;
  --nav-section-color:#6B2020;
  --s1:#F87171;--s2:#6EE7B7;--s3:#FCD34D;--s4:#C084FC;--s5:#67E8F9;--s6:#FB923C;
  --card-accent:linear-gradient(90deg,#EF4444,#FB923C);
  --btn-primary-bg:#EF4444;--btn-primary-hover:#DC2626;
}
[data-theme="slate-pro"]{
  --card:var(--bg2);
  --bg:#F3F6FA;--bg2:#FFFFFF;--bg3:#EEF2F8;--bg4:#E5EBF4;
  --border:#DCE3EE;--border2:#C5CFE0;
  --text:#18243C;--muted:#5E7090;--hint:#8FA0B8;
  --accent:#3B82F6;--accent2:#8B5CF6;--green:#10B981;--amber:#F59E0B;--red:#EF4444;--cyan:#06B6D4;
  --sidebar:248px;--radius:10px;
  --sidebar-bg:#0C1428;--sidebar-border:#1A2840;
  --topbar-bg:#FFFFFF;--topbar-border:#DCE3EE;
  --logo-grad:linear-gradient(135deg,#3B82F6,#8B5CF6);
  --nav-active-bg:rgba(59,130,246,.16);--nav-active-color:#93C5FD;--nav-bar-color:#3B82F6;
  --nav-section-color:#2E4060;
  --s1:#3B82F6;--s2:#10B981;--s3:#F59E0B;--s4:#8B5CF6;--s5:#06B6D4;--s6:#F43F5E;
  --card-accent:linear-gradient(90deg,#3B82F6,#8B5CF6);
  --btn-primary-bg:#3B82F6;--btn-primary-hover:#2563EB;
}
[data-theme="slate-pro"] .sidebar{background:var(--sidebar-bg)!important;border-right:1px solid var(--sidebar-border)!important}
[data-theme="slate-pro"] .sidebar-logo{border-bottom-color:#1A2840!important}
[data-theme="slate-pro"] .logo-text{color:#EEF2FF!important}
[data-theme="slate-pro"] .logo-text span{color:#7A98C0!important}
[data-theme="slate-pro"] .nav-section{color:#3A5A80!important}
[data-theme="slate-pro"] .nav-item{color:#7A98C0!important}
[data-theme="slate-pro"] .nav-item:hover{background:rgba(122,152,192,.1)!important;color:#EEF2FF!important}
[data-theme="slate-pro"] .nav-item.active{background:rgba(59,130,246,.2)!important;color:#93C5FD!important}
[data-theme="slate-pro"] .nav-item.active::before{background:#3B82F6!important}
[data-theme="slate-pro"] .sidebar-footer{border-top-color:#1A2840!important}
[data-theme="slate-pro"] .user-name{color:#EEF2FF!important}
[data-theme="slate-pro"] .user-role{color:#7A98C0!important}
[data-theme="slate-pro"] .logout-btn{color:#7A98C0!important}

[data-theme="rose-gold"]{
  --card:var(--bg2);
  --bg:#180B10;--bg2:#251018;--bg3:#311521;--bg4:#3D1A28;
  --border:#5C2038;--border2:#7A2A4A;
  --text:#FFE4EE;--muted:#E8A0B8;--hint:#A05070;
  --accent:#FF6B9D;--accent2:#FF3D7A;--green:#4ADEAA;--amber:#FBBF24;--red:#FF4D6A;--cyan:#60EFDF;
  --sidebar:248px;--radius:10px;
  --sidebar-bg:#100608;--sidebar-border:#4A1528;
  --topbar-bg:#251018;--topbar-border:#5C2038;
  --logo-grad:linear-gradient(135deg,#FF6B9D,#FF3D7A);
  --nav-active-bg:rgba(255,107,157,.15);--nav-active-color:#FF6B9D;--nav-bar-color:#FF6B9D;
  --nav-section-color:#7A2A4A;
  --s1:#FF6B9D;--s2:#4ADEAA;--s3:#FBBF24;--s4:#A78BFA;--s5:#60EFDF;--s6:#FB923C;
  --card-accent:linear-gradient(90deg,#FF6B9D,#FF3D7A);
  --btn-primary-bg:#FF3D7A;--btn-primary-hover:#E8005E;
}
[data-theme="ocean-breeze"]{
  --card:var(--bg2);
  --bg:#EBF8F8;--bg2:#FFFFFF;--bg3:#D2EEEE;--bg4:#BBE5E5;
  --border:#8CCCCC;--border2:#5CBABA;
  --text:#0A3535;--muted:#2B6B6B;--hint:#5AABAB;
  --accent:#0D9488;--accent2:#0284C7;--green:#059669;--amber:#D97706;--red:#DC2626;--cyan:#0EA5E9;
  --sidebar:248px;--radius:10px;
  --sidebar-bg:#0C3030;--sidebar-border:#184848;
  --topbar-bg:#FFFFFF;--topbar-border:#8CCCCC;
  --logo-grad:linear-gradient(135deg,#0D9488,#0284C7);
  --nav-active-bg:rgba(13,148,136,.14);--nav-active-color:#5EEAD4;--nav-bar-color:#0D9488;
  --nav-section-color:#285858;
  --s1:#0D9488;--s2:#059669;--s3:#D97706;--s4:#7C3AED;--s5:#0284C7;--s6:#DB2777;
  --card-accent:linear-gradient(90deg,#0D9488,#0284C7);
  --btn-primary-bg:#0D9488;--btn-primary-hover:#0F766E;
}
[data-theme="ocean-breeze"] .sidebar{background:var(--sidebar-bg)!important;border-right:1px solid var(--sidebar-border)!important}
[data-theme="ocean-breeze"] .logo-text{color:#CCFBF1!important}
[data-theme="ocean-breeze"] .logo-text span{color:#5EEAD4!important}
[data-theme="ocean-breeze"] .nav-item{color:#6DCFCF!important}
[data-theme="ocean-breeze"] .nav-item:hover{background:rgba(94,234,212,.08)!important;color:#CCFBF1!important}
[data-theme="ocean-breeze"] .nav-item.active{background:rgba(13,148,136,.2)!important;color:#5EEAD4!important}
[data-theme="ocean-breeze"] .nav-item.active::before{background:#0D9488!important}
[data-theme="ocean-breeze"] .sidebar-footer{border-top-color:#184848!important}
[data-theme="ocean-breeze"] .user-name{color:#CCFBF1!important}
[data-theme="ocean-breeze"] .user-role{color:#5EEAD4!important}
[data-theme="ocean-breeze"] .logout-btn{color:#6DCFCF!important}

[data-theme="midnight-purple"]{
  --card:var(--bg2);
  --bg:#0A0718;--bg2:#130D28;--bg3:#1B1338;--bg4:#231848;
  --border:#3A2870;--border2:#4E3590;
  --text:#EDE6FF;--muted:#A090D0;--hint:#604EA0;
  --accent:#A78BFA;--accent2:#7C3AED;--green:#34D399;--amber:#FBBF24;--red:#F87171;--cyan:#67E8F9;
  --sidebar:248px;--radius:10px;
  --sidebar-bg:#070412;--sidebar-border:#2E1E60;
  --topbar-bg:#130D28;--topbar-border:#3A2870;
  --logo-grad:linear-gradient(135deg,#A78BFA,#7C3AED);
  --nav-active-bg:rgba(167,139,250,.15);--nav-active-color:#A78BFA;--nav-bar-color:#A78BFA;
  --nav-section-color:#4E3590;
  --s1:#A78BFA;--s2:#34D399;--s3:#FBBF24;--s4:#F87171;--s5:#67E8F9;--s6:#FB7185;
  --card-accent:linear-gradient(90deg,#A78BFA,#7C3AED);
  --btn-primary-bg:#7C3AED;--btn-primary-hover:#6D28D9;
}
[data-theme="sunset-orange"]{
  --card:var(--bg2);
  --bg:#120800;--bg2:#1E1000;--bg3:#2A1800;--bg4:#362000;
  --border:#5C3A00;--border2:#7A5000;
  --text:#FFF0D6;--muted:#D4A060;--hint:#8B6020;
  --accent:#FB923C;--accent2:#F59E0B;--green:#4ADE80;--amber:#FCD34D;--red:#F87171;--cyan:#67E8F9;
  --sidebar:248px;--radius:10px;
  --sidebar-bg:#0C0500;--sidebar-border:#4A2E00;
  --topbar-bg:#1E1000;--topbar-border:#5C3A00;
  --logo-grad:linear-gradient(135deg,#FB923C,#F59E0B);
  --nav-active-bg:rgba(251,146,60,.15);--nav-active-color:#FB923C;--nav-bar-color:#FB923C;
  --nav-section-color:#7A5000;
  --s1:#FB923C;--s2:#4ADE80;--s3:#F59E0B;--s4:#A78BFA;--s5:#67E8F9;--s6:#F87171;
  --card-accent:linear-gradient(90deg,#FB923C,#F59E0B);
  --btn-primary-bg:#FB923C;--btn-primary-hover:#EA7C22;
}
[data-theme="cyberpunk"]{
  --card:var(--bg2);
  --bg:#020808;--bg2:#050F0F;--bg3:#081818;--bg4:#0C2020;
  --border:#0D4040;--border2:#105555;
  --text:#E0FFFF;--muted:#40B0B0;--hint:#206060;
  --accent:#00FFF5;--accent2:#FF00AA;--green:#00FF7F;--amber:#FFD700;--red:#FF3366;--cyan:#00FFF5;
  --sidebar:248px;--radius:3px;
  --sidebar-bg:#020808;--sidebar-border:#0A3535;
  --topbar-bg:#040C0C;--topbar-border:#0D4040;
  --logo-grad:linear-gradient(135deg,#00FFF5,#FF00AA);
  --nav-active-bg:rgba(0,255,245,.1);--nav-active-color:#00FFF5;--nav-bar-color:#00FFF5;
  --nav-section-color:#105555;
  --s1:#00FFF5;--s2:#00FF7F;--s3:#FFD700;--s4:#FF00AA;--s5:#FF3366;--s6:#7B68EE;
  --card-accent:linear-gradient(90deg,#00FFF5,#FF00AA);
  --btn-primary-bg:#00FFF5;--btn-primary-hover:#00D4CC;
}
[data-theme="cyberpunk"] .btn-primary{color:#020808!important}
[data-theme="cyberpunk"] .card{border-color:#0D4040;box-shadow:0 0 10px rgba(0,255,245,.05)}
[data-theme="cyberpunk"] .nav-item.active::before{box-shadow:0 0 8px #00FFF5}

[data-theme="mocha"]{
  --card:var(--bg2);
  --bg:#F8F2EA;--bg2:#FFFBF4;--bg3:#F2E8D5;--bg4:#EAD9BF;
  --border:#D0B890;--border2:#BA9E6A;
  --text:#3A2205;--muted:#7A5030;--hint:#B09060;
  --accent:#92400E;--accent2:#C2410C;--green:#166534;--amber:#B45309;--red:#B91C1C;--cyan:#0E7490;
  --sidebar:248px;--radius:10px;
  --sidebar-bg:#2C1A08;--sidebar-border:#4A2E10;
  --topbar-bg:#FFFBF4;--topbar-border:#D0B890;
  --logo-grad:linear-gradient(135deg,#92400E,#C2410C);
  --nav-active-bg:rgba(146,64,14,.12);--nav-active-color:#FED7AA;--nav-bar-color:#92400E;
  --nav-section-color:#4A2E10;
  --s1:#92400E;--s2:#166534;--s3:#B45309;--s4:#7C3AED;--s5:#0E7490;--s6:#C2410C;
  --card-accent:linear-gradient(90deg,#92400E,#C2410C);
  --btn-primary-bg:#92400E;--btn-primary-hover:#7C3510;
}
[data-theme="mocha"] .sidebar{background:var(--sidebar-bg)!important;border-right:1px solid var(--sidebar-border)!important}
[data-theme="mocha"] .logo-text{color:#FED7AA!important}
[data-theme="mocha"] .logo-text span{color:#FDBA74!important}
[data-theme="mocha"] .nav-item{color:#C09060!important}
[data-theme="mocha"] .nav-item:hover{background:rgba(254,215,170,.08)!important;color:#FED7AA!important}
[data-theme="mocha"] .nav-item.active{background:rgba(146,64,14,.2)!important;color:#FED7AA!important}
[data-theme="mocha"] .nav-item.active::before{background:#92400E!important}
[data-theme="mocha"] .sidebar-footer{border-top-color:#4A2E10!important}
[data-theme="mocha"] .user-name{color:#FED7AA!important}
[data-theme="mocha"] .user-role{color:#FDBA74!important}
[data-theme="mocha"] .logout-btn{color:#C09060!important}

[data-theme="neon-tokyo"]{
  --card:var(--bg2);
  --bg:#05000A;--bg2:#0C001A;--bg3:#140028;--bg4:#1C0038;
  --border:#3D006B;--border2:#5500A0;
  --text:#F0E0FF;--muted:#B070E0;--hint:#703090;
  --accent:#E040FB;--accent2:#7B1FA2;--green:#00E676;--amber:#FFD740;--red:#FF1744;--cyan:#00E5FF;
  --sidebar:248px;--radius:8px;
  --sidebar-bg:#030008;--sidebar-border:#2A0050;
  --topbar-bg:#0C001A;--topbar-border:#3D006B;
  --logo-grad:linear-gradient(135deg,#E040FB,#00E5FF);
  --nav-active-bg:rgba(224,64,251,.15);--nav-active-color:#E040FB;--nav-bar-color:#E040FB;
  --nav-section-color:#5500A0;
  --s1:#E040FB;--s2:#00E676;--s3:#FFD740;--s4:#00E5FF;--s5:#FF1744;--s6:#FF6D00;
  --card-accent:linear-gradient(90deg,#E040FB,#00E5FF);
  --btn-primary-bg:#E040FB;--btn-primary-hover:#CC00E8;
}
[data-theme="neon-tokyo"] .card{box-shadow:0 0 14px rgba(224,64,251,.07)}
[data-theme="neon-tokyo"] .nav-item.active::before{box-shadow:0 0 8px #E040FB}


/* ════════════════════════════════════════
   BASE STYLES
════════════════════════════════════════ */
body{
  font-family:'Geist',system-ui,sans-serif;
  background:var(--bg);color:var(--text);
  min-height:100vh;overflow:hidden;
  font-size:14px;line-height:1.5;
  transition:background .3s,color .3s;
  -webkit-font-smoothing:antialiased;
}

/* ─── Theme picker ─── */
#themeToggleBtn{
  position:fixed;bottom:24px;right:24px;z-index:8000;
  width:44px;height:44px;border-radius:50%;
  background:var(--logo-grad);border:none;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  font-size:18px;box-shadow:0 4px 20px rgba(0,0,0,.4);
  transition:transform .2s,box-shadow .2s;
}
#themeToggleBtn:hover{transform:scale(1.1);box-shadow:0 6px 28px rgba(0,0,0,.5)}
#themePanel{
  position:fixed;bottom:78px;right:24px;z-index:8000;
  background:var(--bg2);border:1px solid var(--border2);
  border-radius:14px;padding:18px;width:256px;
  box-shadow:0 12px 48px rgba(0,0,0,.55);
  opacity:0;pointer-events:none;
  transform:translateY(10px) scale(.97);
  transition:opacity .22s,transform .22s;
}
#themePanel.open{opacity:1;pointer-events:all;transform:none}
#themePanel h4{font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:12px}
.theme-tabs{display:flex;gap:4px;margin-bottom:12px;background:var(--bg3);border-radius:8px;padding:3px}
.theme-tab{flex:1;padding:5px;border:none;background:none;color:var(--muted);font-size:11px;font-weight:600;cursor:pointer;border-radius:6px;font-family:inherit;transition:all .15s}
.theme-tab.active{background:var(--bg2);color:var(--text);box-shadow:0 1px 4px rgba(0,0,0,.2)}
.theme-pane{display:none}.theme-pane.active{display:block}
.theme-option{
  display:flex;align-items:center;gap:10px;
  padding:8px 10px;border-radius:8px;cursor:pointer;
  border:1.5px solid transparent;margin-bottom:4px;transition:all .16s;
}
.theme-option:hover{background:var(--bg3);border-color:var(--border2)}
.theme-option.active{border-color:var(--accent);background:var(--nav-active-bg)}
.theme-swatch{width:28px;height:28px;border-radius:7px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:13px}
.theme-name{font-size:12px;font-weight:600;color:var(--text);flex:1}
.theme-check{color:var(--accent);font-size:14px;opacity:0;transition:opacity .12s}
.theme-option.active .theme-check{opacity:1}
.theme-info{flex:1}
.theme-name{font-size:12px;font-weight:600;color:var(--text)}
.theme-desc{font-size:10.5px;color:var(--muted);margin-top:1px}
.tg-btn{
  padding:4px 12px;border:1px solid var(--border);border-radius:6px;
  background:transparent;color:var(--muted);font-size:11px;font-weight:600;
  cursor:pointer;font-family:inherit;transition:all .14s;
}
.tg-btn.tg-active{background:var(--nav-active-bg);color:var(--nav-active-color);border-color:var(--accent)}
.tg-btn:hover:not(.tg-active){background:var(--bg3);color:var(--text)}

/* ─── Sidebar ─── */
.sidebar{
  width:var(--sidebar);
  background:var(--sidebar-bg,var(--bg2));
  border-right:1px solid var(--sidebar-border,var(--border));
  display:flex;flex-direction:column;
  position:fixed;top:0;left:0;height:100vh;z-index:100;
  transition:transform .3s cubic-bezier(.4,0,.2,1),background .3s,border-color .3s;
}
.sidebar-logo{
  padding:20px 18px;
  border-bottom:1px solid var(--sidebar-border,var(--border));
  display:flex;align-items:center;gap:10px;flex-shrink:0;
}
.logo-icon{
  width:34px;height:34px;min-width:34px;
  background:var(--logo-grad);border-radius:9px;
  display:flex;align-items:center;justify-content:center;
  font-size:15px;font-weight:700;color:#fff;flex-shrink:0;
}
.logo-text{font-weight:700;font-size:14px;line-height:1.25;letter-spacing:-.01em}
.logo-text span{display:block;font-size:10px;font-weight:400;color:var(--muted);margin-top:1px;letter-spacing:.02em}

.nav{flex:1;overflow-y:auto;padding:10px 0;scrollbar-width:thin;scrollbar-color:var(--border2) transparent}
.nav::-webkit-scrollbar{width:3px}
.nav::-webkit-scrollbar-thumb{background:var(--border2);border-radius:3px}

.nav-section{
  padding:14px 16px 4px;
  font-size:9.5px;font-weight:700;letter-spacing:.12em;
  text-transform:uppercase;color:var(--nav-section-color,var(--hint));
}
.nav-item{
  display:flex;align-items:center;gap:9px;
  padding:8px 16px 8px 18px;cursor:pointer;
  color:var(--muted);transition:all .14s;
  position:relative;margin:1px 8px;border-radius:8px;
}
.nav-item:hover{background:rgba(255,255,255,.05);color:var(--text)}
.nav-item.active{
  background:var(--nav-active-bg);color:var(--nav-active-color);
  font-weight:600;
}
.nav-item.active::before{
  content:'';position:absolute;left:-8px;top:50%;
  transform:translateY(-50%);
  width:3px;height:18px;
  background:var(--nav-bar-color);border-radius:2px;
}
.nav-icon{font-size:15px;width:18px;text-align:center;flex-shrink:0;opacity:.85}
.nav-item.active .nav-icon{opacity:1}
.nav-badge{
  margin-left:auto;background:var(--red);color:#fff;
  font-size:9.5px;font-weight:700;padding:1px 6px;
  border-radius:10px;animation:badgePulse 2.5s infinite;
}
.live-dot{
  width:6px;height:6px;border-radius:50%;background:var(--green);
  display:inline-block;animation:pulse 2s infinite;margin-left:auto;
}
.sidebar-footer{padding:14px;border-top:1px solid var(--sidebar-border,var(--border));flex-shrink:0}
.user-card{
  display:flex;align-items:center;gap:9px;
  padding:6px 8px;border-radius:8px;transition:background .15s;
}
.user-card:hover{background:rgba(255,255,255,.05)}
.user-avatar{
  width:30px;height:30px;min-width:30px;
  background:var(--logo-grad);border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-weight:700;font-size:12px;color:#fff;flex-shrink:0;
}
.user-info{flex:1;min-width:0}
.user-name{font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.user-role{font-size:10px;color:var(--muted)}
.logout-btn{
  background:none;border:none;color:var(--muted);
  cursor:pointer;padding:6px;border-radius:7px;font-size:15px;
  transition:color .15s,background .15s,transform .22s;
}
.logout-btn:hover{color:var(--red);background:rgba(255,77,106,.1);transform:rotate(180deg)}

/* ─── Main layout ─── */
.main{margin-left:var(--sidebar);flex:1;display:flex;flex-direction:column;min-height:100vh;min-width:0}
button *{pointer-events:none}
.app-hidden{display:none!important}

/* ─── Topbar ─── */
.topbar{
  background:var(--topbar-bg,var(--bg2));
  border-bottom:1px solid var(--topbar-border,var(--border));
  padding:0 24px;height:56px;
  display:flex;align-items:center;justify-content:space-between;
  position:sticky;top:0;z-index:50;
  backdrop-filter:blur(8px);
}
.topbar-title{font-size:16px;font-weight:700;letter-spacing:-.02em}
.topbar-right{display:flex;align-items:center;gap:10px}
.topbar-btn{
  background:var(--bg3);border:1px solid var(--border);
  color:var(--muted);padding:6px 13px;border-radius:7px;
  cursor:pointer;font-size:12px;font-family:inherit;
  display:flex;align-items:center;gap:5px;transition:all .14s;
}
.topbar-btn:hover{border-color:var(--accent);color:var(--accent)}

/* ─── Content ─── */
.content{flex:1;padding:24px;overflow-y:auto;max-height:calc(100vh - 56px);overflow-x:hidden}

/* ─── Stat grid ─── */
.stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:14px;margin-bottom:24px}
.stat-card{
  background:var(--bg2);border:1px solid var(--border);
  border-radius:var(--radius);padding:18px 20px;
  position:relative;overflow:hidden;transition:border-color .18s,transform .18s;
  cursor:default;
}
.stat-card:hover{border-color:var(--border2);transform:translateY(-1px)}
.stat-card::after{
  content:'';position:absolute;right:-14px;bottom:-14px;
  width:64px;height:64px;border-radius:50%;opacity:.08;
}
.stat-card.blue::after{background:var(--s1)}.stat-card.green::after{background:var(--s2)}
.stat-card.amber::after{background:var(--s3)}.stat-card.purple::after{background:var(--s4)}
.stat-card.cyan::after{background:var(--s5)}.stat-card.red::after{background:var(--red)}
.stat-label{font-size:11px;font-weight:600;color:var(--muted);margin-bottom:10px;letter-spacing:.01em}
.stat-value{font-size:26px;font-weight:700;line-height:1;font-family:'Geist Mono',monospace;letter-spacing:-.02em}
.stat-card.blue .stat-value{color:var(--s1)}.stat-card.green .stat-value{color:var(--s2)}
.stat-card.amber .stat-value{color:var(--s3)}.stat-card.red .stat-value{color:var(--red)}
.stat-card.purple .stat-value{color:var(--s4)}.stat-card.cyan .stat-value{color:var(--s5)}
.stat-sub{font-size:11px;color:var(--hint);margin-top:5px}

/* ─── Cards ─── */
.card{
  background:var(--bg2);border:1px solid var(--border);
  border-radius:var(--radius);overflow:hidden;
  margin-bottom:18px;position:relative;
}
.card::before{
  content:'';position:absolute;top:0;left:0;right:0;height:2px;
  background:var(--card-accent,var(--accent));
  border-radius:var(--radius) var(--radius) 0 0;pointer-events:none;
}
.card-header{
  padding:14px 18px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;
  gap:12px;
}
.card-title{font-size:13px;font-weight:700;letter-spacing:-.01em}
.card-actions{display:flex;gap:7px}

/* ─── Tables ─── */
table{width:100%;border-collapse:collapse}
thead tr{background:var(--bg3)}
th{
  padding:9px 14px;text-align:left;
  font-size:10.5px;font-weight:700;
  text-transform:uppercase;letter-spacing:.07em;
  color:var(--muted);white-space:nowrap;
}
td{padding:11px 14px;border-bottom:1px solid var(--border);font-size:12.5px;vertical-align:middle}
tr:last-child td{border-bottom:none}
tbody tr{transition:background .1s}
tbody tr:hover{background:var(--bg3)}

/* ─── Badges ─── */
.badge{
  display:inline-flex;align-items:center;gap:4px;
  padding:3px 9px;border-radius:20px;
  font-size:10.5px;font-weight:700;white-space:nowrap;letter-spacing:.01em;
}
.badge::before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor;opacity:.7}
.badge-pending{background:rgba(245,166,35,.14);color:var(--amber)}
.badge-assigned{background:rgba(79,124,255,.14);color:var(--accent)}
.badge-accepted,.badge-on_the_way{background:rgba(34,217,159,.12);color:var(--green)}
.badge-arrived,.badge-working{background:rgba(167,139,250,.14);color:var(--accent2)}
.badge-completed{background:rgba(34,217,159,.14);color:var(--green)}
.badge-cancelled{background:rgba(255,77,106,.14);color:var(--red)}
.badge-available{background:rgba(34,217,159,.14);color:var(--green)}
.badge-busy{background:rgba(245,166,35,.14);color:var(--amber)}
.badge-offline{background:rgba(100,100,130,.18);color:var(--hint)}
.badge-green{background:rgba(34,217,159,.14);color:var(--green)}
.badge-red{background:rgba(255,77,106,.14);color:var(--red)}
.badge-amber{background:rgba(245,166,35,.14);color:var(--amber)}

/* ─── Buttons ─── */
.btn{
  display:inline-flex;align-items:center;gap:5px;
  padding:7px 15px;border-radius:7px;
  font-size:12px;font-weight:600;cursor:pointer;
  border:1px solid transparent;font-family:inherit;
  transition:all .14s;letter-spacing:.01em;
}
.btn-primary{background:var(--btn-primary-bg,var(--accent));color:#fff;border-color:var(--btn-primary-bg,var(--accent))}
.btn-primary:hover{background:var(--btn-primary-hover,#3d6bff);transform:translateY(-1px)}
.btn-ghost{background:transparent;color:var(--muted);border-color:var(--border)}
.btn-ghost:hover{background:var(--bg3);color:var(--text);border-color:var(--border2)}
.btn-danger{background:rgba(255,77,106,.09);color:var(--red);border-color:rgba(255,77,106,.25)}
.btn-danger:hover{background:rgba(255,77,106,.18)}
.btn-sm{padding:4px 11px;font-size:11px;border-radius:6px}
.btn-success{background:rgba(34,217,159,.1);color:var(--green);border-color:rgba(34,217,159,.28)}
.btn-success:hover{background:rgba(34,217,159,.18)}
.btn:disabled{opacity:.5;cursor:not-allowed;transform:none!important}

/* ─── Forms ─── */
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px}
.form-group{display:flex;flex-direction:column;gap:5px;margin-bottom:14px}
label{
  font-size:11px;font-weight:700;color:var(--muted);
  text-transform:uppercase;letter-spacing:.06em;
}
input,select,textarea{
  background:var(--bg3);border:1px solid var(--border);
  color:var(--text);padding:9px 12px;border-radius:7px;
  font-size:13px;font-family:'Geist',inherit;width:100%;
  outline:none;transition:border-color .14s,box-shadow .14s;
}
input:focus,select:focus,textarea:focus{
  border-color:var(--accent);
  box-shadow:0 0 0 3px rgba(79,124,255,.12);
}
input::placeholder{color:var(--hint)}
textarea{resize:vertical;min-height:80px}
select option{background:var(--bg3)}

/* ─── Modals ─── */
.modal-overlay{
  position:fixed;inset:0;background:rgba(0,0,0,.7);
  z-index:999;display:none;align-items:center;justify-content:center;
  padding:20px;backdrop-filter:blur(4px);
}
.modal-overlay.open{display:flex}
.modal{
  background:var(--bg2);border:1px solid var(--border2);
  border-radius:14px;width:100%;max-width:520px;
  max-height:85vh;overflow-y:auto;
  box-shadow:0 24px 80px rgba(0,0,0,.5);
}
.modal-header{
  padding:18px 22px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;
}
.modal-title{font-size:15px;font-weight:700;letter-spacing:-.01em}
.modal-close{
  background:none;border:none;color:var(--muted);
  font-size:20px;cursor:pointer;line-height:1;
  width:28px;height:28px;display:flex;align-items:center;justify-content:center;
  border-radius:6px;transition:all .14s;
}
.modal-close:hover{background:var(--bg3);color:var(--text)}
.modal-body{padding:22px}
.modal-footer{
  padding:14px 22px;border-top:1px solid var(--border);
  display:flex;justify-content:flex-end;gap:8px;
}

/* ─── Login ─── */
.login-wrap{
  position:fixed;inset:0;z-index:9000;
  width:100vw;min-height:100vh;display:flex;
  align-items:center;justify-content:center;
  background:var(--bg);
  background-image:radial-gradient(ellipse at 20% 50%,rgba(79,124,255,.06) 0%,transparent 60%),
                   radial-gradient(ellipse at 80% 20%,rgba(123,95,255,.05) 0%,transparent 50%);
}
.login-card{
  background:var(--bg2);border:1px solid var(--border2);
  border-radius:18px;padding:38px 36px;
  width:100%;max-width:390px;
  box-shadow:0 20px 80px rgba(0,0,0,.35);
}
.login-title{font-size:22px;font-weight:800;letter-spacing:-.03em;margin-bottom:2px}
.login-sub{font-size:13px;color:var(--muted);margin-bottom:26px}
#loginBtn{
  position:relative;overflow:hidden;
}
#loginBtn::after{
  content:'';position:absolute;inset:0;
  background:linear-gradient(135deg,rgba(255,255,255,.12),transparent);
  opacity:0;transition:opacity .2s;
}
#loginBtn:hover::after{opacity:1}

/* ─── Map ─── */
.map-container{
  background:var(--bg3);border-radius:var(--radius);
  height:400px;position:relative;overflow:hidden;
  border:1px solid var(--border);
}

/* ─── Search bar ─── */
.search-bar{
  background:var(--bg3);border:1px solid var(--border);
  border-radius:7px;padding:7px 12px;color:var(--text);
  font-size:12px;font-family:'Geist',inherit;width:220px;
  outline:none;transition:border-color .14s;
}
.search-bar:focus{border-color:var(--accent)}
.search-bar::placeholder{color:var(--hint)}

/* ─── Filters ─── */
.filters{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px}
.filter-chip{
  padding:4px 13px;border-radius:20px;
  border:1px solid var(--border);font-size:11.5px;font-weight:600;
  cursor:pointer;background:transparent;color:var(--muted);
  font-family:inherit;transition:all .14s;
}
.filter-chip:hover{border-color:var(--border2);color:var(--text)}
.filter-chip.active{background:var(--nav-active-bg);border-color:var(--accent);color:var(--accent)}

/* ─── Avatar ─── */
.avatar{
  width:30px;height:30px;border-radius:50%;
  background:linear-gradient(135deg,var(--accent),var(--accent2));
  display:inline-flex;align-items:center;justify-content:center;
  font-weight:700;font-size:11px;color:#fff;flex-shrink:0;
}

/* ─── Alerts ─── */
.alert{padding:10px 14px;border-radius:8px;font-size:12.5px;margin-bottom:14px;display:flex;align-items:center;gap:8px}
.alert-error{background:rgba(255,77,106,.1);border:1px solid rgba(255,77,106,.28);color:var(--red)}
.alert-success{background:rgba(34,217,159,.1);border:1px solid rgba(34,217,159,.28);color:var(--green)}

/* ─── Toast ─── */
#toast{position:fixed;bottom:22px;left:22px;z-index:9999;display:flex;flex-direction:column;gap:6px}
.toast-item{
  padding:11px 16px;border-radius:9px;font-size:12.5px;font-weight:500;
  animation:slideIn .28s ease;box-shadow:0 4px 20px rgba(0,0,0,.4);
  max-width:320px;
}
.toast-success{background:#162d22;border:1px solid rgba(34,217,159,.35);color:var(--green)}
.toast-error{background:#2d161c;border:1px solid rgba(255,77,106,.35);color:var(--red)}
@keyframes slideIn{from{transform:translateX(-100%);opacity:0}to{transform:none;opacity:1}}

/* ─── Loading / Empty ─── */
.loading{display:flex;align-items:center;justify-content:center;padding:48px;color:var(--hint);font-size:13px;gap:8px}
.spinner{width:15px;height:15px;border:2px solid var(--border2);border-top-color:var(--accent);border-radius:50%;animation:spin .5s linear infinite;flex-shrink:0}
@keyframes spin{to{transform:rotate(360deg)}}
.empty{text-align:center;padding:52px;color:var(--hint);font-size:13px}
.empty-icon{font-size:30px;margin-bottom:10px;opacity:.35}

/* ─── Chart ─── */
.chart-wrap{padding:18px;height:200px;position:relative}

/* ─── Misc animations ─── */
@keyframes badgePulse{0%,100%{box-shadow:0 0 0 0 rgba(255,77,106,.5)}50%{box-shadow:0 0 0 3px rgba(255,77,106,0)}}
@keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(34,217,159,.4)}50%{box-shadow:0 0 0 5px rgba(34,217,159,0)}}

/* ─── Scrollbar ─── */
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--border2);border-radius:3px}

/* Backdrop */
.sidebar-backdrop{
  display:none;position:fixed;inset:0;z-index:99;
  background:rgba(0,0,0,.55);opacity:0;transition:opacity .3s;
  backdrop-filter:blur(2px);
}
.sidebar-backdrop.visible{opacity:1}

/* ════════════════════════════════════════
   RESPONSIVE
════════════════════════════════════════ */
@media(min-width:769px){#menuToggle{display:none!important}}

@media(max-width:1024px){
  .content{padding:18px}
  .stat-grid{grid-template-columns:repeat(auto-fill,minmax(150px,1fr))}
}

@media(max-width:768px){
  #menuToggle{display:flex!important}
  .sidebar{
    transform:translateX(-100%);opacity:0;pointer-events:none;
    box-shadow:none;z-index:200;
    transition:transform .3s cubic-bezier(.4,0,.2,1),opacity .26s;
  }
  .sidebar.open{transform:translateX(0);opacity:1;pointer-events:all;box-shadow:8px 0 40px rgba(0,0,0,.45)}
  .sidebar-backdrop{display:block}
  .main{margin-left:0}
  .topbar{padding:0 14px}
  .topbar-title{font-size:14px}
  #clockDisplay{display:none}
  .content{padding:14px}
  .stat-grid{grid-template-columns:1fr 1fr;gap:10px;margin-bottom:18px}
  .form-row{grid-template-columns:1fr}
  .card-header{padding:11px 14px;flex-wrap:wrap;gap:6px}
  table{font-size:11.5px}
  th,td{padding:8px 10px}
  .hide-mobile{display:none!important}
  .filters{gap:5px}
  .filter-chip{padding:3px 9px;font-size:11px}
  .btn{padding:6px 11px;font-size:11.5px}
  .btn-sm{padding:3px 8px;font-size:10.5px}
  .modal-overlay{padding:0;align-items:flex-end}
  .modal{max-width:100%!important;max-height:92vh;border-radius:16px 16px 0 0;border-bottom:none}
  .modal .form-row{grid-template-columns:1fr}
  .login-card{padding:26px 20px;border-radius:16px}
  .login-title{font-size:20px}
  .stat-value{font-size:22px}
  .stat-card{padding:14px 16px}
  .search-bar{width:100%}
  .topbar-btn .btn-label{display:none}
  #themeToggleBtn{bottom:16px;right:16px;width:40px;height:40px;font-size:16px}
  #themePanel{right:8px;left:8px;bottom:66px;width:auto}
  #toast{left:14px;bottom:14px;right:14px}
  .toast-item{width:100%;max-width:none}
}
@media(max-width:400px){
  .stat-grid{grid-template-columns:1fr}
  .topbar-right{gap:5px}
}
</style>
</head>
<body>

<!-- Sidebar backdrop for mobile -->
<div id="sidebarBackdrop" class="sidebar-backdrop" onclick="closeSidebar()"></div>

<!-- Login Screen -->
<div id="loginScreen" class="login-wrap">
  <div class="login-card">
    <div style="text-align:center;margin-bottom:24px">
      <img src="data:image/png;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/4gHYSUNDX1BST0ZJTEUAAQEAAAHIAAAAAAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlkZXNjAAAA8AAAACRyWFlaAAABFAAAABRnWFlaAAABKAAAABRiWFlaAAABPAAAABR3dHB0AAABUAAAABRyVFJDAAABZAAAAChnVFJDAAABZAAAAChiVFJDAAABZAAAAChjcHJ0AAABjAAAADxtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEJYWVogAAAAAAAAb6IAADj1AAADkFhZWiAAAAAAAABimQAAt4UAABjaWFlaIAAAAAAAACSgAAAPhAAAts9YWVogAAAAAAAA9tYAAQAAAADTLXBhcmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABtbHVjAAAAAAAAAAEAAAAMZW5VUwAAACAAAAAcAEcAbwBvAGcAbABlACAASQBuAGMALgAgADIAMAAxADb/2wBDAAUDBAQEAwUEBAQFBQUGBwwIBwcHBw8LCwkMEQ8SEhEPERETFhwXExQaFRERGCEYGh0dHx8fExciJCIeJBweHx7/2wBDAQUFBQcGBw4ICA4eFBEUHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh7/wAARCAQABgADASIAAhEBAxEB/8QAHQABAQABBQEBAAAAAAAAAAAAAAEIAgQFBgcDCf/EAGAQAAIBAwICBQQIEAkICgICAwABAgMEBQYRByESMUFRYRMicYEIFDKRlKHR0hUXIyZCUlVWYnR1k7Gys8EWJCczNlNUcpIYQ0ZlgoTh8CU0NTdERXOiwtNjZIOFo6Tx/8QAHAEBAAIDAQEBAAAAAAAAAAAAAAEGBAUHAgMI/8QARBEBAAECAwMHCAkDBAICAwEAAAECAwQFEQYhMRJBUWFxkdETIlKBobHB8BQVFiMyM1OS4QdCchckNPGi0mKCNUOyJf/aAAwDAQACEQMRAD8AzGAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA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ABIAAAAAAAAAAAAAAAAUhQgAAAEBCFIAQAAJgAUACAoEBQAABIBgoEAAAAAAAwAAAAAAAAkIUgAABICkCAAAAAEqQAAAAAKQAQoAAAAAAAAAAAAAAAAAAFAgAAAAAAAAKQAUgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAKCAAAAgKQACkAFIUgQoAAAEApAUAQFAgAIAFIQAAAAAAAAAAAAAAAAAAAAAACkAoICRQASAAAAAAAAAAAAAAECBKkKyAAAEgAAAAAAAAAAAAAAAAACAAEIAAQAAAAAnQUAEgQoIEAA0FADJAAAAAgIUAAGGGAAAAAgSAAAAAkAAAAAAAAAAAAAAAAAAAAAAAAAAAEKAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAIBQAAAAAAAAAAAAAAAAAAAAFIwAAAAAAAAABQAgBAAAKEAAAgKQCkKQAACBSFIJAAEAAAAAAAAAAAAAAAAAAAAAAAoJEKAAABIAMAAAAAAAAACFIEgKQAAAAACQAAAAAAAAAAAAAAAAABAAAAKAhAUECAoAhQCQAAAABIAAABAKQACggAoIAaBSAAAAAACQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAIBQAEAACQAAAAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAAAAAAAAgAAAAAUgKEACAAhSAUgAAFIQAKQaAACAAAAAAAAAAAAAAAAAAAAAAUEBIAoAAhQAAJAAAAAAAAAhSBIAAAACQAAAAAAAAAAAAAAAAAAAAAAAAAAUgAAAAAAEAAAAAJAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQoIBQAAAAAAgFAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAKQAAAAAAQFIABSFCAAACFAEAAFABAgKQAAAAAIAAAAAAAAAAAAAAAAAAACghIoIUkAAQIUEJFAAAAAACBKkKQAAAAACQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAB2gAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABCkAoAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA7QgKiFAAAICAoAAAAQEAAAAKQAACAAAAAAAAAAAAAAAAAAAFICkiFAAAAAACQAAEBSBIAAAAAAAJAAAAIBQAAAAAAAQFIBQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAIUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACFIBQAAAAAAAAAAAAAAACFIBQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQFIUAQpABQAgYBAKCAgCgAQoBIgKQgAAQAAAAAAAAAAAFIAAKQkUhSEigAAAAAAAEKQAUgCQAAAAEgAAABgQFIAAKAADAAACFBAKAAAAAAAAAAAAAAAAAABCkAFIUAAAAAAAAAAAAAAAAAAAAAAAAAAAIUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABCgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACACgAAAAAAAAAAAAAAAAAAAAhSAACkAFIChCApCAKQEgUhQBCkIAAEAAAAAAAAAAABSFJAAgApCkgAAIAAlSFIAAABgAAAAAACQAACFAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAUAAAAAAAAAgFAAAAAAAAAAAAAAAAAAAAgFAAAAAAAAAAAAAAABCgAAAAAAAAAAAAAAAAAAAAAAAAgFAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAACggApAAgAYAFBCAKQAAAAABAAAAAAAAAoIUkCFBIgKAIAAAACQAAAAAAABgAJAAAAAAhQBCgAAAAIUACFAAAAAAAAAAAACFAAAAAAAAAAAACFAAAAACAUAAAAAAAAAAAAAAAEBQAAAAAAAAAAAAAAAAAAAAAACFAAAAAAAAIBQAAAAAgAFAAAAAAAAAAAAAAAAAAAEKAAAAAAAAAAAAAAAAAADAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAAAAAAAAAAAAAABAAAAAAAAIAAAABAAAgAAAAAAAACkKSAAJAEASAAAAAABNwKAAkAAAABAAAkAIBQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABuAAAAAAAAABCgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAbgAAAAG4AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAAAANwAAAAAIUhSESP//Z" alt="FixGrid" style="height:80px;width:auto;display:block;margin:0 auto 10px">
      <div style="font-size:11px;color:var(--muted);letter-spacing:.05em;text-transform:uppercase;font-weight:500">Admin Panel</div>
    </div>
        <div id="loginError" class="alert alert-error" style="display:none"></div>
    <div class="form-group">
      <label>Email Address</label>
      <input type="email" id="loginEmail" placeholder="Enter your email" autocomplete="email">
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" id="loginPass" placeholder="••••••••" autocomplete="current-password">
    </div>
<button id="loginBtn" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px" onclick="doLogin()">
Sign In to Dashboard
</button>
    <div style="text-align:center;margin-top:12px">
      <button type="button" onclick="showAdminForgot()" style="background:none;border:none;color:var(--muted);cursor:pointer;font-size:13px;text-decoration:underline">🔑 Forgot Password?</button>
    </div>

  </div>
</div>

<!-- App Shell (hidden until login) -->
<div id="appShell" class="app-hidden" style="width:100%;display:none;flex-direction:row">

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <img src="data:image/png;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/4gHYSUNDX1BST0ZJTEUAAQEAAAHIAAAAAAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlkZXNjAAAA8AAAACRyWFlaAAABFAAAABRnWFlaAAABKAAAABRiWFlaAAABPAAAABR3dHB0AAABUAAAABRyVFJDAAABZAAAAChnVFJDAAABZAAAAChiVFJDAAABZAAAAChjcHJ0AAABjAAAADxtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEJYWVogAAAAAAAAb6IAADj1AAADkFhZWiAAAAAAAABimQAAt4UAABjaWFlaIAAAAAAAACSgAAAPhAAAts9YWVogAAAAAAAA9tYAAQAAAADTLXBhcmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABtbHVjAAAAAAAAAAEAAAAMZW5VUwAAACAAAAAcAEcAbwBvAGcAbABlACAASQBuAGMALgAgADIAMAAxADb/2wBDAAUDBAQEAwUEBAQFBQUGBwwIBwcHBw8LCwkMEQ8SEhEPERETFhwXExQaFRERGCEYGh0dHx8fExciJCIeJBweHx7/2wBDAQUFBQcGBw4ICA4eFBEUHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh7/wAARCAQABgADASIAAhEBAxEB/8QAHQABAQABBQEBAAAAAAAAAAAAAAEIAgQFBgcDCf/EAGAQAAIBAwICBQQIEAkICgICAwABAgMEBQYRByESMUFRYRMicYEIFDKRlKHR0hUXIyZCUlVWYnR1k7Gys8EWJCczNlNUcpIYQ0ZlgoTh8CU0NTdERXOiwtNjZIOFo6Tx/8QAHAEBAAIDAQEBAAAAAAAAAAAAAAEGBAUHAgMI/8QARBEBAAECAwMHCAkDBAICAwEAAAECAwQFEQYhMRJBUWFxkdETIlKBobHB8BQVFiMyM1OS4QdCchckNPGi0mKCNUOyJf/aAAwDAQACEQMRAD8AzGAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA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ABIAAAAAAAAAAAAAAAAUhQgAAAEBCFIAQAAJgAUACAoEBQAABIBgoEAAAAAAAwAAAAAAAAkIUgAABICkCAAAAAEqQAAAAAKQAQoAAAAAAAAAAAAAAAAAAFAgAAAAAAAAKQAUgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAKCAAAAgKQACkAFIUgQoAAAEApAUAQFAgAIAFIQAAAAAAAAAAAAAAAAAAAAAACkAoICRQASAAAAAAAAAAAAAAECBKkKyAAAEgAAAAAAAAAAAAAAAAACAAEIAAQAAAAAnQUAEgQoIEAA0FADJAAAAAgIUAAGGGAAAAAgSAAAAAkAAAAAAAAAAAAAAAAAAAAAAAAAAAEKAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAIBQAAAAAAAAAAAAAAAAAAAAFIwAAAAAAAAABQAgBAAAKEAAAgKQCkKQAACBSFIJAAEAAAAAAAAAAAAAAAAAAAAAAAoJEKAAABIAMAAAAAAAAACFIEgKQAAAAACQAAAAAAAAAAAAAAAAABAAAAKAhAUECAoAhQCQAAAABIAAABAKQACggAoIAaBSAAAAAACQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAIBQAEAACQAAAAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAAAAAAAAgAAAAAUgKEACAAhSAUgAAFIQAKQaAACAAAAAAAAAAAAAAAAAAAAAAUEBIAoAAhQAAJAAAAAAAAAhSBIAAAACQAAAAAAAAAAAAAAAAAAAAAAAAAAUgAAAAAAEAAAAAJAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQoIBQAAAAAAgFAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAKQAAAAAAQFIABSFCAAACFAEAAFABAgKQAAAAAIAAAAAAAAAAAAAAAAAAACghIoIUkAAQIUEJFAAAAAACBKkKQAAAAACQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAB2gAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABCkAoAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA7QgKiFAAAICAoAAAAQEAAAAKQAACAAAAAAAAAAAAAAAAAAAFICkiFAAAAAACQAAEBSBIAAAAAAAJAAAAIBQAAAAAAAQFIBQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAIUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACFIBQAAAAAAAAAAAAAAACFIBQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQFIUAQpABQAgYBAKCAgCgAQoBIgKQgAAQAAAAAAAAAAAFIAAKQkUhSEigAAAAAAAEKQAUgCQAAAAEgAAABgQFIAAKAADAAACFBAKAAAAAAAAAAAAAAAAAABCkAFIUAAAAAAAAAAAAAAAAAAAAAAAAAAAIUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABCgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACACgAAAAAAAAAAAAAAAAAAAAhSAACkAFIChCApCAKQEgUhQBCkIAAEAAAAAAAAAAABSFJAAgApCkgAAIAAlSFIAAABgAAAAAACQAACFAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAUAAAAAAAAAgFAAAAAAAAAAAAAAAAAAAAgFAAAAAAAAAAAAAAABCgAAAAAAAAAAAAAAAAAAAAAAAAgFAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAACggApAAgAYAFBCAKQAAAAABAAAAAAAAAoIUkCFBIgKAIAAAACQAAAAAAABgAJAAAAAAhQBCgAAAAIUACFAAAAAAAAAAAACFAAAAAAAAAAAACFAAAAACAUAAAAAAAAAAAAAAAEBQAAAAAAAAAAAAAAAAAAAAAACFAAAAAAAAIBQAAAAAgAFAAAAAAAAAAAAAAAAAAAEKAAAAAAAAAAAAAAAAAADAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAAAAAAAAAAAAAABAAAAAAAAIAAAABAAAgAAAAAAAACkKSAAJAEASAAAAAABNwKAAkAAAABAAAkAIBQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABuAAAAAAAAABCgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAbgAAAAG4AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAAAANwAAAAAIUhSESP//Z" alt="FixGrid" style="height:38px;width:auto;display:block;filter:brightness(0) invert(1);padding:2px 0">
  </div>
  <div class="nav">
    <div class="nav-section">Overview</div>
    <div class="nav-item active" data-page="dashboard" onclick="showPage('dashboard',this)"><span class="nav-icon">📊</span>Dashboard</div>
    <div class="nav-item" data-page="livemap" onclick="showPage('livemap',this)"><span class="nav-icon">🗺️</span>Live Map <span class="live-dot"></span></div>
    <div class="nav-section">Field Operations</div>
    <div class="nav-item" data-page="kyc" onclick="showPage('kyc',this)"><span class="nav-icon">🪪</span>KYC Verification <span class="nav-badge" id="kycBadge" style="display:none">0</span></div>
    <div class="nav-item" data-page="jobs" onclick="showPage('jobs',this)"><span class="nav-icon">📋</span>Job Management <span class="nav-badge" id="pendingBadge" style="display:none">0</span></div>
    <div class="nav-item" data-page="engineers" onclick="showPage('engineers',this)"><span class="nav-icon">👷</span>Engineers</div>
    <div class="nav-item" data-page="services" onclick="showPage('services',this)"><span class="nav-icon">🔧</span>Services & Pricing</div>
    <div class="nav-item" data-page="customers" onclick="showPage('customers',this)"><span class="nav-icon">👥</span>Customers</div>
    <div class="nav-item" data-page="zones" onclick="showPage('zones',this)"><span class="nav-icon">🗾</span>Zone Management</div>
    <div class="nav-section">Website & Settings</div>
    <div class="nav-item" data-page="website" onclick="showPage('website',this)"><span class="nav-icon">🌐</span>Homepage Editor</div>
    <div class="nav-section">Finance</div>
    <div class="nav-item" data-page="reports" onclick="showPage('reports',this)"><span class="nav-icon">📈</span>Reports & Analytics</div>
    <div class="nav-section">Sales & Revenue</div>
    <div class="nav-item" data-page="promo" onclick="showPage('promo',this)"><span class="nav-icon">🎟️</span>Promo Codes</div>
    <div class="nav-item" data-page="contracts" onclick="showPage('contracts',this)"><span class="nav-icon">📄</span>AMC Contracts</div>
    <div class="nav-item" data-page="disputes" onclick="showPage('disputes',this)"><span class="nav-icon">⚖️</span>Disputes</div>
    <div class="nav-item" data-page="quotations" onclick="showPage('quotations',this)"><span class="nav-icon">📋</span>Quotations</div>
    <div class="nav-item" data-page="pickups" onclick="showPage('pickups',this)"><span class="nav-icon">🔧</span>Device Pickups</div>
    <div class="nav-item" data-page="inventory" onclick="showPage('inventory',this)"><span class="nav-icon">📦</span>Parts Inventory</div>
    <div class="nav-section">Configuration</div>
    <div class="nav-item" data-page="settings" onclick="showPage('settings',this)"><span class="nav-icon">⚙️</span>App Settings</div>
    <div class="nav-item" data-page="api-settings" onclick="showPage('api-settings',this)"><span class="nav-icon">🔌</span>API & Integrations</div>
    <div class="nav-item" data-page="wallets" onclick="showPage('wallets',this)"><span class="nav-icon">💰</span>Engineer Wallets</div>
    <div class="nav-item" data-page="platform-fees" onclick="showPage('platform-fees',this)"><span class="nav-icon">🏦</span>Platform Fees</div>
  </div>
  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar" id="adminAvatar">A</div>
      <div class="user-info">
        <div class="user-name" id="adminName">Admin</div>
        <div class="user-role" id="adminRole">Super Admin</div>
      </div>
      <button class="logout-btn" onclick="doLogout()" title="Logout">⏻</button>
    </div>
  </div>
</nav>

<!-- Main Content -->
<div class="main" id="mainWrapper">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:12px">
      <button class="btn btn-ghost btn-sm" id="menuToggle" onclick="toggleSidebar()" style="padding:6px 10px">☰</button>
      <div class="topbar-title" id="pageTitle">Dashboard</div>
    </div>
    <div class="topbar-right">
      <span id="clockDisplay" style="font-family:'Geist Mono',monospace;font-size:12px;color:var(--muted);letter-spacing:.02em"></span>
      <button class="topbar-btn" onclick="refreshPage()">↻ <span class="btn-label">Refresh</span></button>
    </div>
  </div>

  <div class="content" id="mainContent">
    <div class="loading"><div class="spinner"></div> Loading dashboard...</div>
  </div>
</div>

</div><!-- /appShell -->

<!-- Toast container -->
<div id="toast"></div>

<!-- Forgot Password Modal -->
<div id="forgotModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9999;align-items:center;justify-content:center">
  <div style="background:var(--card);border-radius:16px;padding:28px;width:360px;max-width:90vw;box-shadow:0 20px 60px rgba(0,0,0,.5)">
    <div style="font-size:18px;font-weight:700;margin-bottom:6px">🔑 Reset Password</div>
    <div style="font-size:13px;color:var(--muted);margin-bottom:20px">Enter your admin email — we will send a reset link</div>
    <div id="forgotModalMsg" style="display:none;padding:10px;border-radius:8px;margin-bottom:14px;font-size:13px"></div>
    <div class="form-group"><label>Email Address</label><input id="forgotAdminEmail" type="email" placeholder="admin@fixgrid.in" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;background:var(--bg3);color:var(--text);font-size:14px"></div>
    <div style="display:flex;gap:10px;margin-top:16px">
      <button class="btn btn-primary" style="flex:1" onclick="requestAdminReset(event)">📧 Send Reset Link</button>
      <button class="btn btn-ghost" onclick="document.getElementById('forgotModal').style.display='none'">Cancel</button>
    </div>
  </div>
</div>

<!-- Reset Password Modal -->
<div id="resetModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:9999;align-items:center;justify-content:center">
  <div style="background:var(--card);border-radius:16px;padding:28px;width:360px;max-width:90vw;box-shadow:0 20px 60px rgba(0,0,0,.5)">
    <div style="font-size:18px;font-weight:700;margin-bottom:16px">🔒 Set New Password</div>
    <div id="resetModalMsg" style="display:none;padding:10px;border-radius:8px;margin-bottom:14px;font-size:13px"></div>
    <div class="form-group"><label>New Password</label><input id="adminNewPass1" type="password" placeholder="Minimum 6 characters" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;background:var(--bg3);color:var(--text);font-size:14px"></div>
    <div class="form-group"><label>Confirm Password</label><input id="adminNewPass2" type="password" placeholder="Repeat password" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;background:var(--bg3);color:var(--text);font-size:14px"></div>
    <button class="btn btn-primary" style="width:100%;margin-top:8px" onclick="submitAdminReset(event)">🔒 Set New Password</button>
  </div>
</div>

<!-- Engineer Modal -->
<div class="modal-overlay" id="engineerModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="engineerModalTitle">Add Engineer</div>
      <button class="modal-close" onclick="closeModal('engineerModal')">×</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="eng_id">
      <div class="form-row">
        <div class="form-group" style="margin:0"><label>Full Name *</label><input id="eng_name" placeholder="John Doe"></div>
        <div class="form-group" style="margin:0"><label>Phone *</label><input id="eng_phone" placeholder="+91-9999999999"></div>
      </div>
      <div class="form-row" style="margin-top:12px">
        <div class="form-group" style="margin:0"><label>Email *</label><input id="eng_email" type="email" placeholder="john@example.com"></div>
        <div class="form-group" style="margin:0"><label>Password</label><input id="eng_pass" type="password" placeholder="Required for new engineer"></div>
      </div>
      <div class="form-group"><label>Service Area</label><input id="eng_area" placeholder="e.g. Delhi North, Noida"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('engineerModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveEngineer()">Save Engineer</button>
    </div>
  </div>
</div>

<!-- Job Detail / Edit Modal -->
<div class="modal-overlay" id="jobModal">
  <div class="modal" style="max-width:600px">
    <div class="modal-header">
      <div>
        <div class="modal-title">Job Details</div>
        <div id="jobModalNumber" style="font-size:12px;color:var(--muted);font-family:'Geist Mono',monospace;margin-top:2px"></div>
      </div>
      <button class="modal-close" onclick="closeModal('jobModal')">x</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="jm_job_id">
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:20px;padding:14px;background:var(--bg3);border-radius:10px;font-size:13px">
        <div><div style="font-size:10px;color:var(--muted);margin-bottom:4px">Customer</div><div id="jm_customer" style="font-weight:500"></div></div>
        <div><div style="font-size:10px;color:var(--muted);margin-bottom:4px">Engineer</div><div id="jm_engineer" style="font-weight:500"></div></div>
        <div><div style="font-size:10px;color:var(--muted);margin-bottom:4px">Service</div><div id="jm_service" style="font-weight:500"></div></div>
        <div><div style="font-size:10px;color:var(--muted);margin-bottom:4px">Created</div><div id="jm_created" style="font-weight:500"></div></div>
      </div>
      <div class="form-row">
        <div class="form-group" style="margin:0"><label>Status</label><select id="jm_status"><option value="pending">Pending</option><option value="assigned">Assigned</option><option value="accepted">Accepted</option><option value="on_the_way">On the Way</option><option value="arrived">Arrived</option><option value="working">Working</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></div>
        <div class="form-group" style="margin:0"><label>Priority</label><select id="jm_priority"><option value="low">Low</option><option value="normal">Normal</option><option value="high">High</option><option value="urgent">Urgent</option></select></div>
      </div>
      <div class="form-group"><label>Final Amount (Rs.)</label><input type="number" id="jm_amount" step="0.01" min="0" placeholder="0.00"></div>
      <div class="form-group"><label>Address</label><input type="text" id="jm_address" placeholder="Job address"></div>
      <div class="form-group" style="margin:0"><label>Admin Notes</label><textarea id="jm_notes" rows="3" style="resize:vertical" placeholder="Internal notes..."></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('jobModal')">Cancel</button>
      <button class="btn btn-danger btn-sm" id="jm_cancel_btn" style="margin-right:auto">Cancel Job</button>
      <button class="btn btn-primary" onclick="saveJob()">Save Changes</button>
    </div>
  </div>
</div>

<!-- Assign Engineer Modal -->
<div class="modal-overlay" id="assignModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Assign Engineer</div>
      <button class="modal-close" onclick="closeModal('assignModal')">×</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="assign_job_id">
      <div class="form-group">
        <label>Select Engineer</label>
        <select id="assign_engineer_id"><option value="">-- Select --</option></select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('assignModal')">Cancel</button>
      <button class="btn btn-primary" onclick="doAssign()">Assign</button>
    </div>
  </div>
</div>

<script>

// ─── ENGINEERS ────────────────────────────────────────────
async function renderEngineers() {
  setContent('<div class="loading"><div class="spinner"></div> Loading engineers...</div>');
  const r = await api('GET', '/admin/engineers');
  if (!r.success) { setContent('<div class="empty">Failed to load engineers</div>'); return; }
  const html = `
  <div class="card">
    <div class="card-header">
      <div class="card-title">Field Engineers (${r.data.length})</div>
      <div class="card-actions">
        <input class="search-bar" placeholder="🔍 Search..." oninput="searchEngTable(this.value)" style="width:min(180px,100%)">
        <button class="btn btn-primary btn-sm" onclick="openEngineerModal()">+ Add Engineer</button>
      </div>
    </div>
    <div style="overflow-x:auto">
    <table id="engTable">
      <thead><tr><th>Engineer</th><th class="hide-mobile">Phone</th><th class="hide-mobile">Service Area</th><th>Status</th><th class="hide-mobile">Jobs Done</th><th class="hide-mobile">Rating</th><th class="hide-mobile">Last Online</th><th>Actions</th></tr></thead>
      <tbody>
        ${(window._engMap={}, r.data).map(e => (window._engMap[e.id]=e, `<tr data-search="${(e.name+e.phone+e.email).toLowerCase()}">
          <td><div style="display:flex;align-items:center;gap:10px"><div class="avatar">${e.name[0]}</div><div><div style="font-weight:500">${e.name}</div><div style="font-size:11px;color:var(--muted)">${e.email}</div></div></div></td>
          <td class="hide-mobile" style="font-family:'Geist Mono',monospace;font-size:12px">${e.phone}</td>
          <td class="hide-mobile" style="font-size:12px;color:var(--muted)">${e.service_area||'—'}</td>
          <td><span class="badge badge-${e.status}">${e.status}</span>${!e.is_active?'<span class="badge badge-cancelled" style="margin-left:4px">inactive</span>':''}</td>
          <td class="hide-mobile" style="font-family:'Geist Mono',monospace;text-align:center">${e.completed_jobs}</td>
          <td class="hide-mobile" style="color:var(--amber)">⭐ ${e.avg_rating||'—'}</td>
          <td class="hide-mobile" style="font-size:12px;color:var(--muted)">${e.last_online?timeAgo(e.last_online):'Never'}</td>
          <td><div style="display:flex;gap:6px">
            <button class="btn btn-ghost btn-sm" onclick="openEngineerById(${e.id})">Edit</button>
            <button class="btn btn-${e.is_active?'danger':'success'} btn-sm" onclick="toggleEngineer(${e.id})">${e.is_active?'Disable':'Enable'}</button>
          </div></td>
        </tr>`) ).join('')}
      </tbody>
    </table>
    </div>
  </div>`;
  setContent(html);
}

function searchEngTable(q) {
  document.querySelectorAll('#engTable tbody tr').forEach(r => r.style.display = r.dataset.search.includes(q.toLowerCase()) ? '' : 'none');
}

function openEngineerById(id) {
  openEngineerModal((window._engMap||{})[id] || null);
}

function openEngineerModal(eng = null) {
  document.getElementById('engineerModalTitle').textContent = eng ? 'Edit Engineer' : 'Add Engineer';
  document.getElementById('eng_id').value = eng?.id || '';
  document.getElementById('eng_name').value = eng?.name || '';
  document.getElementById('eng_phone').value = eng?.phone || '';
  document.getElementById('eng_email').value = eng?.email || '';
  document.getElementById('eng_pass').value = '';
  document.getElementById('eng_area').value = eng?.service_area || '';
  openModal('engineerModal');
}

async function saveEngineer() {
  const id    = document.getElementById('eng_id').value;
  const name  = document.getElementById('eng_name').value.trim();
  const phone = document.getElementById('eng_phone').value.trim();
  const email = document.getElementById('eng_email').value.trim();
  const pass  = document.getElementById('eng_pass').value;
  const area  = document.getElementById('eng_area').value.trim();
  if (!name || !phone || !email) { toast('Name, phone and email are required', 'error'); return; }
  // FIX: password is required by backend when creating a new engineer
  if (!id && !pass) { toast('Password is required for new engineers', 'error'); return; }
  const body = { name, phone, email, service_area: area };
  if (pass) body.password = pass;
  if (id) body.id = id;
  const r = await api('POST', id ? '/admin/engineer/update' : '/admin/engineer/create', body);
  if (r.success) {
    toast(id ? 'Engineer updated' : 'Engineer created', 'success');
    closeModal('engineerModal');
    renderEngineers();
  } else toast(r.message || 'Error saving engineer', 'error');
}

async function toggleEngineer(id) {
  const r = await api('POST', '/admin/engineer/toggle', { id });
  if (r.success) { toast('Status updated', 'success'); renderEngineers(); }
  else toast(r.message, 'error');
}

// ─── CUSTOMERS ────────────────────────────────────────────
async function renderCustomers() {
  setContent('<div class="loading"><div class="spinner"></div> Loading customers...</div>');
  const r = await api('GET', '/admin/customers');
  if (!r.success) { setContent('<div class="empty">Failed to load</div>'); return; }
  const html = `
  <div class="card">
    <div class="card-header"><div class="card-title">Customers (${r.data.length})</div>
      <input class="search-bar" placeholder="🔍 Search..." oninput="searchCustTable(this.value)" style="width:200px">
    </div>
    <div style="overflow-x:auto">
    <table id="custTable">
      <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Address</th><th>Total Jobs</th><th>Joined</th></tr></thead>
      <tbody>${r.data.map(c=>`<tr data-search="${(c.name+c.phone+(c.email||'')).toLowerCase()}">
        <td><div style="display:flex;align-items:center;gap:8px"><div class="avatar" style="width:28px;height:28px;font-size:11px">${c.name[0]}</div>${c.name}</div></td>
        <td style="font-family:'Geist Mono',monospace;font-size:12px">${c.phone}</td>
        <td style="font-size:12px;color:var(--muted)">${c.email||'—'}</td>
        <td style="font-size:12px;color:var(--muted);max-width:160px">${(c.address||'—').substring(0,50)}</td>
        <td style="text-align:center;font-family:'Geist Mono',monospace">${c.total_jobs}</td>
        <td style="font-size:12px;color:var(--muted)">${timeAgo(c.created_at)}</td>
      </tr>`).join('')}</tbody>
    </table>
    </div>
  </div>`;
  setContent(html);
}
function searchCustTable(q) { document.querySelectorAll('#custTable tbody tr').forEach(r=>r.style.display=r.dataset.search.includes(q.toLowerCase())?'':'none'); }

// ─── REPORTS ──────────────────────────────────────────────
async function renderAnalytics() {
  document.getElementById('pageContent').innerHTML = `
    <div class="page-header"><div class="page-title">📊 Analytics</div></div>
    <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap">
      ${[7,30,90].map(d=>`<button class="btn btn-ghost btn-sm" onclick="loadAnalytics(${d})" id="ana${d}">${d}D</button>`).join('')}
    </div>
    <div id="analyticsContent"><div class="loading"><div class="spinner"></div> Loading...</div></div>`;
  setTimeout(function() { loadAnalytics(30); }, 50);
}

async function loadAnalytics(days) {
  [7,30,90].forEach(d => {
    var b = document.getElementById('ana'+d);
    if(b) b.style.background = d===days ? 'var(--accent)' : '';
  });
  var r = await api('GET', '/analytics/dashboard?period=' + days);
  var el = document.getElementById('analyticsContent');
  if (!el) return;
  if (!r.success) { el.innerHTML = '<div class="empty">Failed to load</div>'; return; }
  var d = r.data;
  var s = d.summary || {};
  var completion = s.total_jobs > 0 ? Math.round((s.completed/s.total_jobs)*100) : 0;
  el.innerHTML = `
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:20px">
      <div class="stat-card"><div class="stat-value">${s.total_jobs||0}</div><div class="stat-label">Total Jobs</div></div>
      <div class="stat-card"><div class="stat-value">${s.completed||0}</div><div class="stat-label">Completed</div></div>
      <div class="stat-card"><div class="stat-value">${s.cancelled||0}</div><div class="stat-label">Cancelled</div></div>
      <div class="stat-card"><div class="stat-value">${completion}%</div><div class="stat-label">Completion Rate</div></div>
      <div class="stat-card"><div class="stat-value">${s.avg_response_mins||0}m</div><div class="stat-label">Avg Response</div></div>
    </div>
    <div class="card" style="margin-bottom:16px">
      <div class="card-header"><div class="card-title">Jobs by Service</div></div>
      <div style="padding:0 16px 16px">
        ${(d.by_service||[]).map(sv=>`
          <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border)">
            <div style="font-size:13px">${sv.name||'Unknown'}</div>
            <div style="font-size:13px;font-weight:700">${sv.total_jobs} jobs</div>
          </div>`).join('')}
      </div>
    </div>
    <div class="card">
      <div class="card-header"><div class="card-title">Daily Jobs (Last ${days} days)</div></div>
      <div style="padding:0 16px 16px">
        ${(d.daily||[]).slice(-14).map(day=>`
          <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--border)">
            <div style="font-size:12px;color:var(--muted)">${day.date}</div>
            <div style="font-size:13px;font-weight:600">${day.jobs} jobs</div>
          </div>`).join('')}
      </div>
    </div>`;
}

async function renderReports() {
  const today = new Date();
  const from  = today.toISOString().slice(0,8) + '01';
  const to    = today.toISOString().slice(0,10);
  setContent(`
    <div class="card" style="margin-bottom:20px">
      <div class="card-header"><div class="card-title">Date Range</div></div>
      <div style="padding:16px;display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
        <div class="form-group" style="margin:0"><label>From</label><input type="date" id="rptFrom" value="${from}"></div>
        <div class="form-group" style="margin:0"><label>To</label><input type="date" id="rptTo" value="${to}"></div>
        <button class="btn btn-primary" onclick="loadReports()">Generate Report</button>
        <button class="btn btn-ghost btn-sm" onclick="exportCSV('jobs')">⬇ Jobs CSV</button>
        <button class="btn btn-ghost btn-sm" onclick="exportCSV('engineers')">⬇ Engineers CSV</button>
        <button class="btn btn-ghost btn-sm" onclick="exportCSV('customers')">⬇ Customers CSV</button>
      </div>
    </div>
    <div id="reportContent"><div class="loading"><div class="spinner"></div> Loading...</div></div>`);
  loadReports();
}

async function loadReports() {
  const from = document.getElementById('rptFrom')?.value || '';
  const to   = document.getElementById('rptTo')?.value || '';
  const r = await api('GET', `/admin/reports?from=${from}&to=${to}`);
  if (!r.success) return;
  const d = r.data;
  document.getElementById('reportContent').innerHTML = `
    <div class="stat-grid">
      <div class="stat-card blue"><div class="stat-label">📋 Total Jobs</div><div class="stat-value">${d.total}</div></div>
      <div class="stat-card green"><div class="stat-label">✅ Completed</div><div class="stat-value">${d.completed}</div></div>
      <div class="stat-card red"><div class="stat-label">❌ Cancelled</div><div class="stat-value">${d.cancelled}</div></div>
      <div class="stat-card cyan"><div class="stat-label">💰 Revenue</div><div class="stat-value">₹${Number(d.revenue).toLocaleString()}</div></div>
      <div class="stat-card amber"><div class="stat-label">⭐ Avg Rating</div><div class="stat-value">${d.avg_rating}</div></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;flex-wrap:wrap">
      <div class="card">
        <div class="card-header"><div class="card-title">🏆 Top Engineers</div></div>
        <table><thead><tr><th>Engineer</th><th class="hide-mobile">Jobs</th><th>Revenue</th><th class="hide-mobile">Rating</th></tr></thead>
        <tbody>${(d.top_engineers||[]).map(e=>`<tr><td>${e.name}</td><td style="font-family:'Geist Mono',monospace">${e.jobs}</td><td style="font-family:'Geist Mono',monospace">₹${Number(e.revenue).toLocaleString()}</td><td style="color:var(--amber)">⭐${parseFloat(e.rating).toFixed(1)}</td></tr>`).join('')}</tbody>
        </table>
      </div>
      <div class="card">
        <div class="card-header"><div class="card-title">🔧 By Service</div></div>
        <table><thead><tr><th>Service</th><th>Jobs</th></tr></thead>
        <tbody>${d.by_service.map(s=>`<tr><td>${s.name}</td><td style="font-family:'Geist Mono',monospace">${s.jobs}</td></tr>`).join('')}</tbody>
        </table>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><div class="card-title">📅 Daily Breakdown</div></div>
      <table><thead><tr><th>Date</th><th>Jobs</th><th>Revenue</th></tr></thead>
      <tbody>${(d.daily||[]).map(d=>`<tr><td style="font-family:'Geist Mono',monospace">${d.date}</td><td>${d.jobs}</td><td style="font-family:'Geist Mono',monospace">₹${Number(d.revenue).toLocaleString()}</td></tr>`).join('')}</tbody>
      </table>
    </div>`;
}

// ─── SKILLS ───────────────────────────────────────────────
// ─── SERVICES ─────────────────────────────────────────────
async function renderServices() {
  setContent('<div class="loading"><div class="spinner"></div> Loading...</div>');
  let r, skillsR;
  try {
    [r, skillsR] = await Promise.all([api('GET','/admin/services'), api('GET','/admin/skills')]);
  } catch(e) {
    setContent('<div class="empty">⚠ Failed to load: ' + e.message + '</div>');
    return;
  }
  if (!r || !r.success) {
    setContent('<div class="empty">⚠ Services error: ' + (r ? r.message : 'No response') + '</div>');
    return;
  }
  const svcs   = r.data || [];
  const skills = skillsR && skillsR.success ? (skillsR.data || []) : [];
  const skillOpts  = skills.filter(s=>s.is_active).map(s=>`<option value="${s.id}">${s.name}</option>`).join('');
  const parentOpts = svcs.map(s=>`<option value="${s.id}">${s.icon||''} ${s.name}</option>`).join('');

  window._svcMap = window._svcMap || {};
  function svcRow(s, indent) {
    window._svcMap[s.id] = s;
    const pad = indent ? 'padding-left:32px;border-left:2px solid var(--border);margin-left:16px' : '';
    return '<div style="display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid var(--border);'+pad+'">' +
      '<div style="font-size:'+(indent?'18px':'22px')+'">'+(s.icon||'🔧')+'</div>' +
      '<div style="flex:1;min-width:0">' +
        '<div style="font-weight:'+(indent?'500':'700')+';font-size:'+(indent?'13px':'14px')+'">'+s.name+'</div>' +
        '<div style="font-size:11px;color:var(--muted)">' +
          (s.base_price>0?'₹'+Number(s.base_price).toLocaleString('en-IN'):'No price') +
          (s.visit_charge>0?' · Visit ₹'+Number(s.visit_charge).toLocaleString('en-IN'):'') +
          (s.skill_name?' · 🛠 '+s.skill_name:'') +
        '</div>' +
      '</div>' +
      '<span style="flex-shrink:0;padding:2px 7px;border-radius:10px;font-size:10px;font-weight:600;background:'+(s.is_active?'rgba(34,217,159,.15)':'rgba(255,77,106,.15)')+';color:'+(s.is_active?'var(--green)':'var(--red)')+'">'+( s.is_active?'Active':'Off')+'</span>' +
      '<button onclick="editServiceById('+s.id+')" style="background:transparent;border:1px solid var(--border);color:var(--muted);padding:3px 8px;border-radius:6px;cursor:pointer;font-size:11px">✏️</button>' +
      '<button onclick="toggleService('+s.id+','+s.is_active+')" style="background:transparent;border:1px solid var(--border);color:var(--muted);padding:3px 8px;border-radius:6px;cursor:pointer;font-size:11px">'+(s.is_active?'🔴':'🟢')+'</button>' +
      '<button onclick="deleteService('+s.id+',event)" data-name="'+s.name.replace(/"/g,'&quot;')+'" style="background:rgba(255,77,106,.1);border:1px solid rgba(255,77,106,.3);color:var(--red);padding:3px 8px;border-radius:6px;cursor:pointer;font-size:11px">🗑</button>' +
    '</div>';
  }

  const treeHtml = svcs.length === 0
    ? '<div style="text-align:center;padding:20px;color:var(--muted)">No services yet</div>'
    : svcs.map(s => {
        let html = svcRow(s, false);
        if (s.sub_services && s.sub_services.length) {
          html += '<div style="margin-left:8px">';
          html += s.sub_services.map(sub => svcRow(sub, true)).join('');
          html += '</div>';
        }
        return html;
      }).join('');

  setContent(`
  <!-- TABS -->
  <div style="display:flex;gap:4px;margin-bottom:16px;background:var(--bg3);border-radius:12px;padding:4px;width:fit-content">
    <button id="tab-services" onclick="switchServicesTab('services')"
      style="padding:8px 20px;border-radius:8px;border:none;cursor:pointer;font-size:13px;font-weight:600;background:var(--card);color:var(--text);box-shadow:0 1px 4px rgba(0,0,0,.15)">
      🔧 Services
    </button>
    <button id="tab-skills" onclick="switchServicesTab('skills')"
      style="padding:8px 20px;border-radius:8px;border:none;cursor:pointer;font-size:13px;font-weight:600;background:transparent;color:var(--muted)">
      🛠️ Skills
    </button>
  </div>

  <!-- SERVICES TAB -->
  <div id="panel-services">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px;">
      <div class="card">
        <div class="card-header">
          <div class="card-title">➕ Add / Edit Service</div>
          <button class="btn btn-ghost btn-sm" onclick="openImportModal('services')">📥 Import CSV</button>
        </div>
        <div style="padding:20px">
          <input type="hidden" id="svcId" value="">
          <div style="background:rgba(79,110,247,.08);border:1px solid rgba(79,110,247,.2);border-radius:10px;padding:10px 12px;margin-bottom:16px;font-size:12px;color:var(--muted);line-height:1.6">
            <b style="color:var(--accent)">📌 Sub-service:</b> select a parent below, fill name & price.<br>
            <b style="color:var(--accent)">📌 Top-level:</b> leave parent blank.
          </div>
          <div class="form-group">
            <label>Parent Category <span style="color:var(--muted);font-weight:400">(blank = top-level)</span></label>
            <select id="svcParent" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;background:var(--bg3);color:var(--text);font-size:14px" onchange="toggleParentFields()">
              <option value="">── Top-level Category ──</option>
              ${parentOpts}
            </select>
          </div>
          <div class="form-group"><label>Service Name *</label><input id="svcName" placeholder="e.g. AC Gas Refill" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;background:var(--bg3);color:var(--text);font-size:14px"></div>
          <div class="form-group"><label>Icon (emoji)</label><input id="svcIcon" placeholder="❄️" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;background:var(--bg3);color:var(--text);font-size:14px"></div>
          <div class="form-row">
            <div class="form-group" style="margin:0"><label>Base Price (₹)</label><input type="number" id="svcBasePrice" placeholder="0.00" min="0" step="0.01" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;background:var(--bg3);color:var(--text);font-size:14px"></div>
            <div class="form-group" style="margin:0"><label>Fixed Visit Charge (₹)</label><input type="number" id="svcVisitCharge" placeholder="0.00" min="0" step="0.01" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;background:var(--bg3);color:var(--text);font-size:14px"></div>
          </div>
          <div class="form-group">
            <label>Per km Rate (₹/km) <span style="color:var(--muted);font-weight:400;font-size:11px">— leave 0 to use global setting, overrides global if set</span></label>
            <input type="number" id="svcPerKmRate" placeholder="0 = use global setting" min="0" step="0.5" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;background:var(--bg3);color:var(--text);font-size:14px">
          </div>
          <div class="form-group"><label>Platform Charge (%)</label><input type="number" id="svcPlatformPct" placeholder="20" min="0" max="100" step="0.5" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;background:var(--bg3);color:var(--text);font-size:14px"></div>
          <div class="form-group" id="svcSkillGroup">
            <label>Required Skill (auto-assign)</label>
            <select id="svcSkill" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;background:var(--bg3);color:var(--text);font-size:14px">
              <option value="">-- No skill required --</option>
              ${skillOpts}
            </select>
          </div>
          <div style="display:flex;gap:8px">
            <button class="btn btn-primary" style="flex:1" onclick="saveService()">💾 Save</button>
            <button class="btn btn-ghost" onclick="clearServiceForm()">✕ Clear</button>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-header">
          <div class="card-title">🔧 All Services (${svcs.length} categories)</div>
        </div>
        <div style="padding:8px 16px;max-height:70vh;overflow-y:auto">${treeHtml}</div>
      </div>
    </div>
  </div>

  <!-- SKILLS TAB (hidden by default) -->
  <div id="panel-skills" style="display:none">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;max-width:900px">
      <div class="card">
        <div class="card-header">
          <div class="card-title">➕ Add New Skill</div>
          <button class="btn btn-ghost btn-sm" onclick="openImportModal('skills')">📥 Import CSV</button>
        </div>
        <div style="padding:20px">
          <div class="form-group"><label>Skill Name</label>
            <input id="newSkillName" placeholder="e.g. Solar Installation" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;background:var(--bg3);color:var(--text);font-size:14px">
          </div>
          <button class="btn btn-primary" style="width:100%" onclick="addSkillAdmin()">➕ Add Skill</button>
        </div>
      </div>
      <div class="card">
        <div class="card-header">
          <div class="card-title">🛠️ All Skills (${skills.length})</div>
        </div>
        <div style="padding:16px">
          ${skills.length === 0 ? '<div style="text-align:center;padding:20px;color:var(--muted)">No skills added yet</div>' :
            skills.map(s => `
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border)">
              <div>
                <div style="font-weight:600">${s.name}</div>
                <div style="font-size:12px;color:var(--muted)">${s.engineer_count || 0} engineers · ${s.is_active ? 'Active' : 'Inactive'}</div>
              </div>
              <div style="display:flex;gap:6px;align-items:center">
                <button onclick="toggleSkill(${s.id},${s.is_active})" style="background:transparent;border:1px solid var(--border);color:var(--muted);padding:3px 10px;border-radius:6px;cursor:pointer;font-size:11px">
                  ${s.is_active ? '🔴 Disable' : '🟢 Enable'}
                </button>
                <button onclick="deleteSkill(${s.id},'${s.name.replace(/'/g,"\'")}',${s.engineer_count||0})"
                  style="background:rgba(255,77,106,.1);border:1px solid rgba(255,77,106,.3);color:var(--red);padding:3px 10px;border-radius:6px;cursor:pointer;font-size:11px">
                  🗑 Delete
                </button>
              </div>
            </div>`).join('')}
        </div>
      </div>
    </div>
  </div>`);

  // Restore active tab
  if (window._servicesActiveTab === 'skills') switchServicesTab('skills');
}

function switchServicesTab(tab) {
  window._servicesActiveTab = tab;
  const tabs   = ['services','skills'];
  tabs.forEach(t => {
    const btn   = document.getElementById('tab-' + t);
    const panel = document.getElementById('panel-' + t);
    if (!btn || !panel) return;
    const active = t === tab;
    btn.style.background   = active ? 'var(--card)' : 'transparent';
    btn.style.color        = active ? 'var(--text)'  : 'var(--muted)';
    btn.style.boxShadow    = active ? '0 1px 4px rgba(0,0,0,.15)' : 'none';
    panel.style.display    = active ? '' : 'none';
  });
}

function toggleParentFields() {
  const hasParent = document.getElementById('svcParent').value !== '';
  const skillGrp  = document.getElementById('svcSkillGroup');
  if (skillGrp) skillGrp.style.opacity = hasParent ? '.4' : '1';
}

function editServiceById(id) {
  const s = (window._svcMap || {})[id];
  if (!s) { toast('Service not found', 'error'); return; }
  editService(s);
}

function editService(s) {
  document.getElementById('svcId').value          = s.id;
  document.getElementById('svcParent').value       = s.parent_id || '';
  document.getElementById('svcName').value         = s.name;
  document.getElementById('svcIcon').value         = s.icon || '';
  document.getElementById('svcSkill').value        = s.required_skill_id || '';
  document.getElementById('svcBasePrice').value    = s.base_price    || '';
  document.getElementById('svcVisitCharge').value  = s.visit_charge  || '';
  document.getElementById('svcPerKmRate').value    = s.per_km_rate   || '';
  document.getElementById('svcPlatformPct').value  = s.platform_charge_pct || '20';
  toggleParentFields();
  // Switch to services tab
  switchServicesTab('services');
  toast('Editing: ' + s.name, 'success');
}

function clearServiceForm() {
  ['svcId','svcParent','svcName','svcIcon','svcSkill','svcBasePrice','svcVisitCharge','svcPerKmRate'].forEach(id => {
    const el = document.getElementById(id); if (el) el.value = '';
  });
  document.getElementById('svcPlatformPct').value = '20';
  toggleParentFields();
}

async function saveService() {
  const id          = document.getElementById('svcId').value;
  const parentId    = document.getElementById('svcParent').value || null;
  const name        = document.getElementById('svcName').value.trim();
  const icon        = document.getElementById('svcIcon').value.trim();
  const skillId     = document.getElementById('svcSkill').value || null;
  const basePrice   = parseFloat(document.getElementById('svcBasePrice').value)   || 0;
  const visitCharge = parseFloat(document.getElementById('svcVisitCharge').value) || 0;
  const platformPct = parseFloat(document.getElementById('svcPlatformPct').value) || 20;
  if (!name) { toast('Service name required', 'error'); return; }
  const perKmRate = parseFloat(document.getElementById('svcPerKmRate')?.value) || 0;
  const r = await api('POST', '/admin/services', {
    id: id || null, parent_id: parentId, name, icon: icon || '🔧',
    required_skill_id: skillId, base_price: basePrice,
    visit_charge: visitCharge, per_km_rate: perKmRate, platform_charge_pct: platformPct
  });
  if (r.success) { toast(id ? 'Service updated!' : 'Service added!', 'success'); clearServiceForm(); renderServices(); }
  else toast(r.message || 'Error', 'error');
}

async function toggleService(id, isActive) {
  const r = await api('POST', '/admin/services', { action: 'toggle', id });
  if (r.success) renderServices();
  else toast(r.message || 'Error', 'error');
}

async function deleteService(id, event) {
  // Use closest() to get name from button regardless of which child element was clicked
  var btn  = event && event.target ? event.target.closest('[data-name]') : null;
  var name = btn ? btn.getAttribute('data-name') : 'this service';
  if (!confirm('Delete service "' + (name || 'this service') + '"?\nThis cannot be undone.')) return;
  const r = await api('POST', '/admin/services', { action: 'delete', id });
  if (r.success) { toast('Service deleted', 'success'); renderServices(); }
  else toast(r.message || 'Cannot delete service', 'error');
}

async function addSkillAdmin() {
  const name = document.getElementById('newSkillName').value.trim();
  if (!name) { toast('Enter a skill name', 'error'); return; }
  const r = await api('POST', '/admin/skills', { name });
  if (r.success) { toast('Skill added!', 'success'); document.getElementById('newSkillName').value=''; renderServices(); }
  else toast(r.message || 'Error', 'error');
}

async function toggleSkill(id, isActive) {
  const r = await api('POST', '/admin/skills', { action: 'toggle', skill_id: id });
  if (r.success) renderServices();
  else toast(r.message || 'Error', 'error');
}

async function deleteSkill(id, name, engineerCount) {
  var msg = 'Delete skill "' + name + '"?';
  if (engineerCount > 0) msg += '\n⚠️ ' + engineerCount + ' engineer(s) have this skill.';
  if (!confirm(msg)) return;
  const r = await api('POST', '/admin/skills', { action: 'delete', skill_id: id });
  if (r.success) { toast('Skill deleted', 'success'); renderServices(); }
  else toast(r.message || 'Cannot delete skill', 'error');
}

// ─── SETTINGS ─────────────────────────────────────────────
async function renderSettings() {
  setContent('<div class="loading"><div class="spinner"></div> Loading settings...</div>');
  const r = await api('GET', '/admin/settings');
  if (!r.success) { setContent('<div class="empty">Failed to load settings</div>'); return; }
  const s = r.data;
  const configured = k => s[k] && !['***set***','***configured***','***active***'].includes(s[k]);
  const statusChip = (ok, label) => `<span style="font-size:11px;padding:2px 8px;border-radius:20px;font-weight:600;background:${ok?'rgba(34,197,94,.15)':'rgba(251,191,36,.15)'};color:${ok?'#16A34A':'#D97706'}">${ok?'✓ '+label:'⚠ '+label}</span>`;

  setContent(`
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;">

    <!-- ── Company & Operations ── -->
    <div class="card">
      <div class="card-header"><div class="card-title">🏢 Company Info</div></div>
      <div style="padding:20px">
        <div class="form-row">
          <div class="form-group" style="margin:0"><label>Company Name</label><input id="s_company_name" value="${s.company_name||''}"></div>
          <div class="form-group" style="margin:0"><label>Support Phone</label><input id="s_company_phone" value="${s.company_phone||''}"></div>
        </div>
        <div class="form-group"><label>Support Email</label><input id="s_company_email" value="${s.company_email||''}"></div>
        <div class="form-row">
          <div class="form-group" style="margin:0"><label>Currency Code</label><input id="s_currency" value="${s.currency||'INR'}"></div>
          <div class="form-group" style="margin:0"><label>Currency Symbol</label><input id="s_currency_symbol" value="${s.currency_symbol||'₹'}"></div>
        </div>
      </div>
    </div>

    <!-- ── Operational ── -->
    <div class="card">
      <div class="card-header"><div class="card-title">⚙️ Operations</div></div>
      <div style="padding:20px">
        <div class="form-row">
          <div class="form-group" style="margin:0"><label>GPS Interval (sec)</label><input id="s_gps_interval" type="number" value="${s.gps_interval||5}"></div>
          <div class="form-group" style="margin:0"><label>Assign Radius (km)</label><input id="s_assign_radius_km" type="number" value="${s.assign_radius_km||20}"></div>
        </div>
        <div class="form-row">
          <div class="form-group" style="margin:0"><label>OTP Expiry (min)</label><input id="s_otp_expiry_min" type="number" value="${s.otp_expiry_min||10}"></div>
          <div class="form-group" style="margin:0"><label>Platform Charge (%)</label><input id="s_platform_charge_pct" type="number" min="0" max="100" step="0.5" value="${s.platform_charge_pct||20}"></div>
        </div>
        <div class="form-group"><label>Google Maps API Key</label><input id="s_google_maps_key" value="${s.google_maps_key||''}" placeholder="AIza..."></div>
      </div>
    </div>

    <!-- ── Visit Charge ── -->
    <div class="card">
      <div class="card-header"><div class="card-title">🚗 Visit Charge Formula</div></div>
      <div style="padding:20px">
        <div style="background:rgba(79,110,247,.08);border:1px solid rgba(79,110,247,.2);border-radius:10px;padding:10px 12px;margin-bottom:14px;font-size:12px;color:var(--muted);line-height:1.6">
          <b>Visit Charge = Base + (km − Free km) × Per km Rate</b><br>
          Capped at Max. Set Per km Rate = 0 for flat fee.
        </div>
        <div class="form-row">
          <div class="form-group" style="margin:0"><label>Base Charge (₹)</label><input id="s_visit_base_charge" oninput="updateVisitPreview()" type="number" min="0" step="0.01" value="${s.visit_base_charge||0}" placeholder="100"></div>
          <div class="form-group" style="margin:0"><label>Per km Rate (₹/km)</label><input id="s_visit_per_km_rate" oninput="updateVisitPreview()" type="number" min="0" step="0.5" value="${s.visit_per_km_rate||0}" placeholder="10"></div>
        </div>
        <div class="form-row">
          <div class="form-group" style="margin:0"><label>Free km</label><input id="s_visit_free_km" oninput="updateVisitPreview()" type="number" min="0" step="0.5" value="${s.visit_free_km||0}" placeholder="3"></div>
          <div class="form-group" style="margin:0"><label>Max Charge Cap (₹)</label><input id="s_visit_max_km_charge" oninput="updateVisitPreview()" type="number" min="0" step="1" value="${s.visit_max_km_charge||200}" placeholder="200"></div>
        </div>
        <div style="background:var(--bg3);border-radius:8px;padding:10px;margin-top:8px;font-size:13px" id="visitFormulaPreview">📐 Formula preview updates as you type</div>
      </div>
    </div>

    <!-- ── GST / Tax ── -->
    <div class="card">
      <div class="card-header"><div class="card-title">🧾 GST / Tax</div></div>
      <div style="padding:20px">
        <div style="background:rgba(34,197,94,.06);border:1px solid rgba(34,197,94,.2);border-radius:8px;padding:10px 12px;margin-bottom:14px;font-size:12px;color:var(--muted)">
          Shown on invoices as CGST + SGST. Set rate to 0 to disable GST.
        </div>
        <div class="form-row">
          <div class="form-group" style="margin:0"><label>Company GSTIN</label><input id="s_company_gstin" value="${s.company_gstin||''}" placeholder="22AAAAA0000A1Z5"></div>
          <div class="form-group" style="margin:0"><label>Company PAN</label><input id="s_company_pan" value="${s.company_pan||''}" placeholder="AAAAA0000A"></div>
        </div>
        <div class="form-row">
          <div class="form-group" style="margin:0"><label>GST Rate (%)</label><input id="s_gst_rate" type="number" min="0" max="28" step="0.5" value="${s.gst_rate||'0'}" placeholder="18"></div>
          <div class="form-group" style="margin:0"><label>HSN Code</label><input id="s_hsn_code" value="${s.hsn_code||''}" placeholder="998511"></div>
        </div>
      </div>
    </div>

    <!-- ── API Integration Toggles ── -->
    <div class="card" style="grid-column:1/-1">
      <div class="card-header">
        <div class="card-title">🔌 API Integrations</div>
        <button class="btn btn-ghost btn-sm" onclick="showPage('api-settings',null)">Configure & Setup Guides →</button>
      </div>
      <div style="padding:0 20px 4px">
        <div style="background:rgba(79,110,247,.06);border:1px solid rgba(79,110,247,.2);border-radius:10px;padding:10px 14px;margin-bottom:4px;font-size:12px;color:var(--muted)">
          Enable or disable each API here. To enter credentials and view full setup guides, go to <b>API Settings</b>.
        </div>
      </div>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:0">

        <!-- Firebase FCM -->
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);border-right:1px solid var(--border)">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px">
            <div style="flex:1">
              <div style="font-weight:600;font-size:13px;margin-bottom:4px">🔥 Firebase Push Notifications</div>
              ${statusChip(configured('fcm_project_id'), configured('fcm_project_id') ? 'Project: '+s.fcm_project_id : 'Not configured')}
            </div>
            <select id="s_fcm_enabled" style="flex-shrink:0;width:105px;padding:5px 8px;border:1px solid var(--border);border-radius:8px;background:var(--bg3);color:var(--text);font-size:12px">
              <option value="0" ${(s.fcm_enabled||'0')==='0'?'selected':''}>Disabled</option>
              <option value="1" ${s.fcm_enabled==='1'?'selected':''}>Enabled</option>
            </select>
          </div>
        </div>

        <!-- WhatsApp OTP -->
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);border-right:1px solid var(--border)">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px">
            <div style="flex:1">
              <div style="font-weight:600;font-size:13px;margin-bottom:4px">📱 WhatsApp OTP</div>
              ${statusChip(configured('twilio_account_sid')||configured('meta_wa_token'), (configured('twilio_account_sid')||configured('meta_wa_token')) ? 'Via '+(s.whatsapp_provider||'Twilio') : 'Not configured')}
            </div>
            <select id="s_whatsapp_enabled" style="flex-shrink:0;width:105px;padding:5px 8px;border:1px solid var(--border);border-radius:8px;background:var(--bg3);color:var(--text);font-size:12px">
              <option value="0" ${(s.whatsapp_enabled||'0')==='0'?'selected':''}>Disabled</option>
              <option value="1" ${s.whatsapp_enabled==='1'?'selected':''}>Enabled</option>
            </select>
          </div>
        </div>

        <!-- Email SMTP -->
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);border-right:1px solid var(--border)">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px">
            <div style="flex:1">
              <div style="font-weight:600;font-size:13px;margin-bottom:4px">📧 Email OTP (SMTP)</div>
              ${statusChip(configured('smtp_host'), configured('smtp_host') ? s.smtp_host : 'Not configured')}
            </div>
            <select id="s_smtp_enabled" style="flex-shrink:0;width:105px;padding:5px 8px;border:1px solid var(--border);border-radius:8px;background:var(--bg3);color:var(--text);font-size:12px">
              <option value="0" ${(s.smtp_enabled||'0')==='0'?'selected':''}>Disabled</option>
              <option value="1" ${s.smtp_enabled==='1'?'selected':''}>Enabled</option>
            </select>
          </div>
        </div>

        <!-- KYC -->
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);border-right:1px solid var(--border)">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px">
            <div style="flex:1">
              <div style="font-weight:600;font-size:13px;margin-bottom:4px">🪪 KYC Verification</div>
              ${statusChip(configured('sandbox_api_key'), configured('sandbox_api_key') ? 'Sandbox.co.in' : 'Not configured')}
              <div style="font-size:11px;color:var(--muted);margin-top:4px">Auto-approve: ${s.kyc_auto_approve==='1'?'<span style="color:#16A34A">ON</span>':'OFF'}</div>
            </div>
            <select id="s_kyc_required" style="flex-shrink:0;width:105px;padding:5px 8px;border:1px solid var(--border);border-radius:8px;background:var(--bg3);color:var(--text);font-size:12px">
              <option value="1" ${(s.kyc_required||'1')==='1'?'selected':''}>Required</option>
              <option value="0" ${s.kyc_required==='0'?'selected':''}>Disabled</option>
            </select>
          </div>
        </div>

        <!-- Payment Gateway -->
        <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px">
            <div style="flex:1">
              <div style="font-weight:600;font-size:13px;margin-bottom:4px">💳 Payment Gateway</div>
              ${statusChip(configured('razorpay_key_id'), configured('razorpay_key_id') ? 'Razorpay' : 'Cash/UPI only')}
            </div>
            <select id="s_payment_gateway" style="flex-shrink:0;width:105px;padding:5px 8px;border:1px solid var(--border);border-radius:8px;background:var(--bg3);color:var(--text);font-size:12px">
              <option value="cash_upi" ${(s.payment_gateway||'cash_upi')==='cash_upi'?'selected':''}>Cash/UPI</option>
              <option value="razorpay" ${s.payment_gateway==='razorpay'?'selected':''}>Razorpay</option>
              <option value="paytm"    ${s.payment_gateway==='paytm'?'selected':''}>Paytm</option>
            </select>
          </div>
        </div>

      </div><!-- end grid -->
    </div><!-- end integrations card -->

  </div><!-- end outer grid -->

  <div style="margin-top:20px">
    <button class="btn btn-primary" onclick="saveSettings()">💾 Save All Settings</button>
  </div>
`);
  updateVisitPreview();
}

// ─── API SETTINGS PAGE ────────────────────────────────────────────────────────
async function renderApiSettings() {
  setContent('<div class="loading"><div class="spinner"></div> Loading API settings...</div>');
  const r = await api('GET', '/admin/settings');
  if (!r.success) { setContent('<div class="empty">Failed to load settings</div>'); return; }
  const s = r.data;
  const masked = v => v && ['***set***','***configured***','***active***'].includes(v);

  setContent(`
  <div style="display:flex;flex-direction:column;gap:20px;max-width:860px">

  <!-- ── PAGE HEADER ── -->
  <div style="background:linear-gradient(135deg,var(--accent),#7C3AED);border-radius:14px;padding:24px 28px;color:#fff">
    <div style="font-size:22px;font-weight:700;margin-bottom:6px">🔌 API Settings & Setup Guides</div>
    <div style="font-size:14px;opacity:.85">Configure credentials for all third-party integrations. Each section includes a step-by-step guide.</div>
  </div>

  <!-- ────────────────────────────────────────── -->
  <!-- 1. FIREBASE FCM v1                         -->
  <!-- ────────────────────────────────────────── -->
  <div class="card" id="section-firebase">
    <div class="card-header" style="cursor:pointer" onclick="toggleApiSection('firebase')">
      <div style="display:flex;align-items:center;gap:10px">
        <div style="width:36px;height:36px;border-radius:10px;background:rgba(255,152,0,.15);display:flex;align-items:center;justify-content:center;font-size:18px">🔥</div>
        <div>
          <div class="card-title">Firebase Push Notifications (FCM v1)</div>
          <div style="font-size:12px;color:var(--muted)">Real-time push alerts to engineers and customers</div>
        </div>
      </div>
      <span id="firebase-chevron" style="font-size:18px;color:var(--muted)">▼</span>
    </div>
    <div id="firebase-body" style="padding:0 24px 24px">

      <!-- Step-by-step guide -->
      <div style="background:rgba(255,152,0,.07);border:1px solid rgba(255,152,0,.25);border-radius:12px;padding:16px 18px;margin-bottom:20px">
        <div style="font-weight:700;font-size:13px;color:#F59E0B;margin-bottom:10px">📋 Setup Guide — Firebase FCM v1</div>
        <div style="font-size:12px;color:var(--muted);line-height:2">
          <b style="color:var(--text)">Step 1</b> — Go to <a href="https://console.firebase.google.com" target="_blank" style="color:var(--accent)">console.firebase.google.com</a> and open your project (or create one)<br>
          <b style="color:var(--text)">Step 2</b> — Click <b>⚙ Project Settings</b> (gear icon) → <b>Service accounts</b> tab<br>
          <b style="color:var(--text)">Step 3</b> — Click <b>"Generate new private key"</b> → confirm → a <code style="background:var(--bg3);padding:1px 5px;border-radius:4px">serviceAccountKey.json</code> file will download<br>
          <b style="color:var(--text)">Step 4</b> — Open that file in a text editor, copy the <b>entire JSON content</b>, paste into the field below<br>
          <b style="color:var(--text)">Step 5</b> — From <b>Project Settings → General</b>, copy your <b>Project ID</b> (e.g. <code style="background:var(--bg3);padding:1px 5px;border-radius:4px">hridya-tech-abc12</code>)<br>
          <b style="color:var(--text)">Step 6</b> — For Web/PWA push: go to <b>Project Settings → Cloud Messaging</b> → Web configuration → <b>Generate key pair</b> to get VAPID key<br>
          <b style="color:var(--text)">Step 7</b> — For Web app config: <b>Project Settings → Your apps → Add Web app</b> → copy apiKey, appId, messagingSenderId<br>
          <b style="color:var(--text)">Step 8</b> — Click <b>"Save & Test Firebase"</b> below — a test push should appear on your device
        </div>
        <div style="margin-top:10px;font-size:11px;color:#F59E0B;background:rgba(255,152,0,.1);border-radius:6px;padding:6px 10px">
          ⚠ FCM v1 uses OAuth2 (service account JSON). The old Server Key / legacy API no longer works as of June 2024.
        </div>
      </div>

      <!-- Fields -->
      <div class="form-row">
        <div class="form-group" style="margin:0"><label>Enable Push Notifications</label>
          <select id="a_fcm_enabled" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;background:var(--bg3);color:var(--text)">
            <option value="0" ${(s.fcm_enabled||'0')==='0'?'selected':''}>Disabled</option>
            <option value="1" ${s.fcm_enabled==='1'?'selected':''}>Enabled</option>
          </select>
        </div>
        <div class="form-group" style="margin:0"><label>Firebase Project ID</label>
          <input id="a_fcm_project_id" value="${s.fcm_project_id||''}" placeholder="your-project-id">
          <div style="font-size:11px;color:var(--muted);margin-top:4px">Project Settings → General → Project ID</div>
        </div>
      </div>

      <div class="form-group" style="margin-top:12px">
        <label>Service Account JSON
          <span style="font-weight:400;font-size:11px;color:var(--muted)"> — paste entire contents of serviceAccountKey.json</span>
        </label>
        <textarea id="a_fcm_service_account_json" rows="5"
          style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;background:var(--bg3);color:var(--text);font-size:12px;font-family:'JetBrains Mono',monospace;resize:vertical;box-sizing:border-box"
          placeholder='{"type":"service_account","project_id":"...","private_key":"-----BEGIN RSA PRIVATE KEY-----\\n...","client_email":"firebase-adminsdk-...@....iam.gserviceaccount.com",...}'>${s.fcm_service_account_json ? '***configured***' : ''}</textarea>
        ${s.fcm_service_account_json ? '<div style="font-size:11px;color:#16A34A;margin-top:4px">✓ Service account is configured — paste new JSON only if replacing</div>' : '<div style="font-size:11px;color:#D97706;margin-top:4px">⚠ Not yet configured — paste your serviceAccountKey.json contents above</div>'}
      </div>

      <div style="font-size:12px;font-weight:600;color:var(--muted);margin:16px 0 8px">🌐 Web / PWA Config <span style="font-weight:400">(for browser push notifications)</span></div>
      <div class="form-row">
        <div class="form-group" style="margin:0"><label>Web API Key</label><input id="a_fcm_web_api_key" value="${s.fcm_web_api_key||''}" placeholder="AIzaSy..."></div>
        <div class="form-group" style="margin:0"><label>App ID</label><input id="a_fcm_app_id" value="${s.fcm_app_id||''}" placeholder="1:123456:web:abc123"></div>
      </div>
      <div class="form-row">
        <div class="form-group" style="margin:0"><label>Messaging Sender ID</label><input id="a_fcm_messaging_sender_id" value="${s.fcm_messaging_sender_id||s.fcm_sender_id||''}" placeholder="123456789012"></div>
        <div class="form-group" style="margin:0"><label>VAPID Key (Web Push)</label><input id="a_fcm_vapid_key" value="${s.fcm_vapid_key||''}" placeholder="BN...">
          <div style="font-size:11px;color:var(--muted);margin-top:4px">Cloud Messaging tab → Web Push certificates → Key pair</div>
        </div>
      </div>

      <div class="form-group" style="margin-top:12px">
        <label>Test Device Token <span style="font-weight:400;font-size:11px;color:var(--muted)">(optional — paste any FCM token to send a test push)</span></label>
        <input id="a_fcm_test_token" placeholder="Paste a device FCM token here to test...">
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:4px">
        <button class="btn btn-primary btn-sm" onclick="saveFcmSettings()">🔥 Save & Test Firebase</button>
        <button class="btn btn-ghost btn-sm" onclick="sendTestToAll('engineer')">📤 Broadcast Test → Engineers</button>
        <button class="btn btn-ghost btn-sm" onclick="sendTestToAll('customer')">📤 Broadcast Test → Customers</button>
      </div>
    </div>
  </div>

  <!-- ────────────────────────────────────────── -->
  <!-- 2. KYC — Sandbox.co.in                     -->
  <!-- ────────────────────────────────────────── -->
  <div class="card" id="section-kyc">
    <div class="card-header" style="cursor:pointer" onclick="toggleApiSection('kyc')">
      <div style="display:flex;align-items:center;gap:12px">
        <span style="font-size:20px">🤳</span>
        <div>
          <div class="card-title">Engineer Registration Settings</div>
          <div style="font-size:12px;color:var(--muted)">Control selfie review and auto-approval</div>
        </div>
      </div>
      <span id="kyc-chevron" style="font-size:18px;color:var(--muted)">▼</span>
    </div>
    <div id="kyc-body" style="padding:0 24px 24px">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:16px">
        <div class="form-group" style="margin:0"><label>Auto Approve Selfie</label>
          <select id="a_kyc_auto_approve" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;background:var(--bg3);color:var(--text)">
            <option value="0" ${(s.kyc_auto_approve||'0')==='0'?'selected':''}>No — admin reviews selfie</option>
            <option value="1" ${s.kyc_auto_approve==='1'?'selected':''}>Yes — auto approve on selfie upload</option>
          </select>
        </div>
      </div>
      <button class="btn btn-primary btn-sm" style="margin-top:16px" onclick="saveApiSettings(['kyc_auto_approve'], 'a_')">💾 Save Settings</button>
    </div>
  </div>
  <div class="card" id="section-payment">
    <div class="card-header" style="cursor:pointer" onclick="toggleApiSection('payment')">
      <div style="display:flex;align-items:center;gap:10px">
        <div style="width:36px;height:36px;border-radius:10px;background:rgba(79,110,247,.12);display:flex;align-items:center;justify-content:center;font-size:18px">💳</div>
        <div>
          <div class="card-title">Payment Gateway — Razorpay</div>
          <div style="font-size:12px;color:var(--muted)">Online payments for job bookings and wallet top-ups</div>
        </div>
      </div>
      <span id="payment-chevron" style="font-size:18px;color:var(--muted)">▼</span>
    </div>
    <div id="payment-body" style="padding:0 24px 24px">

      <div style="background:rgba(79,110,247,.07);border:1px solid rgba(79,110,247,.25);border-radius:12px;padding:16px 18px;margin-bottom:20px">
        <div style="font-weight:700;font-size:13px;color:var(--accent);margin-bottom:10px">📋 Setup Guide — Razorpay</div>
        <div style="font-size:12px;color:var(--muted);line-height:2">
          <b style="color:var(--text)">Step 1</b> — Sign up at <a href="https://razorpay.com" target="_blank" style="color:var(--accent)">razorpay.com</a> and complete KYC verification<br>
          <b style="color:var(--text)">Step 2</b> — Go to <b>Settings → API Keys</b> in the Razorpay Dashboard<br>
          <b style="color:var(--text)">Step 3</b> — Click <b>"Generate Test Key"</b> — copy the <b>Key ID</b> and <b>Key Secret</b><br>
          <b style="color:var(--text)">Step 4</b> — Paste both keys below and set Gateway to Razorpay<br>
          <b style="color:var(--text)">Step 5</b> — For production, click <b>"Generate Live Key"</b> in the same section and replace test keys<br>
          <b style="color:var(--text)">Step 6</b> — Test keys start with <code style="background:var(--bg3);padding:1px 5px;border-radius:4px">rzp_test_</code>, Live keys with <code style="background:var(--bg3);padding:1px 5px;border-radius:4px">rzp_live_</code>
        </div>
        <div style="margin-top:10px;font-size:11px;background:rgba(79,110,247,.1);border-radius:6px;padding:6px 10px;color:var(--accent)">
          ℹ Payment initiation uses server-side order creation (Key Secret never exposed to browser).
        </div>
      </div>

      <div class="form-group"><label>Payment Gateway</label>
        <select id="a_payment_gateway" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;background:var(--bg3);color:var(--text)">
          <option value="cash_upi" ${(s.payment_gateway||'cash_upi')==='cash_upi'?'selected':''}>Cash / UPI (Manual — no integration needed)</option>
          <option value="razorpay" ${s.payment_gateway==='razorpay'?'selected':''}>Razorpay</option>
          <option value="paytm"    ${s.payment_gateway==='paytm'?'selected':''}>Paytm</option>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group" style="margin:0"><label>Razorpay Key ID</label><input id="a_razorpay_key_id" value="${s.razorpay_key_id||''}" placeholder="rzp_test_... or rzp_live_..."></div>
        <div class="form-group" style="margin:0"><label>Razorpay Key Secret</label><input id="a_razorpay_key_secret" type="password" value="${s.razorpay_key_secret||''}" placeholder="••••••••••••••••"></div>
      </div>
      <button class="btn btn-primary btn-sm" style="margin-top:12px" onclick="saveApiSettings(['payment_gateway','razorpay_key_id','razorpay_key_secret'], 'a_')">💾 Save Payment Settings</button>
    </div>
  </div>

  <!-- ────────────────────────────────────────── -->
  <!-- 4. WHATSAPP OTP                            -->
  <!-- ────────────────────────────────────────── -->
  <div class="card" id="section-whatsapp">
    <div class="card-header" style="cursor:pointer" onclick="toggleApiSection('whatsapp')">
      <div style="display:flex;align-items:center;gap:10px">
        <div style="width:36px;height:36px;border-radius:10px;background:rgba(37,211,102,.12);display:flex;align-items:center;justify-content:center;font-size:18px">📱</div>
        <div>
          <div class="card-title">WhatsApp OTP</div>
          <div style="font-size:12px;color:var(--muted)">OTP delivery for customer login via Twilio or Meta</div>
        </div>
      </div>
      <span id="whatsapp-chevron" style="font-size:18px;color:var(--muted)">▼</span>
    </div>
    <div id="whatsapp-body" style="padding:0 24px 24px">

      <!-- Provider selector -->
      <div class="form-group">
        <label>WhatsApp Provider</label>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:4px">
          <label style="cursor:pointer;border:2px solid var(--border);border-radius:10px;padding:12px 14px;display:flex;align-items:center;gap:10px;transition:.15s" id="wa_tab_twilio" onclick="switchWaTab('twilio')">
            <input type="radio" name="wa_provider" value="twilio" ${(s.whatsapp_provider||'twilio')==='twilio'?'checked':''} style="margin:0">
            <div><div style="font-weight:600;font-size:13px">Twilio</div><div style="font-size:11px;color:var(--muted)">Reliable, global, paid</div></div>
          </label>
          <label style="cursor:pointer;border:2px solid var(--border);border-radius:10px;padding:12px 14px;display:flex;align-items:center;gap:10px;transition:.15s" id="wa_tab_meta" onclick="switchWaTab('meta')">
            <input type="radio" name="wa_provider" value="meta" ${s.whatsapp_provider==='meta'?'checked':''} style="margin:0">
            <div><div style="font-weight:600;font-size:13px">Meta Cloud API</div><div style="font-size:11px;color:var(--muted)">Official, free tier</div></div>
          </label>
        </div>
        <input type="hidden" id="a_whatsapp_provider" value="${s.whatsapp_provider||'twilio'}">
      </div>

      <!-- Twilio guide + fields -->
      <div id="wa_twilio_section">
        <div style="background:rgba(244,63,94,.07);border:1px solid rgba(244,63,94,.2);border-radius:12px;padding:14px 16px;margin-bottom:16px">
          <div style="font-weight:700;font-size:13px;color:#F43F5E;margin-bottom:8px">📋 Twilio Setup Guide</div>
          <div style="font-size:12px;color:var(--muted);line-height:2">
            <b style="color:var(--text)">Step 1</b> — Sign up at <a href="https://www.twilio.com/try-twilio" target="_blank" style="color:var(--accent)">twilio.com/try-twilio</a> (free trial available)<br>
            <b style="color:var(--text)">Step 2</b> — From Console dashboard, copy <b>Account SID</b> and <b>Auth Token</b><br>
            <b style="color:var(--text)">Step 3</b> — Go to <b>Messaging → Try it out → Send a WhatsApp message</b> to activate sandbox<br>
            <b style="color:var(--text)">Step 4</b> — Sandbox number is <code style="background:var(--bg3);padding:1px 5px;border-radius:4px">whatsapp:+14155238886</code> — customers must join sandbox first<br>
            <b style="color:var(--text)">Step 5</b> — For production: request a dedicated WhatsApp-enabled number from Twilio (paid)<br>
            <b style="color:var(--text)">Step 6</b> — Paste SID, token, and from-number below
          </div>
        </div>
        <div class="form-row">
          <div class="form-group" style="margin:0"><label>Account SID</label><input id="a_twilio_account_sid" value="${s.twilio_account_sid||''}" placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"></div>
          <div class="form-group" style="margin:0"><label>Auth Token</label><input id="a_twilio_auth_token" type="password" value="${s.twilio_auth_token||''}" placeholder="••••••••••••••••"></div>
        </div>
        <div class="form-group" style="margin-top:12px"><label>WhatsApp From Number</label>
          <input id="a_twilio_whatsapp_from" value="${s.twilio_whatsapp_from||'whatsapp:+14155238886'}" placeholder="whatsapp:+14155238886">
          <div style="font-size:11px;color:var(--muted);margin-top:4px">Must include <code style="background:var(--bg3);padding:1px 5px;border-radius:4px">whatsapp:</code> prefix</div>
        </div>
      </div>

      <!-- Meta guide + fields -->
      <div id="wa_meta_section" style="display:none">
        <div style="background:rgba(24,119,242,.07);border:1px solid rgba(24,119,242,.2);border-radius:12px;padding:14px 16px;margin-bottom:16px">
          <div style="font-weight:700;font-size:13px;color:#1877F2;margin-bottom:8px">📋 Meta WhatsApp Cloud API Setup</div>
          <div style="font-size:12px;color:var(--muted);line-height:2">
            <b style="color:var(--text)">Step 1</b> — Go to <a href="https://developers.facebook.com" target="_blank" style="color:var(--accent)">developers.facebook.com</a> → <b>My Apps → Create App</b> → choose <b>Business</b><br>
            <b style="color:var(--text)">Step 2</b> — Add <b>WhatsApp</b> product to your app → click <b>Set up</b><br>
            <b style="color:var(--text)">Step 3</b> — Under <b>WhatsApp → API Setup</b>, copy the <b>Phone Number ID</b> and <b>Temporary access token</b><br>
            <b style="color:var(--text)">Step 4</b> — For permanent token: <b>Business Settings → System Users → Generate token</b> with <code style="background:var(--bg3);padding:1px 5px;border-radius:4px">whatsapp_business_messaging</code> permission<br>
            <b style="color:var(--text)">Step 5</b> — Create a message template named <code style="background:var(--bg3);padding:1px 5px;border-radius:4px">otp_message</code> in <b>WhatsApp → Message Templates</b> with body: <em>"Your OTP is {{1}}. Valid for 10 minutes."</em><br>
            <b style="color:var(--text)">Step 6</b> — Wait for template approval (usually a few minutes), then paste credentials below
          </div>
        </div>
        <div class="form-row">
          <div class="form-group" style="margin:0"><label>Meta API Token</label><input id="a_meta_wa_token" type="password" value="${s.meta_wa_token||''}" placeholder="EAAxxxxxxxxxxxxxxxx"></div>
          <div class="form-group" style="margin:0"><label>Phone Number ID</label><input id="a_meta_wa_phone_id" value="${s.meta_wa_phone_id||''}" placeholder="1234567890123456"></div>
        </div>
        <div class="form-group" style="margin-top:12px"><label>Template Name</label>
          <input id="a_meta_wa_template" value="${s.meta_wa_template||'otp_message'}" placeholder="otp_message">
          <div style="font-size:11px;color:var(--muted);margin-top:4px">Must match the approved template name exactly (case-sensitive)</div>
        </div>
      </div>

      <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
        <button class="btn btn-primary btn-sm" onclick="saveWhatsAppSettings()">💾 Save WhatsApp Settings</button>
        <button class="btn btn-ghost btn-sm" onclick="testOtp()">📤 Send Test OTP</button>
      </div>
    </div>
  </div>

  <!-- ────────────────────────────────────────── -->
  <!-- 5. EMAIL SMTP                              -->
  <!-- ────────────────────────────────────────── -->
  <div class="card" id="section-smtp">
    <div class="card-header" style="cursor:pointer" onclick="toggleApiSection('smtp')">
      <div style="display:flex;align-items:center;gap:10px">
        <div style="width:36px;height:36px;border-radius:10px;background:rgba(79,110,247,.12);display:flex;align-items:center;justify-content:center;font-size:18px">📧</div>
        <div>
          <div class="card-title">Email OTP — SMTP</div>
          <div style="font-size:12px;color:var(--muted)">OTP and password reset emails via any SMTP server</div>
        </div>
      </div>
      <span id="smtp-chevron" style="font-size:18px;color:var(--muted)">▼</span>
    </div>
    <div id="smtp-body" style="padding:0 24px 24px">

      <div style="background:rgba(79,110,247,.07);border:1px solid rgba(79,110,247,.25);border-radius:12px;padding:16px 18px;margin-bottom:20px">
        <div style="font-weight:700;font-size:13px;color:var(--accent);margin-bottom:10px">📋 SMTP Setup Guide</div>
        <div style="font-size:12px;color:var(--muted);line-height:2">
          <b style="color:var(--text)">Gmail (recommended for testing)</b><br>
          &nbsp;&nbsp;1. Go to <b>Google Account → Security → 2-Step Verification → App passwords</b><br>
          &nbsp;&nbsp;2. Create an App Password for "Mail" — copy the 16-character password<br>
          &nbsp;&nbsp;3. Use host: <code style="background:var(--bg3);padding:1px 5px;border-radius:4px">smtp.gmail.com</code> · port: <code style="background:var(--bg3);padding:1px 5px;border-radius:4px">587</code> · username: your Gmail address<br>
          &nbsp;&nbsp;4. Password = the App Password (not your Gmail password)<br><br>
          <b style="color:var(--text)">Other providers</b><br>
          &nbsp;&nbsp;• <b>Zoho Mail:</b> smtp.zoho.in · port 587 · use your Zoho email + password<br>
          &nbsp;&nbsp;• <b>Hostinger:</b> smtp.hostinger.com · port 465 (SSL)<br>
          &nbsp;&nbsp;• <b>Brevo (Sendinblue):</b> smtp-relay.brevo.com · port 587 · use API key as password<br>
          &nbsp;&nbsp;• <b>SendGrid:</b> smtp.sendgrid.net · port 587 · username: apikey · password: your API key
        </div>
        <div style="margin-top:10px;font-size:11px;background:rgba(79,110,247,.1);border-radius:6px;padding:6px 10px;color:var(--accent)">
          ℹ Port 587 = STARTTLS (recommended) · Port 465 = SSL/TLS · Port 25 = usually blocked on shared hosting
        </div>
      </div>

      <div class="form-row">
        <div class="form-group" style="margin:0"><label>Enable Email OTP</label>
          <select id="a_smtp_enabled" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;background:var(--bg3);color:var(--text)">
            <option value="0" ${(s.smtp_enabled||'0')==='0'?'selected':''}>Disabled</option>
            <option value="1" ${s.smtp_enabled==='1'?'selected':''}>Enabled</option>
          </select>
        </div>
        <div class="form-group" style="margin:0"><label>SMTP Port</label>
          <input id="a_smtp_port" type="number" value="${s.smtp_port||587}" placeholder="587">
          <div style="font-size:11px;color:var(--muted);margin-top:4px">587 = STARTTLS · 465 = SSL</div>
        </div>
      </div>
      <div class="form-group" style="margin-top:12px"><label>SMTP Host</label><input id="a_smtp_host" value="${s.smtp_host||''}" placeholder="smtp.gmail.com"></div>
      <div class="form-row">
        <div class="form-group" style="margin:0"><label>SMTP Username</label><input id="a_smtp_user" value="${s.smtp_user||''}" placeholder="you@gmail.com"></div>
        <div class="form-group" style="margin:0"><label>SMTP Password / App Password</label><input id="a_smtp_pass" type="password" value="${s.smtp_pass||''}" placeholder="••••••••••••••••"></div>
      </div>
      <div class="form-row" style="margin-top:12px">
        <div class="form-group" style="margin:0"><label>From Email</label><input id="a_smtp_from_email" value="${s.smtp_from_email||''}" placeholder="noreply@fixgrid.in"></div>
        <div class="form-group" style="margin:0"><label>From Name</label><input id="a_smtp_from_name" value="${s.smtp_from_name||'FixGrid'}" placeholder="FixGrid"></div>
      </div>
      <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
        <button class="btn btn-primary btn-sm" onclick="saveApiSettings(['smtp_enabled','smtp_port','smtp_host','smtp_user','smtp_pass','smtp_from_email','smtp_from_name'], 'a_')">💾 Save SMTP Settings</button>
        <button class="btn btn-ghost btn-sm" onclick="testOtp()">📤 Send Test OTP</button>
      </div>
    </div>
  </div>

  </div><!-- end flex column -->
`);

  // Init WA tab highlight
  switchWaTab(s.whatsapp_provider || 'twilio');
}

// Toggle collapsible API section
function toggleApiSection(name) {
  const body = document.getElementById(name+'-body');
  const chev = document.getElementById(name+'-chevron');
  if (!body) return;
  const open = body.style.display !== 'none';
  body.style.display = open ? 'none' : '';
  if (chev) chev.textContent = open ? '▶' : '▼';
}

// WhatsApp provider tab switcher
function switchWaTab(provider) {
  document.getElementById('a_whatsapp_provider').value = provider;
  // Update radio buttons
  document.querySelectorAll('input[name="wa_provider"]').forEach(r => r.checked = r.value === provider);
  // Show/hide sections
  const tw = document.getElementById('wa_twilio_section');
  const mt = document.getElementById('wa_meta_section');
  if (tw) tw.style.display = provider === 'twilio' ? '' : 'none';
  if (mt) mt.style.display = provider === 'meta'   ? '' : 'none';
  // Highlight selected tab card
  const tabTw = document.getElementById('wa_tab_twilio');
  const tabMt = document.getElementById('wa_tab_meta');
  if (tabTw) tabTw.style.borderColor = provider === 'twilio' ? 'var(--accent)' : 'var(--border)';
  if (tabMt) tabMt.style.borderColor = provider === 'meta'   ? 'var(--accent)' : 'var(--border)';
}

// Kept for backward compat (called from settings page WA provider selector)
function toggleWaProvider() {
  const p = document.getElementById('s_whatsapp_provider')?.value;
  switchWaTab(p || 'twilio');
}

// Save a subset of API settings (keys list + id prefix)
async function saveApiSettings(keys, prefix) {
  const body = {};
  keys.forEach(k => {
    const el = document.getElementById((prefix||'')+k);
    if (el) body[k] = el.value;
  });
  const r = await api('POST', '/admin/save-settings', body);
  if (r.success) toast('Settings saved!', 'success');
  else toast(r.message || 'Error saving settings', 'error');
}

// Save WhatsApp settings (handles provider + respective fields)
async function saveWhatsAppSettings() {
  const provider = document.getElementById('a_whatsapp_provider')?.value || 'twilio';
  const body = { whatsapp_provider: provider };
  if (provider === 'twilio') {
    ['twilio_account_sid','twilio_auth_token','twilio_whatsapp_from'].forEach(k => {
      const el = document.getElementById('a_'+k); if (el) body[k] = el.value;
    });
  } else {
    ['meta_wa_token','meta_wa_phone_id','meta_wa_template'].forEach(k => {
      const el = document.getElementById('a_'+k); if (el) body[k] = el.value;
    });
  }
  const r = await api('POST', '/admin/save-settings', body);
  if (r.success) toast('WhatsApp settings saved!', 'success');
  else toast(r.message || 'Error saving settings', 'error');
}

function updateVisitPreview() {
  const base   = parseFloat(document.getElementById('s_visit_base_charge')?.value)  || 0;
  const perKm  = parseFloat(document.getElementById('s_visit_per_km_rate')?.value)  || 0;
  const freeKm = parseFloat(document.getElementById('s_visit_free_km')?.value)      || 0;
  const maxCap = parseFloat(document.getElementById('s_visit_max_km_charge')?.value)|| 999;
  const el = document.getElementById('visitFormulaPreview');
  if (!el) return;
  if (perKm === 0) { el.innerHTML = '📐 Flat charge: <b>₹'+base+'</b> for every job'; return; }
  const rows = [2,3,5,8,10,15,20].map(km => {
    const total = base + Math.min(Math.max(0,km-freeKm)*perKm, maxCap);
    return '<span style="margin-right:14px"><b>'+km+'km</b> → ₹'+total.toFixed(0)+'</span>';
  });
  el.innerHTML = '📐 <b>₹'+base+' base + (km − '+freeKm+'km free) × ₹'+perKm+'/km, max ₹'+maxCap+'</b>'
    +'<div style="margin-top:6px;color:var(--muted)">'+rows.join('')+'</div>';
}

async function sendTestToAll(userType) {
  const btn = event.target.closest('button')||event.target;
  btn.disabled = true; btn.textContent = 'Sending...';
  const res = await api('POST', '/admin/save-fcm', {
    send_test_broadcast: userType,
    enabled: document.getElementById('a_fcm_enabled')?.value || '1',
  });
  btn.disabled = false;
  btn.textContent = userType === 'engineer' ? '📤 Broadcast Test → Engineers' : '📤 Broadcast Test → Customers';
  toast(res.message || (res.success ? 'Test sent!' : 'Failed'), res.success ? 'success' : 'error');
}

async function saveFcmSettings() {
  const json      = (document.getElementById('a_fcm_service_account_json')?.value?.trim()||'').replace(/^\*\*\*configured\*\*\*$/, '');
  const projectId = document.getElementById('a_fcm_project_id')?.value?.trim() || '';
  const enabled   = document.getElementById('a_fcm_enabled')?.value || '0';
  const testToken = document.getElementById('a_fcm_test_token')?.value?.trim() || '';
  if (!json && !projectId) { toast('Enter Project ID or paste Service Account JSON', 'error'); return; }
  const btn = event.target.closest('button')||event.target;
  btn.disabled = true; btn.textContent = 'Saving...';
  const res = await api('POST', '/admin/save-fcm', { service_account_json: json, project_id: projectId, enabled, test_token: testToken });
  btn.disabled = false; btn.textContent = '🔥 Save & Test Firebase';
  if (res.success) {
    toast(res.message || 'Firebase saved!', 'success');
    if (res.data?.test_sent === true)  toast('✅ Test push sent successfully!', 'success');
    if (res.data?.test_sent === false) toast('⚠ Test push failed — check token and credentials', 'error');
  } else toast(res.message || 'Error saving Firebase settings', 'error');
}

async function testOtp() {
  const phone = prompt('Phone number to receive test OTP (e.g. +919876543210):');
  if (!phone) return;
  const r = await api('POST', '/auth/register', {phone, name:'Test'});
  toast(r.message || (r.success ? 'Test OTP sent!' : 'Failed'), r.success ? 'success' : 'error');
}

async function saveSettings() {
  const keys = ['company_name','company_phone','company_email','currency','currency_symbol',
                 'gps_interval','assign_radius_km','otp_expiry_min','platform_charge_pct',
                 'payment_gateway','google_maps_key',
                 'kyc_required','company_gstin','company_pan','gst_rate','hsn_code',
                 'fcm_enabled','whatsapp_enabled','smtp_enabled',
                 'visit_base_charge','visit_per_km_rate','visit_free_km','visit_max_km_charge'];
  const body = {};
  keys.forEach(k => { const el = document.getElementById('s_'+k); if (el) body[k] = el.value; });
  const r = await api('POST', '/admin/save-settings', body);
  if (r.success) toast('Settings saved!', 'success');
  else toast(r.message || 'Error saving settings', 'error');
}


// ─── PLATFORM FEES ───────────────────────────────────────────
async function renderPlatformFees() {
  setContent('<div class="loading"><div class="spinner"></div> Loading platform fees...</div>');
  const from = new Date(new Date().setDate(1)).toISOString().slice(0,10);
  const to   = new Date().toISOString().slice(0,10);
  const r = await api('GET', '/admin/platform-fees?from=' + from + '&to=' + to);
  if (!r.success) { setContent('<div class="empty">Failed to load fees</div>'); return; }
  const d = r.data;
  const s = d.summary;
  const fmt = n => Number(n||0).toLocaleString('en-IN', {minimumFractionDigits:2});
  const mono = 'font-family:Geist Mono,monospace';
  let rows = '';
  d.fees.forEach(function(f) {
    const dateStr = f.end_time ? new Date(f.end_time).toLocaleDateString('en-IN') : '-';
    const collDate = f.collected_at ? new Date(f.collected_at).toLocaleDateString('en-IN') : '';
    const statusBadge = f.fee_status === 'collected'
      ? '<span class="badge badge-completed">Collected</span>'
      : '<span class="badge badge-pending">Pending</span>';
    const actionBtn = f.fee_status !== 'collected'
      ? '<button class="btn btn-success btn-sm" onclick="markFeeCollected(' + f.job_id + ')">Mark Collected</button>'
      : '<span style="font-size:11px;color:var(--muted)">' + collDate + '</span>';
    rows += '<tr>' +
      '<td><span style="' + mono + ';font-size:11px;color:var(--accent)">' + f.job_number + '</span></td>' +
      '<td style="font-size:12px;color:var(--muted)">' + dateStr + '</td>' +
      '<td><div style="font-size:13px;font-weight:500">' + f.engineer_name + '</div><div style="font-size:11px;color:var(--muted)">' + f.engineer_phone + '</div></td>' +
      '<td style="font-size:13px">' + f.customer_name + '</td>' +
      '<td style="' + mono + '">₹' + fmt(f.final_amount) + '</td>' +
      '<td style="' + mono + ';font-weight:700;color:var(--accent)">₹' + fmt(f.platform_charge) + '</td>' +
      '<td>' + statusBadge + '</td>' +
      '<td>' + actionBtn + '</td>' +
    '</tr>';
  });
  const html = '<div class="stat-grid" style="grid-template-columns:repeat(3,1fr)">' +
    '<div class="stat-card cyan"><div class="stat-label">Total Cash Fees</div><div class="stat-value" style="font-size:22px">₹' + fmt(s.total) + '</div></div>' +
    '<div class="stat-card green"><div class="stat-label">Collected</div><div class="stat-value" style="font-size:22px;color:var(--green)">₹' + fmt(s.collected) + '</div></div>' +
    '<div class="stat-card red"><div class="stat-label">Outstanding</div><div class="stat-value" style="font-size:22px;color:var(--red)">₹' + fmt(s.pending) + '</div></div>' +
    '</div>' +
    '<div class="card">' +
      '<div class="card-header"><div class="card-title">🏦 Cash Platform Fee Collection</div><div style="font-size:12px;color:var(--muted)">Mark fees collected when engineer pays you</div></div>' +
      '<div style="overflow-x:auto"><table id="feeTable">' +
        '<thead><tr><th>Job #</th><th>Date</th><th>Engineer</th><th>Customer</th><th>Job Amount</th><th>Platform Fee</th><th>Status</th><th>Action</th></tr></thead>' +
        '<tbody>' + rows + '</tbody>' +
      '</table></div>' +
    '</div>';
  setContent(html);
}

async function markFeeCollected(jobId) {
  const note = prompt('Note (e.g. Received via UPI, cash):', 'Cash collected') || '';
  const r = await api('POST', '/admin/platform-fees', {job_id: jobId, action: 'collect', note});
  if (r.success) { toast(r.message, 'success'); renderPlatformFees(); }
  else toast(r.message || 'Error', 'error');
}

// ─── WALLETS ──────────────────────────────────────────────
async function renderWallets() {
  setContent('<div class="loading"><div class="spinner"></div> Loading wallets...</div>');
  const r = await api('GET', '/admin/engineer-wallets');
  if (!r.success) { setContent('<div class="empty">Failed to load wallets</div>'); return; }
  const html = `
  <div class="card">
    <div class="card-header">
      <div class="card-title">💰 Engineer Wallets</div>
      <div style="font-size:12px;color:var(--muted)">Platform charge: 20% per job</div>
    </div>
    <div style="overflow-x:auto;-webkit-overflow-scrolling:touch">
    <table id="walletTable" style="min-width:580px">
      <thead><tr><th>Engineer</th><th>Phone</th><th>Balance</th><th>Total Earned</th><th>Withdrawn</th><th>Actions</th></tr></thead>
      <tbody>${r.data.map(e=>`<tr>
        <td><div style="display:flex;align-items:center;gap:8px"><div class="avatar" style="width:28px;height:28px;font-size:11px">${e.name[0]}</div>${e.name}</div></td>
        <td style="font-family:'Geist Mono',monospace;font-size:12px">${e.phone}</td>
        <td style="font-family:'Geist Mono',monospace;color:var(--green);font-weight:600">₹${Number(e.balance).toLocaleString()}</td>
        <td style="font-family:'Geist Mono',monospace">₹${Number(e.total_earned).toLocaleString()}</td>
        <td style="font-family:'Geist Mono',monospace;color:var(--muted)">₹${Number(e.total_withdrawn).toLocaleString()}</td>
        <td><div style="display:flex;gap:6px">
          <button class="btn btn-success btn-sm" onclick="openWalletAction(${e.id},'${e.name}','credit')">+ Credit</button>
          <button class="btn btn-ghost btn-sm" onclick="openWalletAction(${e.id},'${e.name}','payout')">Payout</button>
        </div></td>
      </tr>`).join('')}</tbody>
    </table>
    </div>
  </div>`;
  setContent(html);
}

async function openWalletAction(engineerId, name, action) {
  const label = action === 'credit' ? 'Credit' : 'Payout';
  const amount = prompt(label + ' amount for ' + name + ' (₹):');
  if (!amount || isNaN(amount) || parseFloat(amount) <= 0) return;
  const note = prompt('Note (optional):') || '';
  const r = await api('POST', '/admin/engineer-wallets', { action, engineer_id: engineerId, amount: parseFloat(amount), note });
  if (r.success) { toast(r.message, 'success'); renderWallets(); }
  else toast(r.message || 'Error', 'error');
}

// ─── Assign Modal ─────────────────────────────────────────
// ─── Job Detail / Edit Modal ──────────────────────────────
async function openJobModal(jobId) {
  // Show modal immediately with a loading state
  document.getElementById('jm_job_id').value = jobId;
  document.getElementById('jobModalNumber').textContent = 'Loading...';
  document.getElementById('jm_customer').textContent = '...';
  document.getElementById('jm_engineer').textContent  = '...';
  document.getElementById('jm_service').textContent   = '...';
  document.getElementById('jm_created').textContent   = '...';
  openModal('jobModal');

  // Fetch full job data
  const r = await api('GET', '/admin/jobs?id=' + jobId);
  const j = r.success && r.data && r.data.length ? r.data[0] : null;
  if (!j) { toast('Could not load job', 'error'); closeModal('jobModal'); return; }

  document.getElementById('jm_job_id').value = j.job_id || j.id;
  document.getElementById('jobModalNumber').textContent = '#' + j.job_number;
  document.getElementById('jm_customer').textContent = j.customer + (j.customer_phone ? ' · ' + j.customer_phone : '');
  document.getElementById('jm_engineer').textContent  = j.engineer + (j.engineer_phone ? ' · ' + j.engineer_phone : '');
  document.getElementById('jm_service').textContent   = j.service;
  document.getElementById('jm_created').textContent   = timeAgo(j.created_at);
  document.getElementById('jm_status').value   = j.status;
  document.getElementById('jm_priority').value = j.priority || 'normal';
  document.getElementById('jm_amount').value   = j.amount  || '';
  document.getElementById('jm_address').value  = j.address || '';
  document.getElementById('jm_notes').value    = j.notes   || '';

  const cancelBtn = document.getElementById('jm_cancel_btn');
  if (cancelBtn) {
    const terminal = j.status === 'completed' || j.status === 'cancelled';
    cancelBtn.style.display = terminal ? 'none' : 'inline-flex';
    cancelBtn.onclick = () => quickUpdateJob('cancelled');
  }
}

async function saveJob() {
  const jobId  = document.getElementById('jm_job_id').value;
  const status = document.getElementById('jm_status').value;
  const priority = document.getElementById('jm_priority').value;
  const amount   = document.getElementById('jm_amount').value;
  const address  = document.getElementById('jm_address').value.trim();
  const notes    = document.getElementById('jm_notes').value.trim();
  if (!jobId) { toast('No job selected', 'error'); return; }
  const r = await api('POST', '/admin/job-update', {
    job_id: jobId, status, priority,
    final_amount: amount ? parseFloat(amount) : undefined,
    address: address || undefined,
    notes
  });
  if (r.success) {
    toast('Job updated!', 'success');
    closeModal('jobModal');
    renderJobs();
  } else {
    toast(r.message || 'Update failed', 'error');
  }
}

async function quickUpdateJob(status) {
  const jobId = document.getElementById('jm_job_id').value;
  if (!jobId) return;
  if (status === 'cancelled' && !confirm('Cancel this job?')) return;
  const r = await api('POST', '/admin/job-update', { job_id: jobId, status });
  if (r.success) {
    toast('Job ' + status + '!', 'success');
    closeModal('jobModal');
    renderJobs();
  } else {
    toast(r.message || 'Failed', 'error');
  }
}

async function openAssign(jobId) {
  document.getElementById('assign_job_id').value = jobId;
  const r = await api('GET', '/admin/engineers');
  const sel = document.getElementById('assign_engineer_id');
  sel.innerHTML = '<option value="">-- Select Engineer --</option>';
  if (r.success) r.data.filter(e=>e.is_active&&e.status==='available').forEach(e => {
    sel.innerHTML += `<option value="${e.id}">${e.name} — ${e.phone} (${e.status})</option>`;
  });
  openModal('assignModal');
}

async function doAssign() {
  const jobId = document.getElementById('assign_job_id').value;
  const engId = document.getElementById('assign_engineer_id').value;
  if (!engId) { toast('Please select an engineer','error'); return; }
  const r = await api('POST', '/admin/assign-engineer', { job_id: jobId, engineer_id: engId });
  if (r.success) { toast('Engineer assigned!','success'); closeModal('assignModal'); renderJobs(); }
  else toast(r.message||'Error','error');
}

// ─── DISPUTES ─────────────────────────────────────────────
async function renderDisputes() {
  setContent('<div class="loading"><div class="spinner"></div> Loading disputes...</div>');
  // FIX: DB enum is open/investigating/resolved/closed — added 'investigating' to filters
  const statuses = ['open','investigating','resolved','closed'];
  const r = await api('GET', '/admin/disputes?status=open');
  if (!r.success) { setContent('<div class="empty">Failed to load disputes</div>'); return; }
  const html = `
  <div class="filters">
    ${statuses.map(s => `<button class="filter-chip ${s==='open'?'active':''}" onclick="loadDisputesByStatus('${s}',this)">${s.charAt(0).toUpperCase()+s.slice(1)}</button>`).join('')}
  </div>
  <div class="card" id="disputesCard">
    <div class="card-header"><div class="card-title">⚖️ Disputes</div></div>
    <div id="disputesList">${renderDisputeRows(r.data)}</div>
  </div>`;
  setContent(html);
}

function renderDisputeRows(data) {
  if (!data.length) return '<div class="empty"><div class="empty-icon">⚖️</div>No disputes found</div>';
  return `<div style="overflow-x:auto"><table>
    <thead><tr><th>Job #</th><th>Customer</th><th>Reason</th><th>Status</th><th>Raised</th><th>Actions</th></tr></thead>
    <tbody>${data.map(d => `<tr>
      <td><span style="font-family:'Geist Mono',monospace;font-size:12px;color:var(--accent)">${d.job_number}</span></td>
      <td><div>${d.customer_name}</div><div style="font-size:11px;color:var(--muted)">${d.customer_phone}</div></td>
      <td style="max-width:220px;font-size:12px;color:var(--muted)">${d.reason||'—'}</td>
      <td><span class="badge badge-${d.status==='open'?'pending':d.status==='investigating'?'assigned':d.status==='resolved'?'completed':'cancelled'}">${d.status}</span></td>
      <td style="font-size:12px;color:var(--muted)">${timeAgo(d.created_at)}</td>
      <td><div style="display:flex;gap:6px">
        ${(d.status==='open'||d.status==='investigating')?`
        <button class="btn btn-success btn-sm" onclick="resolveDispute(${d.id})">Resolve</button>
        <button class="btn btn-danger btn-sm" onclick="closeDispute(${d.id})">Close</button>`:'—'}
      </div></td>
    </tr>`).join('')}</tbody>
  </table></div>`;
}

async function loadDisputesByStatus(status, el) {
  document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
  if (el) el.classList.add('active');
  const r = await api('GET', `/admin/disputes?status=${status}`);
  if (r.success) document.getElementById('disputesList').innerHTML = renderDisputeRows(r.data);
}

async function resolveDispute(id) {
  const resolution = prompt('Enter resolution notes:');
  if (resolution === null) return;
  const r = await api('POST', '/admin/disputes', { action: 'resolve', dispute_id: id, resolution });
  if (r.success) { toast('Dispute resolved', 'success'); renderDisputes(); }
  else toast(r.message || 'Error', 'error');
}

async function closeDispute(id) {
  if (!confirm('Close this dispute?')) return;
  const r = await api('POST', '/admin/disputes', { action: 'close', dispute_id: id });
  if (r.success) { toast('Dispute closed', 'success'); renderDisputes(); }
  else toast(r.message || 'Error', 'error');
}

// ─── CONTRACTS ────────────────────────────────────────────
async function renderContracts() {
  setContent('<div class="loading"><div class="spinner"></div> Loading contracts...</div>');
  const r = await api('GET', '/admin/contracts?status=active');
  if (!r.success) { setContent('<div class="empty">Failed to load contracts: ' + (r.message||'error') + '</div>'); return; }
  const custR = await api('GET', '/admin/customers');
  const customers = custR.success ? custR.data : [];
  const custOpts = customers.map(c => `<option value="${c.id}">${c.name} — ${c.phone}</option>`).join('');
  const html = `
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;">
    <div class="card">
      <div class="card-header"><div class="card-title">➕ New Contract</div></div>
      <div style="padding:20px">
        <div class="form-group"><label>Customer</label>
          <select id="ctCustomer" style="width:100%"><option value="">-- Select Customer --</option>${custOpts}</select>
        </div>
        <div class="form-group"><label>Title</label><input id="ctTitle" placeholder="e.g. Annual AC Maintenance"></div>
        <div class="form-row">
          <div class="form-group" style="margin:0"><label>Start Date</label><input type="date" id="ctStart" value="${new Date().toISOString().slice(0,10)}"></div>
          <div class="form-group" style="margin:0"><label>End Date</label><input type="date" id="ctEnd" value="${new Date(Date.now()+365*86400000).toISOString().slice(0,10)}"></div>
        </div>
        <div class="form-row">
          <div class="form-group" style="margin:0"><label>Amount (₹)</label><input type="number" id="ctAmount" placeholder="0" min="0"></div>
          <div class="form-group" style="margin:0"><label>Total Visits</label><input type="number" id="ctVisits" placeholder="1" min="1" value="1"></div>
        </div>
        <button class="btn btn-primary" style="width:100%" onclick="saveContract()">📄 Create Contract</button>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
        <div class="card-title">📄 Active Contracts (${r.success?r.data.length:0})</div>
      </div>
      <div style="padding:8px 16px">
        ${!r.success||!r.data.length ? '<div class="empty"><div class="empty-icon">📄</div>No contracts found</div>' :
          r.data.map(c => `
          <div style="padding:12px 0;border-bottom:1px solid var(--border)">
            <div style="display:flex;justify-content:space-between;align-items:flex-start">
              <div>
                <div style="font-weight:600;font-size:13px">${c.title}</div>
                <div style="font-size:11px;color:var(--muted);margin-top:2px">${c.customer_name} · ${c.customer_phone}</div>
                <div style="font-size:11px;color:var(--hint);margin-top:4px">${c.start_date} → ${c.end_date}</div>
              </div>
              <div style="text-align:right">
                <div style="font-family:'Geist Mono',monospace;font-size:13px;color:var(--green)">₹${Number(c.amount).toLocaleString()}</div>
                <div style="font-size:11px;color:var(--muted);margin-top:2px">${c.visits_done}/${c.visits_total} visits</div>
              </div>
            </div>
            <div style="margin-top:6px">
              <span style="font-size:11px;font-family:'Geist Mono',monospace;color:var(--hint)">${c.contract_number}</span>
            </div>
          </div>`).join('')}
      </div>
    </div>
  </div>`;
  setContent(html);
}

async function saveContract() {
  const customerId = document.getElementById('ctCustomer').value;
  const title = document.getElementById('ctTitle').value.trim();
  const startDate = document.getElementById('ctStart').value;
  const endDate = document.getElementById('ctEnd').value;
  const amount = parseFloat(document.getElementById('ctAmount').value) || 0;
  const visits = parseInt(document.getElementById('ctVisits').value) || 1;
  if (!customerId || !title) { toast('Customer and title required', 'error'); return; }
  const r = await api('POST', '/admin/contracts', { action: 'create', customer_id: customerId, title, start_date: startDate, end_date: endDate, amount, visits_total: visits });
  if (r.success) { toast('Contract created: ' + r.data.contract_number, 'success'); renderContracts(); }
  else toast(r.message || 'Error', 'error');
}

// ─── PROMO CODES ──────────────────────────────────────────
async function renderPromo() {
  setContent('<div class="loading"><div class="spinner"></div> Loading promo codes...</div>');
  const r = await api('GET', '/admin/promo');
  const promos = r.success ? r.data : [];
  const html = `
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;">
    <div class="card">
      <div class="card-header"><div class="card-title">🎟️ Create Promo Code</div></div>
      <div style="padding:20px">
        <div class="form-row">
          <div class="form-group" style="margin:0"><label>Code</label><input id="pcCode" placeholder="SAVE20" style="text-transform:uppercase"></div>
          <div class="form-group" style="margin:0"><label>Type</label>
            <select id="pcType"><option value="flat">Flat (₹)</option><option value="percent">Percent (%)</option></select>
          </div>
        </div>
        <div class="form-row" style="margin-top:12px">
          <div class="form-group" style="margin:0"><label>Value</label><input type="number" id="pcValue" placeholder="0" min="0"></div>
          <div class="form-group" style="margin:0"><label>Min Order (₹)</label><input type="number" id="pcMinOrder" placeholder="0" min="0"></div>
        </div>
        <div class="form-row" style="margin-top:12px">
          <div class="form-group" style="margin:0"><label>Max Discount (₹)</label><input type="number" id="pcMaxDisc" placeholder="Optional"></div>
          <div class="form-group" style="margin:0"><label>Usage Limit</label><input type="number" id="pcLimit" placeholder="Unlimited"></div>
        </div>
        <div class="form-group"><label>Valid Till</label><input type="date" id="pcValidTill"></div>
        <button class="btn btn-primary" style="width:100%" onclick="createPromo()">🎟️ Create Code</button>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><div class="card-title">🎟️ All Promo Codes (${promos.length})</div></div>
      <div style="padding:8px 16px">
        ${!promos.length ? '<div class="empty"><div class="empty-icon">🎟️</div>No promo codes</div>' :
          promos.map(p => `
          <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--border)">
            <div style="flex:1">
              <div style="font-family:'Geist Mono',monospace;font-size:13px;font-weight:700;color:var(--accent)">${p.code}</div>
              <div style="font-size:11px;color:var(--muted);margin-top:2px">
                ${p.discount_type==='flat'?'₹'+(p.discount_value||0)+' off':''+(p.discount_value||0)+'% off'}
                ${(p.min_order_value>0)?' · Min ₹'+p.min_order_value:''}
                ${p.usage_limit?' · '+p.used_count+'/'+p.usage_limit+' used':' · '+p.used_count+' used'}
              </div>
              ${p.valid_until?`<div style="font-size:10px;color:var(--hint);margin-top:2px">Expires: ${p.valid_until}</div>`:''}
            </div>
            <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;
              background:${p.computed_status==='active'?'rgba(34,217,159,.15)':'rgba(255,77,106,.15)'};
              color:${p.computed_status==='active'?'var(--green)':'var(--red)'}">${p.computed_status}</span>
            <button onclick="togglePromo(${p.id})" style="background:transparent;border:1px solid var(--border);color:var(--muted);padding:3px 10px;border-radius:6px;cursor:pointer;font-size:11px">
              ${p.is_active?'Disable':'Enable'}
            </button>
          </div>`).join('')}
      </div>
    </div>
  </div>`;
  setContent(html);
}

async function createPromo() {
  const code = document.getElementById('pcCode').value.trim().toUpperCase();
  const type = document.getElementById('pcType').value;
  const value = parseFloat(document.getElementById('pcValue').value) || 0;
  const minOrder = parseFloat(document.getElementById('pcMinOrder').value) || 0;
  const maxDisc = document.getElementById('pcMaxDisc').value || null;
  const limit = document.getElementById('pcLimit').value || null;
  const validTill = document.getElementById('pcValidTill').value || null;
  if (!code || !value) { toast('Code and value required', 'error'); return; }
  const r = await api('POST', '/admin/promo', { action: 'create', code, type, value, min_order: minOrder, max_discount: maxDisc, usage_limit: limit, valid_till: validTill });
  if (r.success) { toast('Promo code created!', 'success'); renderPromo(); }
  else toast(r.message || 'Error', 'error');
}

async function togglePromo(id) {
  const r = await api('POST', '/admin/promo', { action: 'toggle', id });
  if (r.success) { toast('Status updated', 'success'); renderPromo(); }
  else toast(r.message || 'Error', 'error');
}

// ─── INVENTORY ────────────────────────────────────────────
async function renderInventory() {
  setContent('<div class="loading"><div class="spinner"></div> Loading inventory...</div>');
  const r = await api('GET', '/admin/inventory');
  const engR = await api('GET', '/admin/engineers');
  const engineers = engR.success ? engR.data.filter(e => e.is_active) : [];
  const engOpts = engineers.map(e => `<option value="${e.id}">${e.name} — ${e.phone}</option>`).join('');
  if (!r.success) { setContent('<div class="empty">Failed to load inventory</div>'); return; }
  const parts = r.data.parts || [];
  const lowCount = r.data.low_stock_alert_count || 0;
  const html = `
  ${lowCount > 0 ? `<div class="alert alert-error" style="display:flex;margin-bottom:16px">⚠️ ${lowCount} part(s) are running low on stock!</div>` : ''}
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px;">
    <div class="card">
      <div class="card-header"><div class="card-title">➕ Add / Restock Part</div><button class="btn btn-ghost btn-sm" onclick="openImportModal('inventory')">📥 Import CSV</button></div>
      <div style="padding:20px">
        <input type="hidden" id="invPartId" value="">
        <div class="form-group"><label>Part Name</label><input id="invName" placeholder="e.g. Capacitor 40MFD"></div>
        <div class="form-row">
          <div class="form-group" style="margin:0"><label>SKU</label><input id="invSku" placeholder="SKU-001"></div>
          <div class="form-group" style="margin:0"><label>Cost Price (₹)</label><input type="number" id="invPrice" placeholder="0" min="0" step="0.01"></div>
        </div>
        <div class="form-row" style="margin-top:12px">
          <div class="form-group" style="margin:0"><label>Stock Qty</label><input type="number" id="invQty" placeholder="0" min="0"></div>
          <div class="form-group" style="margin:0"><label>Min Stock Alert</label><input type="number" id="invAlert" placeholder="5" min="0" value="5"></div>
        </div>
        <div style="display:flex;gap:8px">
          <button class="btn btn-primary" style="flex:1" onclick="savePart()">💾 Save Part</button>
          <button class="btn btn-ghost" onclick="clearPartForm()">✕</button>
        </div>
        <div style="margin-top:16px;border-top:1px solid var(--border);padding-top:16px">
          <div style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:10px">Assign to Engineer</div>
          <div class="form-group"><label>Part</label>
            <select id="invAssignPart"><option value="">-- Select Part --</option>
              ${parts.map(p => `<option value="${p.id}">${p.name} (Stock: ${p.stock_qty})</option>`).join('')}
            </select>
          </div>
          <div class="form-row">
            <div class="form-group" style="margin:0"><label>Engineer</label>
              <select id="invAssignEng"><option value="">-- Select Engineer --</option>${engOpts}</select>
            </div>
            <div class="form-group" style="margin:0"><label>Qty</label><input type="number" id="invAssignQty" placeholder="1" min="1" value="1"></div>
          </div>
          <button class="btn btn-success" style="width:100%" onclick="assignPartToEngineer()">📦 Assign</button>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><div class="card-title">📦 Spare Parts (${parts.length})</div></div>
      <div style="overflow-x:auto">
        <table>
          <thead><tr><th>Part</th><th>SKU</th><th>Cost Price</th><th>Stock</th><th>In Field</th><th>Action</th></tr></thead>
          <tbody>
            ${!parts.length ? `<tr><td colspan="6" style="text-align:center;color:var(--hint);padding:24px">No parts added</td></tr>` :
              parts.map(p => `<tr>
                <td>
                  <div style="font-weight:500">${p.name}</div>
                  <div style="font-size:11px;color:var(--muted)">${p.sku||'—'}</div>
                </td>
                <td style="font-family:'Geist Mono',monospace;font-size:12px;color:var(--muted)">${p.sku||'—'}</td>
                <td style="font-family:'Geist Mono',monospace">₹${Number(p.unit_price||p.cost_price||0).toLocaleString()}</td>
                <td>
                  <span style="font-family:'Geist Mono',monospace;font-weight:600;color:${p.is_low_stock?'var(--red)':'var(--green)'}">${p.stock_qty}</span>
                  ${p.is_low_stock?'<span style="font-size:10px;color:var(--red);margin-left:4px">⚠ Low</span>':''}
                </td>
                <td style="font-family:'Geist Mono',monospace;color:var(--muted)">${p.total_in_field||0}</td>
                <td>
                  <button class="btn btn-ghost btn-sm" onclick="restockPart(${p.id},'${p.name.replace(/'/g,'\\\'')}')" title="Restock">+Stock</button>
                </td>
              </tr>`).join('')}
          </tbody>
        </table>
      </div>
    </div>
  </div>`;
  setContent(html);
}

function clearPartForm() {
  ['invPartId','invName','invSku','invPrice','invQty'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
  const alertEl = document.getElementById('invAlert'); if (alertEl) alertEl.value = '5';
}

async function savePart() {
  const name = document.getElementById('invName').value.trim();
  const sku = document.getElementById('invSku').value.trim();
  const price = parseFloat(document.getElementById('invPrice').value) || 0;
  const qty = parseInt(document.getElementById('invQty').value) || 0;
  const alert = parseInt(document.getElementById('invAlert').value) || 5;
  if (!name) { toast('Part name required', 'error'); return; }
  const r = await api('POST', '/admin/inventory', { action: 'create', name, sku, unit_price: price, stock_qty: qty, low_stock_alert: alert });
  if (r.success) { toast('Part saved!', 'success'); clearPartForm(); renderInventory(); }
  else toast(r.message || 'Error', 'error');
}

async function restockPart(partId, partName) {
  const qty = prompt(`Restock quantity for "${partName}":`);
  if (!qty || isNaN(qty) || parseInt(qty) <= 0) return;
  const r = await api('POST', '/admin/inventory', { action: 'restock', part_id: partId, qty: parseInt(qty) });
  if (r.success) { toast('Stock updated!', 'success'); renderInventory(); }
  else toast(r.message || 'Error', 'error');
}

async function assignPartToEngineer() {
  const partId = document.getElementById('invAssignPart').value;
  const engId = document.getElementById('invAssignEng').value;
  const qty = parseInt(document.getElementById('invAssignQty').value) || 1;
  if (!partId || !engId) { toast('Select part and engineer', 'error'); return; }
  const r = await api('POST', '/admin/inventory', { action: 'assign_to_engineer', part_id: partId, engineer_id: engId, qty });
  if (r.success) { toast(r.message || 'Assigned!', 'success'); renderInventory(); }
  else toast(r.message || 'Error', 'error');
}

// ─── QUOTATIONS ───────────────────────────────────────────
async function renderQuotations() {
  setContent('<div class="loading"><div class="spinner"></div> Loading quotations...</div>');
  const statuses = ['requested','sent','approved','rejected'];
  const r = await api('GET', '/admin/quotations?status=requested');
  const slots = await api('GET', '/schedule/slots');
  // /schedule/slots may return {date, slots:[]} or flat array — handle both
  const _slotsArr = slots.success ? (Array.isArray(slots.data) ? slots.data : (slots.data.slots || [])) : [];
  const slotOpts = _slotsArr.map(s => `<option value="${s.id}">${s.label}</option>`).join('');
  window._slotOpts = slotOpts;
  if (!r.success) { setContent('<div class="empty">Failed to load quotations</div>'); return; }
  setContent(`
  <div class="filters">
    ${statuses.map(s => `<button class="filter-chip ${s==='requested'?'active':''}" onclick="loadQuotationsByStatus('${s}',this)">${s.charAt(0).toUpperCase()+s.slice(1)}</button>`).join('')}
  </div>
  <div class="card" id="quotationsCard">
    <div class="card-header"><div class="card-title">📋 Quotation Requests</div></div>
    <div id="quotationsList">${renderQuotationRows(r.data)}</div>
  </div>`);
}

function renderQuotationRows(data) {
  if (!data.length) return '<div class="empty"><div class="empty-icon">📋</div>No quotations</div>';
  const slotOpts = window._slotOpts||'';
  return data.map(q => {
    let parts = []; try { parts = JSON.parse(q.parts_details||'[]'); } catch(e){}
    return `<div class="card" style="margin:12px">
      <div style="display:flex;justify-content:space-between;margin-bottom:8px">
        <div><div style="font-weight:700">#${q.job_number} — ${q.customer_name} · ${q.customer_phone}</div>
          <div style="font-size:12px;color:var(--muted)">${q.engineer_name} · ${new Date(q.created_at).toLocaleDateString('en-IN')}</div></div>
        <span class="badge badge-${q.status==='requested'?'pending':q.status==='sent'?'assigned':q.status==='approved'?'completed':'cancelled'}">${q.status}</span>
      </div>
      <div style="font-size:13px;background:var(--bg3);border-radius:8px;padding:10px;margin-bottom:10px">
        <b>Engineer Notes:</b> <span style="color:var(--muted)">${q.request_notes}</span>
        ${parts.length?'<div style="margin-top:6px"><b>Parts:</b> '+parts.map(p=>'• '+p.name+' ×'+p.qty+(p.est_price?' ~₹'+p.est_price:'')).join(' ')+'</div>':''}
      </div>
      ${q.status==='requested'?`
      <div style="background:var(--bg3);border-radius:10px;padding:12px;margin-bottom:12px;font-size:12px;color:var(--muted)">💡 First visit charge collected: <b style="color:var(--green)">₹${parseFloat(q.job_visit_charge||0).toLocaleString('en-IN',{minimumFractionDigits:2})}</b> — this will be deducted from customer's revisit payment automatically.</div>
      <div class="form-row">
        <div class="form-group" style="margin:0"><label>Parts Cost (₹)*</label><input type="number" id="qpc_${q.id}" placeholder="Cost of parts" min="0" step="0.01" oninput="updateQuotTotal('${q.id}')"></div>
        <div class="form-group" style="margin:0"><label>Installation / Labor (₹)*</label><input type="number" id="qic_${q.id}" placeholder="Labor charge" min="0" step="0.01" oninput="updateQuotTotal('${q.id}')"></div>
      </div>
      <div style="background:rgba(34,209,159,.1);border:1px solid rgba(34,209,159,.3);border-radius:8px;padding:10px;margin-bottom:10px;font-size:13px">
        <div style="display:flex;justify-content:space-between;margin-bottom:4px"><span style="color:var(--muted)">Parts + Installation</span><span id="qsubt_${q.id}">₹0.00</span></div>
        <div style="display:flex;justify-content:space-between;margin-bottom:4px"><span style="color:var(--muted)">Visit Paid (deducted)</span><span style="color:var(--green)">-₹${parseFloat(q.job_visit_charge||0).toLocaleString('en-IN',{minimumFractionDigits:2})}</span></div>
        <div style="display:flex;justify-content:space-between;font-weight:700;border-top:1px solid rgba(34,209,159,.3);padding-top:6px"><span>Customer Pays on Revisit</span><span id="qtotal_${q.id}" style="color:var(--green)">₹0.00</span></div>
      </div>
      <div class="form-row">
        <div class="form-group" style="margin:0"><label>Revisit Date*</label><input type="date" id="qd_${q.id}"></div>
        <div class="form-group" style="margin:0"><label>Time Slot</label><select id="qs_${q.id}"><option value="">Any time</option>${slotOpts}</select></div>
      </div>
      <div class="form-group" style="margin:0 0 10px"><label>Notes for Customer</label><input id="qn_${q.id}" placeholder="Warranty, parts model, special instructions..."></div>
      <button class="btn btn-primary btn-sm" onclick="sendQuotation('${q.id}','${parseFloat(q.job_visit_charge||0)}')">📤 Send Quotation to Customer</button>`
      :`<div style="font-size:13px;color:var(--muted)">Amount: ${q.quotation_amount?'₹'+Number(q.quotation_amount).toLocaleString('en-IN'):'—'} · Revisit: ${q.revisit_date||'—'} ${q.slot_label||''}</div>`}
    </div>`;
  }).join('');
}

function updateQuotTotal(qId) {
  var pc   = parseFloat(document.getElementById('qpc_'+qId)?.value)||0;
  var ic   = parseFloat(document.getElementById('qic_'+qId)?.value)||0;
  var visit = parseFloat(document.getElementById('qtotal_'+qId)?.dataset?.visit||0);
  var subtEl = document.getElementById('qsubt_'+qId);
  var totEl  = document.getElementById('qtotal_'+qId);
  if (subtEl) subtEl.textContent = '₹'+(pc+ic).toLocaleString('en-IN',{minimumFractionDigits:2});
  if (totEl)  totEl.textContent  = '₹'+Math.max(0,pc+ic-visit).toLocaleString('en-IN',{minimumFractionDigits:2});
}

async function sendQuotation(qId, visitCharge) {
  var pc    = parseFloat(document.getElementById('qpc_'+qId)?.value)||0;
  var ic    = parseFloat(document.getElementById('qic_'+qId)?.value)||0;
  var date  = document.getElementById('qd_'+qId)?.value;
  var slot  = document.getElementById('qs_'+qId)?.value;
  var notes = document.getElementById('qn_'+qId)?.value||'';
  if (pc<=0 && ic<=0){toast('Enter parts cost and/or installation charge','error');return;}
  if (!date){toast('Select revisit date','error');return;}
  const r = await api('POST','/admin/quotations',{action:'send',quotation_id:parseInt(qId),parts_cost:pc,installation_charge:ic,revisit_date:date,revisit_slot_id:slot||null,admin_notes:notes});
  if(r.success){
    toast('Quotation sent! Customer pays ₹'+(r.data?.customer_pays||0).toLocaleString('en-IN',{minimumFractionDigits:2}),'success');
    renderQuotations();
  }else toast(r.message||'Error','error');
}

async function loadQuotationsByStatus(status,el){
  document.querySelectorAll('.filter-chip').forEach(c=>c.classList.remove('active'));
  if(el)el.classList.add('active');
  const r=await api('GET','/admin/quotations?status='+status);
  if(r.success)document.getElementById('quotationsList').innerHTML=renderQuotationRows(r.data);
}

// ─── PICKUPS ──────────────────────────────────────────────
async function renderPickups() {
  setContent('<div class="loading"><div class="spinner"></div> Loading pickups...</div>');
  const statuses = ['requested','scheduled','picked','repaired','delivered'];
  const r = await api('GET', '/admin/pickups?status=requested');
  if (!r.success) { setContent('<div class="empty">Failed to load</div>'); return; }
  setContent(`
  <div class="filters">
    ${statuses.map(s=>`<button class="filter-chip ${s==='requested'?'active':''}" onclick="loadPickupsByStatus('${s}',this)">${s.charAt(0).toUpperCase()+s.slice(1)}</button>`).join('')}
  </div>
  <div class="card"><div class="card-header"><div class="card-title">🔧 Device Pickups</div></div>
  <div id="pickupsList">${renderPickupRows(r.data)}</div></div>`);
}

function renderPickupRows(data) {
  if (!data.length) return '<div class="empty"><div class="empty-icon">🔧</div>No pickup requests</div>';
  return data.map(p=>`<div class="card" style="margin:12px">
    <div style="display:flex;justify-content:space-between;margin-bottom:8px">
      <div><div style="font-weight:700">#${p.job_number} — ${p.customer_name}</div>
        <div style="font-size:12px;color:var(--muted)">Engineer: ${p.engineer_name}</div></div>
      <span class="badge badge-${p.status==='delivered'?'completed':'assigned'}">${p.status}</span>
    </div>
    <div style="font-size:13px;margin-bottom:6px"><b>Device:</b> ${p.device_desc}</div>
    <div style="font-size:13px;color:var(--muted);margin-bottom:6px">${p.pickup_notes||''}</div>
    <div style="font-size:12px;color:var(--muted);margin-bottom:10px">📍 ${p.pickup_address||p.job_address}</div>
    ${p.status!=='delivered'?`
    <div class="form-row" style="margin-bottom:8px">
      <div class="form-group" style="margin:0"><label>Status</label><select id="ps_${p.id}"><option value="scheduled">Scheduled</option><option value="picked">Picked Up</option><option value="repaired">Repaired</option><option value="delivered">Delivered</option></select></div>
      <div class="form-group" style="margin:0"><label>Repair Charge (₹)</label><input type="number" id="pc_${p.id}" placeholder="0" value="${p.repair_charge||''}"></div>
    </div>
    <div class="form-group" style="margin-bottom:8px"><label>Repair Notes</label><input id="pn_${p.id}" placeholder="Work done, parts replaced..." value="${p.repair_notes||''}"></div>
    <button class="btn btn-primary btn-sm" onclick="updatePickup(${p.id})">Update Status</button>`
    :`<div style="color:var(--green)">✅ Delivered · ₹${p.repair_charge||0}</div>`}
  </div>`).join('');
}

async function updatePickup(pId){
  const status=document.getElementById('ps_'+pId)?.value;
  const charge=parseFloat(document.getElementById('pc_'+pId)?.value)||0;
  const notes=document.getElementById('pn_'+pId)?.value||'';
  const r=await api('POST','/admin/quotations',{action:'update_pickup',pickup_id:pId,status,repair_charge:charge,repair_notes:notes});
  if(r.success){toast('Updated','success');renderPickups();}else toast(r.message||'Error','error');
}

async function loadPickupsByStatus(status,el){
  document.querySelectorAll('.filter-chip').forEach(c=>c.classList.remove('active'));
  if(el)el.classList.add('active');
  const r=await api('GET','/admin/pickups?status='+status);
  if(r.success)document.getElementById('pickupsList').innerHTML=renderPickupRows(r.data);
}

function setContent(html) { document.getElementById('mainContent').innerHTML = html; }

function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('.modal-overlay').forEach(m => {
  m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
});

function toast(msg, type='success') {
  const el = document.createElement('div');
  el.className = `toast-item toast-${type}`;
  el.textContent = (type==='success'?'✓ ':'⚠ ') + msg;
  document.getElementById('toast').appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

// ─── CSV IMPORT ───────────────────────────────────────────
const _importTemplates = {
  skills: {
    title: '📥 Import Skills',
    hint:  'One skill name per row.',
    headers: ['skill_name'],
    sample:  [['AC Repair'],['Plumbing'],['Electrical'],['Carpentry'],['Painting']],
  },
  services: {
    title: '📥 Import Services & Sub-Services',
    hint:  'Leave "parent_name" blank for a top-level service. Fill it to create a sub-service under that parent (auto-created if missing).',
    headers: ['name','base_price','visit_charge','duration_min','icon','is_active(1/0)','parent_name'],
    sample:  [
      ['AC Repair','500','200','60','❄️','1',''],
      ['Split AC Service','600','200','90','❄️','1','AC Repair'],
      ['Window AC Service','400','200','60','❄️','1','AC Repair'],
      ['Plumbing','0','150','60','🔧','1',''],
      ['Pipe Leakage Fix','300','150','45','🔧','1','Plumbing'],
    ],
  },
  inventory: {
    title: '📥 Import Spare Parts / Inventory',
    hint:  'If SKU or Name already exists the stock will be topped up. Sell Price defaults to cost + 20% if left blank.',
    headers: ['name','sku','cost_price','stock_qty','min_alert','sell_price'],
    sample:  [
      ['Capacitor 40MFD','CAP-40MFD','80','50','10','120'],
      ['Copper Wire 1.5mm','CW-1.5','15','200','20','25'],
      ['PVC Pipe 1 inch','PVC-1IN','45','100','15','70'],
      ['MCB 32A','MCB-32A','120','30','5','180'],
    ],
  },
};

const _importDownloads = {
  skills: [
    {
      icon: '🛠️', label: 'Skills Sample', filename: 'skills_sample.csv', rows: 10,
      headers: ['skill_name'],
      data: [
        ['AC Repair'],['Plumbing'],['Electrical'],['Carpentry'],['Painting'],
        ['CCTV Installation'],['Welding'],['Appliance Repair'],['Solar Installation'],['Pest Control'],
      ],
    },
  ],
  services: [
    {
      icon: '❄️', label: 'AC & Appliance Services', filename: 'services_ac_sample.csv', rows: 8,
      headers: ['name','base_price','visit_charge','duration_min','icon','is_active','parent_name'],
      data: [
        ['AC Repair','0','200','60','❄️','1',''],
        ['Split AC Service','600','200','90','❄️','1','AC Repair'],
        ['Window AC Service','400','200','60','❄️','1','AC Repair'],
        ['AC Deep Clean','800','200','120','❄️','1','AC Repair'],
        ['Refrigerator Repair','500','200','60','🧊','1',''],
        ['Washing Machine Repair','500','200','60','🫧','1',''],
        ['Geyser Repair','400','200','45','🚿','1',''],
        ['TV Repair','600','200','60','📺','1',''],
      ],
    },
    {
      icon: '🔧', label: 'Plumbing & Electrical', filename: 'services_home_sample.csv', rows: 10,
      headers: ['name','base_price','visit_charge','duration_min','icon','is_active','parent_name'],
      data: [
        ['Plumbing','0','150','60','🔧','1',''],
        ['Pipe Leakage Fix','300','150','45','🔧','1','Plumbing'],
        ['Tap & Faucet Repair','200','150','30','🔧','1','Plumbing'],
        ['Drainage Cleaning','400','150','60','🔧','1','Plumbing'],
        ['Electrical','0','150','60','⚡','1',''],
        ['Fan Installation','350','150','45','⚡','1','Electrical'],
        ['MCB / Switch Repair','250','150','30','⚡','1','Electrical'],
        ['Wiring Work','500','150','90','⚡','1','Electrical'],
        ['Carpentry','0','150','60','🪚','1',''],
        ['Door Repair','400','150','60','🪚','1','Carpentry'],
      ],
    },
    {
      icon: '💻', label: 'Laptop & IT Services', filename: 'services_it_sample.csv', rows: 7,
      headers: ['name','base_price','visit_charge','duration_min','icon','is_active','parent_name'],
      data: [
        ['Laptop Repair','0','200','60','💻','1',''],
        ['Screen Replacement','2500','200','90','💻','1','Laptop Repair'],
        ['Keyboard Replacement','800','200','60','💻','1','Laptop Repair'],
        ['Battery Replacement','1200','200','45','💻','1','Laptop Repair'],
        ['Virus Removal','500','200','60','💻','1','Laptop Repair'],
        ['Desktop Repair','0','200','60','🖥️','1',''],
        ['RAM Upgrade','600','200','30','🖥️','1','Desktop Repair'],
      ],
    },
  ],
  inventory: [
    {
      icon: '❄️', label: 'AC Parts', filename: 'inventory_ac_parts.csv', rows: 8,
      headers: ['name','sku','cost_price','stock_qty','min_alert','sell_price'],
      data: [
        ['Capacitor 40MFD','CAP-40MFD','80','50','10','120'],
        ['Capacitor 25MFD','CAP-25MFD','60','50','10','90'],
        ['Capacitor 15MFD','CAP-15MFD','45','50','10','70'],
        ['AC Gas R32 (1kg)','GAS-R32','800','10','3','1200'],
        ['AC Gas R22 (1kg)','GAS-R22','600','10','3','900'],
        ['AC Fan Motor 48W','MTR-48W','350','20','5','550'],
        ['AC PCB Board','PCB-AC','1200','10','3','1800'],
        ['AC Remote Universal','RMT-UNV','150','30','5','250'],
      ],
    },
    {
      icon: '⚡', label: 'Electrical Parts', filename: 'inventory_electrical.csv', rows: 8,
      headers: ['name','sku','cost_price','stock_qty','min_alert','sell_price'],
      data: [
        ['Copper Wire 1.5mm (m)','CW-1.5M','15','200','20','25'],
        ['Copper Wire 2.5mm (m)','CW-2.5M','22','200','20','35'],
        ['MCB 32A','MCB-32A','120','30','5','180'],
        ['MCB 16A','MCB-16A','90','30','5','140'],
        ['MCB 6A','MCB-6A','70','30','5','110'],
        ['Switch Board 6x4','SWB-6X4','80','50','10','130'],
        ['3-Pin Socket','SCK-3PN','45','100','15','75'],
        ['Electrical Tape Roll','ETAPE','20','100','20','35'],
      ],
    },
    {
      icon: '🔧', label: 'Plumbing Parts', filename: 'inventory_plumbing.csv', rows: 8,
      headers: ['name','sku','cost_price','stock_qty','min_alert','sell_price'],
      data: [
        ['PVC Pipe 1 inch (ft)','PVC-1IN','45','100','15','70'],
        ['PVC Pipe 0.5 inch (ft)','PVC-HIN','30','100','15','50'],
        ['Teflon Tape','TAPE-TFL','15','100','20','25'],
        ['Ball Valve 0.5 inch','VLV-0.5','80','30','5','130'],
        ['Elbow Joint 0.5 inch','ELB-0.5','12','100','20','20'],
        ['PVC Solvent Cement','SOL-PVC','60','30','5','100'],
        ['Washer Set Assorted','WSHR-SET','30','100','20','50'],
        ['Tap Cartridge','CART-TAP','120','20','5','200'],
      ],
    },
    {
      icon: '💻', label: 'Laptop & IT Parts', filename: 'inventory_it_parts.csv', rows: 8,
      headers: ['name','sku','cost_price','stock_qty','min_alert','sell_price'],
      data: [
        ['Thermal Paste 4g','PASTE-4G','80','30','5','130'],
        ['SATA Data Cable','SATA-CBL','60','20','5','100'],
        ['RAM DDR4 4GB','RAM-4GB','800','10','2','1200'],
        ['RAM DDR4 8GB','RAM-8GB','1500','10','2','2200'],
        ['Hard Disk 500GB','HDD-500','2000','5','2','2800'],
        ['SSD 256GB','SSD-256','2500','5','2','3500'],
        ['Laptop Battery 4400mAh','BAT-44','1200','10','3','1800'],
        ['Laptop Charger 65W','CHG-65W','600','10','3','950'],
      ],
    },
  ],
};

let _importType  = '';
let _importRows  = [];

function openImportModal(type) {
  _importType = type;
  _importRows = [];
  const tpl = _importTemplates[type];
  document.getElementById('importModalTitle').textContent = tpl.title;
  document.getElementById('importTemplateHint').textContent = tpl.hint;
  document.getElementById('importFileName').textContent = 'No file selected';
  document.getElementById('importPreview').style.display = 'none';
  document.getElementById('importStatus').style.display = 'none';
  document.getElementById('csvFileInput').value = '';

  // Render download buttons
  const btnContainer = document.getElementById('sampleDownloadBtns');
  const downloads = _importDownloads[type] || [];
  btnContainer.innerHTML = downloads.map((d,i) =>
    `<button class="btn btn-ghost btn-sm" style="justify-content:flex-start;gap:8px;text-align:left"
      onclick="downloadSample(${i})">
      <span style="font-size:16px">${d.icon}</span>
      <span><b>${d.label}</b><span style="color:var(--muted);font-size:11px;margin-left:6px">${d.rows} rows sample</span></span>
      <span style="margin-left:auto;font-size:11px;color:var(--accent)">⬇ Download</span>
    </button>`
  ).join('');

  openModal('importModal');
}

function downloadSample(idx) {
  const d    = _importDownloads[_importType][idx];
  const rows = [d.headers, ...d.data];
  const csv  = rows.map(r => r.map(c => '"' + String(c).replace(/"/g, '""') + '"').join(',')).join('\n');
  const blob = new Blob(['﻿' + csv], {type: 'text/csv;charset=utf-8'});
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href = url; a.download = d.filename; a.click();
  URL.revokeObjectURL(url);
}

function handleFileSelect(input) {
  if (input.files[0]) readCsvFile(input.files[0]);
}

function handleDrop(e) {
  e.preventDefault();
  document.getElementById('dropZone').style.borderColor = 'var(--border)';
  const file = e.dataTransfer.files[0];
  if (file) readCsvFile(file);
}

function readCsvFile(file) {
  document.getElementById('importFileName').textContent = file.name;
  const reader = new FileReader();
  reader.onload = function(e) {
    const lines = e.target.result.split(/\r?\n/).filter(l => l.trim());
    // Detect if first row is header (non-numeric first cell)
    const parsed = lines.map(l => parseCsvLine(l));
    const firstCell = (parsed[0]?.[0]||'').toLowerCase();
    const tplHeaders = _importTemplates[_importType].headers;
    const hasHeader = isNaN(firstCell) && (firstCell === tplHeaders[0] || firstCell === 'name' || firstCell === 'skill_name');
    _importRows = hasHeader ? parsed.slice(1) : parsed;
    showPreview(tplHeaders, _importRows.slice(0, 5));
  };
  reader.readAsText(file);
}

function parseCsvLine(line) {
  const result = []; let cur = ''; let inQ = false;
  for (let i = 0; i < line.length; i++) {
    const c = line[i];
    if (c === '"') { inQ = !inQ; }
    else if (c === ',' && !inQ) { result.push(cur.trim()); cur = ''; }
    else { cur += c; }
  }
  result.push(cur.trim());
  return result;
}

function showPreview(headers, rows) {
  document.getElementById('importPreview').style.display = '';
  document.getElementById('previewCount').textContent = '(' + _importRows.length + ')';
  document.getElementById('previewHead').innerHTML =
    '<tr>' + headers.map(h => `<th style="padding:6px 10px;background:var(--bg3);font-size:11px">${h}</th>`).join('') + '</tr>';
  document.getElementById('previewBody').innerHTML = rows.map(r =>
    '<tr>' + headers.map((_,i) =>
      `<td style="padding:5px 10px;border-bottom:1px solid var(--border);font-size:12px">${r[i]||''}</td>`
    ).join('') + '</tr>'
  ).join('');
}

async function runImport() {
  if (!_importRows.length) { toast('Select a CSV file first', 'error'); return; }
  const btn = document.getElementById('importBtn');
  btn.disabled = true; btn.textContent = 'Importing...';
  const statusEl = document.getElementById('importStatus');

  const res = await api('POST', '/admin/import', {type: _importType, rows: _importRows});

  btn.disabled = false; btn.textContent = '📥 Import Now';
  statusEl.style.display = '';

  if (res.success) {
    statusEl.style.background = 'rgba(34,209,159,.12)';
    statusEl.style.color      = 'var(--green)';
    statusEl.style.border     = '1px solid rgba(34,209,159,.3)';
    statusEl.innerHTML = `✅ <b>${res.data.imported}</b> imported, <b>${res.data.skipped}</b> skipped`
      + (res.data.errors?.length ? `<br><span style="color:var(--red)">${res.data.errors.join('<br>')}</span>` : '');
    // Refresh the relevant page
    const refreshFns = {services: renderServices, inventory: renderInventory};
    if (refreshFns[_importType]) setTimeout(refreshFns[_importType], 800);
  } else {
    statusEl.style.background = 'rgba(239,68,68,.12)';
    statusEl.style.color      = 'var(--red)';
    statusEl.style.border     = '1px solid rgba(239,68,68,.3)';
    statusEl.textContent = '❌ ' + (res.message || 'Import failed');
  }
}

function timeAgo(dateStr) {
  const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
  if (diff < 60) return diff + 's ago';
  if (diff < 3600) return Math.floor(diff/60) + 'm ago';
  if (diff < 86400) return Math.floor(diff/3600) + 'h ago';
  return Math.floor(diff/86400) + 'd ago';
}

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ════════════════════════════════════════════════════
// FEATURE: Revenue / Data Export (CSV)
// ════════════════════════════════════════════════════
function exportCSV(type) {
  const from = document.getElementById('rptFrom')?.value || new Date().toISOString().slice(0,8) + '01';
  const to   = document.getElementById('rptTo')?.value   || new Date().toISOString().slice(0,10);
  const url  = `${API}/admin/export?type=${type}&from=${from}&to=${to}&format=csv`;

  // Use fetch with auth header, then trigger download
  fetch(url, { headers: { 'Authorization': 'Bearer ' + authToken } })
    .then(r => {
      if (!r.ok) return r.json().then(d => { throw new Error(d.message || 'Export failed'); });
      return r.blob();
    })
    .then(blob => {
      const a   = document.createElement('a');
      a.href    = URL.createObjectURL(blob);
      a.download = `fixgrid_${type}_${from}_to_${to}.csv`;
      document.body.appendChild(a);
      a.click();
      setTimeout(() => { URL.revokeObjectURL(a.href); a.remove(); }, 1000);
      toast('✅ ' + type.charAt(0).toUpperCase() + type.slice(1) + ' CSV downloaded!', 'success');
    })
    .catch(e => toast(e.message || 'Export failed', 'error'));
}

// ════════════════════════════════════════════════════
// FEATURE: Admin Chat (view job chat history)
// ════════════════════════════════════════════════════
async function viewJobChat(jobId, jobNumber) {
  const modal = document.createElement('div');
  modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px';
  modal.innerHTML = `
    <div style="background:var(--bg2);border-radius:16px;width:100%;max-width:520px;max-height:80vh;display:flex;flex-direction:column;overflow:hidden">
      <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
        <div style="font-weight:700;font-size:16px">💬 Chat — Job #${jobNumber}</div>
        <button onclick="this.closest('[style*=fixed]').remove()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--text)">✕</button>
      </div>
      <div id="adminChatBody" style="flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:8px">
        <div style="text-align:center;color:var(--muted);padding:20px">Loading...</div>
      </div>
    </div>`;
  document.body.appendChild(modal);

  const res = await api('GET', `/chat/messages?job_id=${jobId}`);
  const msgs = Array.isArray(res.data) ? res.data : (res.data?.messages || []);
  const body = document.getElementById('adminChatBody');
  if (!body) return;
  if (!msgs.length) { body.innerHTML = '<div style="text-align:center;color:var(--muted);padding:20px">No messages in this job</div>'; return; }
  body.innerHTML = msgs.map(m => {
    const isEng = m.sender_type === 'engineer';
    return `<div style="display:flex;justify-content:${isEng ? 'flex-end' : 'flex-start'}">
      <div style="max-width:75%;background:${isEng ? '#DBEAFE' : '#F0FDF4'};border-radius:12px;padding:10px 14px;font-size:13px">
        <div style="font-size:10px;font-weight:700;color:var(--muted);margin-bottom:4px">${m.sender_type.toUpperCase()}</div>
        ${m.message ? `<div>${m.message}</div>` : ''}
        <div style="font-size:10px;color:var(--muted);margin-top:4px">${new Date(m.created_at).toLocaleString('en-IN')}</div>
      </div>
    </div>`;
  }).join('');
  body.scrollTop = body.scrollHeight;
}

// ════════════════════════════════════════════════════
// FEATURE: Broadcast scheduled job to engineers now
// ════════════════════════════════════════════════════
async function broadcastScheduledJob(jobId, jobNumber) {
  if (!confirm('Broadcast job #' + jobNumber + ' to nearby engineers now?')) return;
  const r = await api('POST', '/admin/broadcast-job', {job_id: jobId});
  if (r.success) {
    toast('✅ ' + (r.data.engineers_notified||0) + ' engineer(s) notified for job #' + jobNumber, 'success');
    renderJobs();
  } else {
    toast(r.message || 'Broadcast failed', 'error');
  }
}

// ════════════════════════════════════════════════════
// FEATURE: Notify zone engineers about a pending job
// ════════════════════════════════════════════════════
async function notifyZoneEngineers(jobId, jobNumber, btn) {
  const orig = btn.textContent;
  btn.disabled = true;
  btn.textContent = '…';

  const r = await api('POST', '/admin/zone-notify', { job_id: jobId });

  btn.disabled = false;
  btn.textContent = orig;

  if (r.success) {
    const notified = r.data?.notified || 0;
    const zones    = (r.data?.zones   || []).join(', ');
    if (notified === 0) {
      toast('⚠ No available zone engineers found near job #' + jobNumber
        + (r.data?.zones_matched ? ' — zones matched: ' + r.data.zones_matched : ''), 'warning');
    } else {
      toast('✅ ' + notified + ' engineer(s) notified in zone(s): ' + (zones || '—')
        + ' for job #' + jobNumber, 'success');
    }
  } else {
    toast('⚠ ' + (r.message || 'Zone notify failed'), 'error');
  }
}

// ════════════════════════════════════════════════════
// KYC REVIEW — Engineer document verification
// ════════════════════════════════════════════════════
async function renderKycReview() {
  setContent('<div class="loading"><div class="spinner"></div> Loading KYC applications...</div>');
  const statuses = ['submitted','approved','rejected'];
  let html = `
  <div class="filters">
    ${statuses.map(s=>`<button class="filter-chip ${s==='submitted'?'active':''}" onclick="loadKyc('${s}',this)">${s.charAt(0).toUpperCase()+s.slice(1)}</button>`).join('')}
  </div>
  <div id="kycContent"></div>`;
  setContent(html);
  loadKyc('submitted');
}

async function loadKyc(status, btn) {
  if (btn) { document.querySelectorAll('.filter-chip').forEach(c=>c.classList.remove('active')); btn.classList.add('active'); }
  const r = await api('GET', `/admin/kyc-review?status=${status}`);
  if (!r.success) { document.getElementById('kycContent').innerHTML = '<div class="empty">Failed to load</div>'; return; }
  if (!r.data.length) { document.getElementById('kycContent').innerHTML = `<div class="empty">No ${status} applications</div>`; return; }
  document.getElementById('kycContent').innerHTML = r.data.map(e => `
    <div class="card" style="margin-bottom:20px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px">
        <div>
          <div style="font-size:16px;font-weight:700">${e.name}</div>
          <div style="font-size:13px;color:var(--muted)">${e.email} · ${e.phone}</div>
          <div style="font-size:12px;color:var(--muted)">${e.city || '—'} · Applied ${timeAgo(e.created_at)}</div>
        </div>
        <span class="badge badge-${e.kyc_status==='approved'?'green':e.kyc_status==='rejected'?'red':'amber'}">${e.kyc_status.toUpperCase()}</span>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:16px">
        <!-- Aadhaar -->
        <div style="background:var(--bg3);border-radius:10px;padding:12px;text-align:center">
          <div style="font-size:20px;margin-bottom:6px">${e.kyc_aadhaar_number?'✅':'❌'}</div>
          <div style="font-size:12px;font-weight:600;margin-bottom:4px">Aadhaar</div>
          ${e.kyc_aadhaar_number?`<div style="font-size:11px;color:var(--muted);margin-bottom:6px">${e.kyc_aadhaar_number}</div>`:'<div style="font-size:11px;color:var(--red)">Not provided</div>'}
          ${e.kyc_aadhaar_doc_url?`
            <a href="${e.kyc_aadhaar_doc_url}" target="_blank">
              <img src="${e.kyc_aadhaar_doc_url}" style="width:100%;max-height:70px;object-fit:cover;border-radius:6px;border:1px solid var(--border)" onerror="this.style.display='none'">
              <div style="font-size:10px;color:var(--accent);margin-top:4px">📄 View Document</div>
            </a>`:'<div style="font-size:11px;color:var(--muted)">No doc uploaded</div>'}
        </div>
        <!-- PAN -->
        <div style="background:var(--bg3);border-radius:10px;padding:12px;text-align:center">
          <div style="font-size:20px;margin-bottom:6px">${e.kyc_pan_number?'✅':'❌'}</div>
          <div style="font-size:12px;font-weight:600;margin-bottom:4px">PAN Card</div>
          ${e.kyc_pan_number?`<div style="font-size:11px;color:var(--muted);font-weight:600;letter-spacing:2px;margin-bottom:6px">${e.kyc_pan_number}</div>`:'<div style="font-size:11px;color:var(--red)">Not provided</div>'}
          ${e.kyc_pan_doc_url?`
            <a href="${e.kyc_pan_doc_url}" target="_blank">
              <img src="${e.kyc_pan_doc_url}" style="width:100%;max-height:70px;object-fit:cover;border-radius:6px;border:1px solid var(--border)" onerror="this.style.display='none'">
              <div style="font-size:10px;color:var(--accent);margin-top:4px">📄 View Document</div>
            </a>`:'<div style="font-size:11px;color:var(--muted)">No doc uploaded</div>'}
        </div>
        <!-- Selfie -->
        <div style="background:var(--bg3);border-radius:10px;padding:12px;text-align:center">
          <div style="font-size:20px;margin-bottom:6px">${e.kyc_selfie_url?'✅':'❌'}</div>
          <div style="font-size:12px;font-weight:600;margin-bottom:6px">Selfie</div>
          ${e.kyc_selfie_url?`
            <a href="${e.kyc_selfie_url}" target="_blank">
              <img src="${e.kyc_selfie_url}" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:2px solid var(--border);margin-bottom:4px">
              <div style="font-size:10px;color:var(--accent)">View Full</div>
            </a>`:'<div style="font-size:11px;color:var(--muted)">Not uploaded</div>'}
        </div>
      </div>
      ${e.kyc_status==='submitted'?`
      <div style="display:flex;gap:10px">
        <button class="btn btn-success btn-sm" style="flex:1" onclick="kycAction(${e.id},'approve','${e.name}')">✓ Approve</button>
        <button class="btn btn-danger btn-sm" style="flex:1" onclick="kycAction(${e.id},'reject','${e.name}')">✗ Reject</button>
      </div>`:`<div style="font-size:12px;color:var(--muted)">Reviewed ${timeAgo(e.kyc_reviewed_at)}${e.kyc_rejection_reason?' · Reason: '+e.kyc_rejection_reason:''}</div>`}
    </div>`).join('');
}

async function kycAction(engId, action, name) {
  var reason = '';
  if (action === 'reject') {
    reason = prompt(`Rejection reason for ${name}:`);
    if (!reason) return;
  }
  if (!confirm(`${action === 'approve' ? 'Approve' : 'Reject'} engineer ${name}?`)) return;
  const r = await api('POST', '/admin/kyc-review', {engineer_id: engId, action, reason});
  if (r.success) { toast(r.message || 'Done', 'success'); loadKyc(action === 'approve' ? 'approved' : 'rejected'); }
  else toast(r.message || 'Error', 'error');
}


// Swipe to close sidebar on mobile
(function(){
  var touchStartX = 0;
  document.addEventListener('touchstart', function(e){
    touchStartX = e.touches[0].clientX;
  }, {passive: true});
  document.addEventListener('touchend', function(e){
    var dx = e.changedTouches[0].clientX - touchStartX;
    var sb = document.getElementById('sidebar');
    if (sb && sb.classList.contains('open') && dx < -60) {
      window.closeSidebar();
    }
  }, {passive: true});
})();


</script>

<!-- ═══════════════════════════════════════════════════════════
     IMPORT CSV MODAL
════════════════════════════════════════════════════════════ -->
<div id="importModal" class="modal-overlay">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <div class="modal-title" id="importModalTitle">📥 Import CSV</div>
      <button class="modal-close" onclick="closeModal('importModal')">✕</button>
    </div>
    <div style="padding:20px">

      <!-- Template download -->
      <div style="background:var(--bg3);border-radius:10px;padding:14px;margin-bottom:16px">
        <div style="font-weight:600;font-size:13px;margin-bottom:10px">📋 Download Sample Template</div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:12px" id="importTemplateHint"></div>
        <div style="display:flex;flex-direction:column;gap:8px" id="sampleDownloadBtns">
          <!-- Buttons injected by JS based on type -->
        </div>
      </div>

      <!-- Upload area -->
      <div style="border:2px dashed var(--border);border-radius:12px;padding:24px;text-align:center;cursor:pointer;margin-bottom:16px"
           onclick="document.getElementById('csvFileInput').click()"
           id="dropZone"
           ondragover="event.preventDefault();this.style.borderColor='var(--accent)'"
           ondragleave="this.style.borderColor='var(--border)'"
           ondrop="handleDrop(event)">
        <div style="font-size:32px;margin-bottom:8px">📂</div>
        <div style="font-weight:600;font-size:14px">Click to browse or drag & drop CSV</div>
        <div style="font-size:12px;color:var(--muted);margin-top:4px" id="importFileName">No file selected</div>
        <input type="file" id="csvFileInput" accept=".csv,.txt" style="display:none" onchange="handleFileSelect(this)">
      </div>

      <!-- Preview -->
      <div id="importPreview" style="display:none">
        <div style="font-weight:600;font-size:13px;margin-bottom:8px">Preview <span id="previewCount"></span> rows</div>
        <div style="overflow-x:auto;max-height:180px;border:1px solid var(--border);border-radius:8px">
          <table style="width:100%;font-size:12px">
            <thead id="previewHead"></thead>
            <tbody id="previewBody"></tbody>
          </table>
        </div>
      </div>

      <!-- Status -->
      <div id="importStatus" style="display:none;padding:12px;border-radius:8px;margin-top:12px;font-size:13px"></div>

      <div style="display:flex;gap:10px;margin-top:16px">
        <button class="btn btn-primary" style="flex:1" onclick="runImport()" id="importBtn">📥 Import Now</button>
        <button class="btn btn-ghost" onclick="closeModal('importModal')">Cancel</button>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════
     THEME PICKER
═══════════════════════════════════════════════ -->
<button id="themeToggleBtn" onclick="toggleThemePanel()" title="Change Theme">🎨</button>

<div id="themePanel">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <h4 style="margin:0">🎨 Choose Theme</h4>
    <div style="display:flex;gap:6px">
      <button onclick="setThemeGroup('dark')" id="tg-dark" class="tg-btn tg-active">Dark</button>
      <button onclick="setThemeGroup('light')" id="tg-light" class="tg-btn">Light</button>
      <button onclick="setThemeGroup('colour')" id="tg-colour" class="tg-btn">Colour</button>
    </div>
  </div>

  <!-- Dark themes -->
  <div class="theme-group" id="group-dark">
    <div class="theme-option active" id="opt-dark-nebula" onclick="applyTheme('dark-nebula')">
      <div class="theme-swatch" style="background:transparent;gap:3px;padding:2px;flex-wrap:wrap;width:44px;height:44px"><span style="width:10px;height:10px;border-radius:50%;background:#4F7CFF;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#22D99F;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#F59E0B;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#A78BFA;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#22D3EE;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#FF6B9D;flex-shrink:0"></span></div>
      <div class="theme-info"><div class="theme-name">Dark Nebula</div><div class="theme-desc">Blue-violet · Default</div></div>
      <span class="theme-check">✓</span>
    </div>
    <div class="theme-option" id="opt-forest-pro" onclick="applyTheme('forest-pro')">
      <div class="theme-swatch" style="background:transparent;gap:3px;padding:2px;flex-wrap:wrap;width:44px;height:44px"><span style="width:10px;height:10px;border-radius:50%;background:#4ADE80;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#22D3EE;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#FCD34D;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#C084FC;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#F87171;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#FB923C;flex-shrink:0"></span></div>
      <div class="theme-info"><div class="theme-name">Forest Pro</div><div class="theme-desc">Deep green · Nature</div></div>
      <span class="theme-check">✓</span>
    </div>
    <div class="theme-option" id="opt-crimson-dark" onclick="applyTheme('crimson-dark')">
      <div class="theme-swatch" style="background:transparent;gap:3px;padding:2px;flex-wrap:wrap;width:44px;height:44px"><span style="width:10px;height:10px;border-radius:50%;background:#F87171;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#6EE7B7;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#FCD34D;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#C084FC;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#67E8F9;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#FB923C;flex-shrink:0"></span></div>
      <div class="theme-info"><div class="theme-name">Crimson Dark</div><div class="theme-desc">Red-orange · Bold</div></div>
      <span class="theme-check">✓</span>
    </div>
    <div class="theme-option" id="opt-midnight-purple" onclick="applyTheme('midnight-purple')">
      <div class="theme-swatch" style="background:transparent;gap:3px;padding:2px;flex-wrap:wrap;width:44px;height:44px"><span style="width:10px;height:10px;border-radius:50%;background:#A78BFA;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#34D399;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#FBBF24;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#F87171;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#67E8F9;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#FB7185;flex-shrink:0"></span></div>
      <div class="theme-info"><div class="theme-name">Midnight Purple</div><div class="theme-desc">Deep violet · Mystical</div></div>
      <span class="theme-check">✓</span>
    </div>
    <div class="theme-option" id="opt-sunset-orange" onclick="applyTheme('sunset-orange')">
      <div class="theme-swatch" style="background:transparent;gap:3px;padding:2px;flex-wrap:wrap;width:44px;height:44px"><span style="width:10px;height:10px;border-radius:50%;background:#FB923C;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#4ADE80;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#F59E0B;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#A78BFA;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#67E8F9;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#F87171;flex-shrink:0"></span></div>
      <div class="theme-info"><div class="theme-name">Sunset Orange</div><div class="theme-desc">Warm amber · Energetic</div></div>
      <span class="theme-check">✓</span>
    </div>
    <div class="theme-option" id="opt-rose-gold" onclick="applyTheme('rose-gold')">
      <div class="theme-swatch" style="background:transparent;gap:3px;padding:2px;flex-wrap:wrap;width:44px;height:44px"><span style="width:10px;height:10px;border-radius:50%;background:#FF6B9D;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#4ADEAA;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#FBBF24;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#A78BFA;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#60EFDF;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#FB923C;flex-shrink:0"></span></div>
      <div class="theme-info"><div class="theme-name">Rose Gold</div><div class="theme-desc">Pink-red · Elegant</div></div>
      <span class="theme-check">✓</span>
    </div>
  </div>

  <!-- Light themes -->
  <div class="theme-group" id="group-light" style="display:none">
    <div class="theme-option" id="opt-arctic-light" onclick="applyTheme('arctic-light')">
      <div class="theme-swatch" style="background:transparent;gap:3px;padding:2px;flex-wrap:wrap;width:44px;height:44px"><span style="width:10px;height:10px;border-radius:50%;background:#1E6BF1;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#16A34A;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#D97706;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#7C3AED;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#0891B2;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#DB2777;flex-shrink:0"></span></div>
      <div class="theme-info"><div class="theme-name">Arctic Light</div><div class="theme-desc">Clean white · Professional</div></div>
      <span class="theme-check">✓</span>
    </div>
    <div class="theme-option" id="opt-slate-pro" onclick="applyTheme('slate-pro')">
      <div class="theme-swatch" style="background:transparent;gap:3px;padding:2px;flex-wrap:wrap;width:44px;height:44px"><span style="width:10px;height:10px;border-radius:50%;background:#3B82F6;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#10B981;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#F59E0B;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#8B5CF6;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#06B6D4;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#F43F5E;flex-shrink:0"></span></div>
      <div class="theme-info"><div class="theme-name">Slate Pro</div><div class="theme-desc">Split dark/light · Enterprise</div></div>
      <span class="theme-check">✓</span>
    </div>
    <div class="theme-option" id="opt-ocean-breeze" onclick="applyTheme('ocean-breeze')">
      <div class="theme-swatch" style="background:transparent;gap:3px;padding:2px;flex-wrap:wrap;width:44px;height:44px"><span style="width:10px;height:10px;border-radius:50%;background:#0D9488;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#059669;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#D97706;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#7C3AED;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#0284C7;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#DB2777;flex-shrink:0"></span></div>
      <div class="theme-info"><div class="theme-name">Ocean Breeze</div><div class="theme-desc">Teal-white · Fresh</div></div>
      <span class="theme-check">✓</span>
    </div>
    <div class="theme-option" id="opt-mocha" onclick="applyTheme('mocha')">
      <div class="theme-swatch" style="background:transparent;gap:3px;padding:2px;flex-wrap:wrap;width:44px;height:44px"><span style="width:10px;height:10px;border-radius:50%;background:#92400E;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#166534;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#B45309;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#7C3AED;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#0E7490;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#C2410C;flex-shrink:0"></span></div>
      <div class="theme-info"><div class="theme-name">Mocha</div><div class="theme-desc">Warm cream · Cozy</div></div>
      <span class="theme-check">✓</span>
    </div>
  </div>

  <!-- Colour / neon themes -->
  <div class="theme-group" id="group-colour" style="display:none">
    <div class="theme-option" id="opt-cyberpunk" onclick="applyTheme('cyberpunk')">
      <div class="theme-swatch" style="background:transparent;gap:3px;padding:2px;flex-wrap:wrap;width:44px;height:44px"><span style="width:10px;height:10px;border-radius:50%;background:#00FFF5;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#00FF7F;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#FFD700;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#FF00AA;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#FF3366;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#7B68EE;flex-shrink:0"></span></div>
      <div class="theme-info"><div class="theme-name">Cyberpunk</div><div class="theme-desc">Cyan-magenta · Electric</div></div>
      <span class="theme-check">✓</span>
    </div>
    <div class="theme-option" id="opt-neon-tokyo" onclick="applyTheme('neon-tokyo')">
      <div class="theme-swatch" style="background:transparent;gap:3px;padding:2px;flex-wrap:wrap;width:44px;height:44px"><span style="width:10px;height:10px;border-radius:50%;background:#E040FB;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#00E676;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#FFD740;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#00E5FF;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#FF1744;flex-shrink:0"></span><span style="width:10px;height:10px;border-radius:50%;background:#FF6D00;flex-shrink:0"></span></div>
      <div class="theme-info"><div class="theme-name">Neon Tokyo</div><div class="theme-desc">Purple-cyan · Vivid</div></div>
      <span class="theme-check">✓</span>
    </div>
  </div>
</div>

<script>
/* ─── 12-Theme System ──────────────────────────── */
(function(){
  var ALL_THEMES = [
    'dark-nebula','forest-pro','crimson-dark','midnight-purple','sunset-orange','rose-gold',
    'arctic-light','slate-pro','ocean-breeze','mocha',
    'cyberpunk','neon-tokyo'
  ];
  var DARK_THEMES   = ['dark-nebula','forest-pro','crimson-dark','midnight-purple','sunset-orange','rose-gold'];
  var LIGHT_THEMES  = ['arctic-light','slate-pro','ocean-breeze','mocha'];
  var COLOUR_THEMES = ['cyberpunk','neon-tokyo'];

  var current = localStorage.getItem('fsm_theme') || 'dark-nebula';

  function getGroup(name) {
    if (DARK_THEMES.includes(name))   return 'dark';
    if (LIGHT_THEMES.includes(name))  return 'light';
    return 'colour';
  }

  function applyTheme(name) {
    document.documentElement.setAttribute('data-theme', name);
    current = name;
    localStorage.setItem('fsm_theme', name);
    ALL_THEMES.forEach(function(t) {
      var el = document.getElementById('opt-' + t);
      if (el) el.classList.toggle('active', t === name);
    });
    // Switch to correct group tab
    setThemeGroup(getGroup(name), true);
    closeThemePanel();
  }

  function setThemeGroup(group, silent) {
    ['dark','light','colour'].forEach(function(g) {
      var btn   = document.getElementById('tg-' + g);
      var panel = document.getElementById('group-' + g);
      var active = g === group;
      if (btn)   { btn.classList.toggle('tg-active', active); }
      if (panel) { panel.style.display = active ? '' : 'none'; }
    });
    if (!silent) {} // just UI switch, no theme change
  }

  function toggleThemePanel() {
    var panel = document.getElementById('themePanel');
    var open = panel.classList.toggle('open');
    if (open) setThemeGroup(getGroup(current), true);
  }
  function closeThemePanel() {
    document.getElementById('themePanel').classList.remove('open');
  }

  document.addEventListener('click', function(e) {
    var panel = document.getElementById('themePanel');
    var btn   = document.getElementById('themeToggleBtn');
    if (panel && !panel.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
      closeThemePanel();
    }
  });

  window.applyTheme       = applyTheme;
  window.toggleThemePanel = toggleThemePanel;
  window.setThemeGroup    = setThemeGroup;

  document.addEventListener('DOMContentLoaded', function() {
    document.documentElement.setAttribute('data-theme', current);
    ALL_THEMES.forEach(function(t) {
      var el = document.getElementById('opt-' + t);
      if (el) el.classList.toggle('active', t === current);
    });
  });

  document.documentElement.setAttribute('data-theme', current);
})();
</script>

<script>
async function renderWebsite() {
  setContent('<div class="loading"><div class="spinner"></div> Loading website content...</div>');
  const r = await api('GET', '/public/homepage');
  if (!r.success) { setContent('<div class="empty">Failed to load website settings</div>'); return; }
  const s = r.data;

  const esc = v => escHtml(v||'');
  const field = (id, label, val, type='text', hint='') =>
    `<div class="form-group">
      <label>${label}${hint?`<span class="ws-hint-inline">${hint}</span>`:''}</label>
      ${type==='textarea'
        ? `<textarea id="ws_${id}" rows="3" style="resize:vertical">${esc(val)}</textarea>`
        : `<input id="ws_${id}" type="${type}" value="${esc(val)}">`}
    </div>`;
  const row = (...f) => `<div class="form-row">${f.join('')}</div>`;
  const tab = (id,lbl,act=false) => `<button class="tab-btn${act?' active':''}" onclick="wsTab('${id}')">${lbl}</button>`;
  const panel = (id,html,act=false) => `<div id="wsp_${id}" class="ws-panel" style="display:${act?'block':'none'}">${html}</div>`;
  const card = (title, sub, body) => `<div class="card" style="margin-bottom:16px">
    <div class="card-header"><div class="card-title">${title}</div>${sub?`<div style="font-size:12px;color:var(--muted)">${sub}</div>`:''}
    </div><div style="padding:20px">${body}</div></div>`;

  // ── TABLE BUILDERS ──────────────────────────────────────
  const tbl = (id, heads, rows, addBtn) =>
    `<div style="overflow-x:auto"><table class="ws-tbl" id="${id}">
      <thead><tr>${heads.map(h=>`<th>${h}</th>`).join('')}<th style="width:36px"></th></tr></thead>
      <tbody>${rows}</tbody>
    </table></div>
    <button class="btn btn-ghost btn-sm" style="margin-top:8px" onclick="${addBtn}">＋ Add Row</button>`;

  const delBtn = `<button type="button" onclick="this.closest('tr').remove()" style="background:none;border:none;color:var(--red);cursor:pointer;padding:4px 8px;font-size:18px;line-height:1">✕</button>`;

  const inp = (cls,val,ph,w='') => `<input class="${cls}" value="${esc(val)}" placeholder="${ph}" style="width:100%;box-sizing:border-box${w?';width:'+w:''}">`;
  const ta  = (cls,val,ph) => `<textarea class="${cls}" rows="2" placeholder="${ph}" style="width:100%;box-sizing:border-box;resize:none">${esc(val)}</textarea>`;
  const colorInp = (cls,val) => `<input class="${cls}" type="color" value="${val||'#1E88E5'}" style="width:44px;height:34px;padding:2px;border:1px solid var(--border);border-radius:6px;cursor:pointer;background:var(--bg3)">`;
  const sel = (cls,val,opts) => `<select class="${cls}" style="width:100%;padding:7px 8px;border:1px solid var(--border);border-radius:8px;background:var(--bg3);color:var(--text);font-size:13px">
    ${opts.map(([v,l])=>`<option value="${v}"${val===v?' selected':''}>${l}</option>`).join('')}
  </select>`;

  // Services  Name|emoji|price
  const buildServices = raw => {
    const p = (raw||'').split('|');
    let rows = '';
    for (let i=0;i<p.length||i<3;i+=3)
      rows+=`<tr><td>${inp('sv-n',p[i]||'','AC Repair')}</td><td style="width:80px">${inp('sv-e',p[i+1]||'','❄️')}</td><td>${inp('sv-p',p[i+2]||'','From ₹499')}</td><td>${delBtn}</td></tr>`;
    return tbl('svT',['Service Name','Icon','Starting Price'],rows,"wsAdd('svT',[['sv-n','Service'],['sv-e','❄️'],['sv-p','From ₹0']])");
  };

  // Steps  Title|Desc
  const buildSteps = raw => {
    const p = (raw||'').split('|');
    let rows = '';
    for (let i=0;i<p.length||i<2;i+=2)
      rows+=`<tr><td style="width:35%">${inp('st-t',p[i]||'','Step Title')}</td><td>${ta('st-d',p[i+1]||'','Description')}</td><td>${delBtn}</td></tr>`;
    return tbl('stT',['Step Title','Description'],rows,"wsAdd('stT',[['st-t','Step Title'],['st-d','Description']])");
  };

  // Features  emoji|title|desc
  const buildFeats = raw => {
    const p = (raw||'').split('|');
    let rows = '';
    for (let i=0;i<p.length||i<3;i+=3)
      rows+=`<tr><td style="width:60px">${inp('ft-e',p[i]||'','⚡')}</td><td style="width:30%">${inp('ft-t',p[i+1]||'','Title')}</td><td>${ta('ft-d',p[i+2]||'','Description')}</td><td>${delBtn}</td></tr>`;
    return tbl('ftT',['Icon','Title','Description'],rows,"wsAdd('ftT',[['ft-e','⭐'],['ft-t','Title'],['ft-d','Description']])");
  };

  // Zones  Name|color|region
  const buildZones = raw => {
    const p = (raw||'').split('|');
    const colors=[['green','🟢 Active'],['yellow','🟡 Expanding'],['blue','🔵 Coming Soon'],['red','🔴 Paused'],['gray','⚫ Inactive']];
    let rows = '';
    for (let i=0;i<p.length||i<3;i+=3)
      rows+=`<tr><td>${inp('zn-n',p[i]||'','Cyber City')}</td><td style="width:160px">${sel('zn-c',p[i+1]||'green',colors)}</td><td>${inp('zn-r',p[i+2]||'','Gurgaon')}</td><td>${delBtn}</td></tr>`;
    return tbl('znT',['Area / Zone Name','Status','City / Region'],rows,"wsAdd('znT',[['zn-n','Area Name'],['zn-c','green'],['zn-r','City']])");
  };

  // Testimonials  Name|Initials|color|location|text
  const buildTesti = raw => {
    const p = (raw||'').split('|');
    let rows = '';
    for (let i=0;i<p.length||i<5;i+=5)
      rows+=`<tr>
        <td>${inp('te-n',p[i]||'','Full Name')}</td>
        <td style="width:68px">${inp('te-i',p[i+1]||'','RK')}</td>
        <td style="width:50px">${colorInp('te-c',p[i+2]||'#1E88E5')}</td>
        <td style="width:28%">${inp('te-l',p[i+3]||'','City, Area')}</td>
        <td>${ta('te-x',p[i+4]||'','Review text…')}</td>
        <td>${delBtn}</td></tr>`;
    return tbl('teT',['Name','Initials','Color','Location','Review Text'],rows,"wsAdd('teT',[['te-n','Name'],['te-i','AB'],['te-c','#1E88E5'],['te-l','City'],['te-x','Review']])");
  };

  // Bullet list (single col)
  const buildList = (tid,cls,raw,ph) => {
    const items = (raw||'').split('|').filter(x=>x.trim());
    if (!items.length) items.push('');
    const rows = items.map(v=>`<tr><td>${inp(cls,v,ph)}</td><td>${delBtn}</td></tr>`).join('');
    return tbl(tid,['Item'],rows,`wsAdd('${tid}',[['${cls}','${ph}']])`);
  };

  // ── HTML ────────────────────────────────────────────────
  const html = `
<style>
.ws-tabs{display:flex;gap:4px;flex-wrap:wrap;margin-bottom:20px;border-bottom:2px solid var(--border);padding-bottom:0}
.tab-btn{background:none;border:none;padding:9px 15px;font-size:13px;font-weight:600;color:var(--muted);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;border-radius:4px 4px 0 0;transition:all .18s;white-space:nowrap}
.tab-btn.active{color:var(--accent);border-bottom-color:var(--accent);background:rgba(79,110,247,.07)}
.tab-btn:hover:not(.active){color:var(--text);background:var(--hover)}
.ws-panel .card{margin-bottom:14px}
.ws-info{background:rgba(79,110,247,.07);border:1px solid rgba(79,110,247,.18);border-radius:8px;padding:10px 14px;font-size:12px;color:var(--hint);line-height:1.7;margin-bottom:14px}
.ws-info b{color:var(--accent)}
.ws-badge{display:inline-block;font-size:10px;font-weight:800;letter-spacing:.8px;text-transform:uppercase;padding:2px 9px;border-radius:20px;margin-right:6px;background:rgba(79,110,247,.12);color:var(--accent)}
.ws-hint-inline{font-size:10px;color:var(--muted);font-weight:400;margin-left:6px}
.ws-tbl{width:100%;border-collapse:collapse;font-size:13px}
.ws-tbl th{background:var(--bg3);padding:8px 10px;text-align:left;font-size:11px;font-weight:700;color:var(--muted);border-bottom:1px solid var(--border);text-transform:uppercase;letter-spacing:.5px}
.ws-tbl td{padding:5px 5px;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:middle}
.ws-tbl td input,.ws-tbl td textarea,.ws-tbl td select{padding:6px 8px;border:1px solid var(--border);border-radius:6px;background:var(--bg2);color:var(--text);font-size:12px;font-family:inherit}
.ws-tbl tbody tr:hover{background:rgba(79,110,247,.03)}
.ws-preview-btn{background:rgba(255,111,0,.1);border:1px solid rgba(255,111,0,.25);color:#E65100;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
</style>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
  <div>
    <div style="font-family:'Poppins',sans-serif;font-size:18px;font-weight:700;color:var(--text)">🌐 Homepage Editor</div>
    <div style="font-size:12px;color:var(--muted);margin-top:2px">Edit every section of the FixGrid website. Click Save when done.</div>
  </div>
  <div style="display:flex;gap:10px">
    <a href="/" target="_blank" class="ws-preview-btn">👁 Preview Site</a>
    <button class="btn btn-primary" onclick="saveWebsite()">💾 Save All</button>
  </div>
</div>

<div class="ws-tabs">
  ${tab('hero',   '🏠 Hero Banner', true)}
  ${tab('services','🔧 Services')}
  ${tab('how',    '📋 How It Works')}
  ${tab('why',    '⭐ Why Us')}
  ${tab('stats',  '📊 Stats')}
  ${tab('zones',  '📍 Service Areas')}
  ${tab('corp',   '🏢 Corporate')}
  ${tab('testi',  '💬 Reviews')}
  ${tab('app',    '📱 App Download')}
  ${tab('cta',    '🎯 CTA Banner')}
  ${tab('footer', '🦶 Footer & Nav')}
  ${tab('seo',    '🔍 SEO')}
</div>

<!-- HERO -->
${panel('hero',`
  ${card('🏠 Hero Banner — Headlines & Buttons','The big section at the top of the homepage',`
    <div class="ws-info">This is the <b>first thing visitors see</b>. Keep the headline punchy and the subtitle under 20 words.</div>
    ${field('hero_badge','🔴 Live Badge Text',s.hp_hero_badge,'text','Top Left badge — e.g. "Technician at your doorstep in 60 minutes"')}
    ${row(field('hero_title1','Headline — Line 1',s.hp_hero_title1,'text','Large bold text'),field('hero_title2','Headline — Line 2',s.hp_hero_title2,'text','Large bold text'))}
    ${field('hero_title3','Headline — Line 3 (shown in orange/accent color)',s.hp_hero_title3,'text','Highlighted word or phrase')}
    ${field('hero_subtitle','Subtitle — 1-2 sentence description',s.hp_hero_subtitle,'textarea')}
    ${row(field('hero_btn1','Primary Button Text',s.hp_hero_btn1,'text','e.g. Book a Technician'),field('hero_btn2','Secondary Button Text',s.hp_hero_btn2,'text','e.g. Watch Demo'))}
    ${row(field('hero_trust1','Trust Badge 1',s.hp_hero_trust1,'text','e.g. ✓ Verified'),field('hero_trust2','Trust Badge 2',s.hp_hero_trust2),field('hero_trust3','Trust Badge 3',s.hp_hero_trust3))}
  `)}
  ${card('📊 Booking Card Stats','3 numbers shown inside the hero booking form',`
    ${row(field('hero_stat1_num','Stat 1 Number',s.hp_hero_stat1_num,'text','e.g. 5,000+'),field('hero_stat1_lbl','Stat 1 Label',s.hp_hero_stat1_lbl,'text','e.g. Jobs Done'))}
    ${row(field('hero_stat2_num','Stat 2 Number',s.hp_hero_stat2_num),field('hero_stat2_lbl','Stat 2 Label',s.hp_hero_stat2_lbl))}
    ${row(field('hero_stat3_num','Stat 3 Number',s.hp_hero_stat3_num),field('hero_stat3_lbl','Stat 3 Label',s.hp_hero_stat3_lbl))}
  `)}
  ${card('📢 Scrolling Marquee Strip','Messages that scroll across the orange band',`
    <div class="ws-info">Each row = one message in the scrolling strip. Keep each message short (5–8 words).</div>
    ${buildList('mqT','mq-v',s.hp_marquee,'e.g. ⚡ 60-Minute Response Guaranteed')}
  `)}
  ${card('💬 WhatsApp Contact Number','Used for booking CTA and footer link',`
    ${field('wa_number','WhatsApp Number — include country code, no spaces or +',s.hp_wa_number,'text','e.g. 919810519169')}
  `)}
`, true)}

<!-- SERVICES -->
${panel('services',`
  ${card('🔧 Services Section','The service cards shown on the homepage',`
    ${row(field('services_tag','Section Tag Label',s.hp_services_tag,'text','e.g. Our Services'),field('services_title','Section Heading',s.hp_services_title))}
    ${field('services_sub','Section Subtitle / Description',s.hp_services_sub)}
  `)}
  ${card('🃏 Service Cards','Each row = one service card displayed on homepage',`
    <div class="ws-info">Add each service with its <b>name</b>, <b>emoji icon</b>, and <b>starting price</b>.</div>
    ${buildServices(s.hp_services_list)}
  `)}
`)}

<!-- HOW IT WORKS -->
${panel('how',`
  ${card('📋 How It Works Section','Step-by-step process section',`
    ${row(field('how_tag','Section Tag Label',s.hp_how_tag),field('how_title','Section Heading',s.hp_how_title))}
    ${field('how_sub','Section Subtitle',s.hp_how_sub)}
  `)}
  ${card('🪜 Process Steps','Each row = one numbered step',`
    <div class="ws-info">Give each step a short <b>title</b> (3–5 words) and a <b>description</b> (1–2 sentences).</div>
    ${buildSteps(s.hp_how_steps)}
  `)}
`)}

<!-- WHY US -->
${panel('why',`
  ${card('⭐ Why Choose Us Section','Feature highlights that build customer trust',`
    ${row(field('why_title','Section Heading',s.hp_why_title),field('why_sub','Section Subtitle',s.hp_why_sub))}
  `)}
  ${card('✅ Feature Cards','Each row = one trust/feature card',`
    <div class="ws-info">Pick an <b>emoji</b> icon, write a short <b>title</b> (3–5 words), and a <b>description</b> (1–2 sentences).</div>
    ${buildFeats(s.hp_why_feats)}
  `)}
`)}

<!-- STATS -->
${panel('stats',`
  ${card('📊 Mid-Page Statistics','4 big credibility numbers shown in a full-width banner',`
    <div class="ws-info">Use impressive numbers like <b>"5,000+"</b> or <b>"98%"</b>. Keep labels short (2–3 words).</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div style="background:var(--bg3);border-radius:10px;padding:16px">
        <div style="font-size:11px;font-weight:700;color:var(--accent);margin-bottom:8px">STAT 1</div>
        ${row(field('stat1_num','Number',s.hp_stat1_num,'text','e.g. 5,000+'),field('stat1_lbl','Label',s.hp_stat1_lbl,'text','e.g. Jobs Done'))}
      </div>
      <div style="background:var(--bg3);border-radius:10px;padding:16px">
        <div style="font-size:11px;font-weight:700;color:var(--accent);margin-bottom:8px">STAT 2</div>
        ${row(field('stat2_num','Number',s.hp_stat2_num),field('stat2_lbl','Label',s.hp_stat2_lbl))}
      </div>
      <div style="background:var(--bg3);border-radius:10px;padding:16px">
        <div style="font-size:11px;font-weight:700;color:var(--accent);margin-bottom:8px">STAT 3</div>
        ${row(field('stat3_num','Number',s.hp_stat3_num),field('stat3_lbl','Label',s.hp_stat3_lbl))}
      </div>
      <div style="background:var(--bg3);border-radius:10px;padding:16px">
        <div style="font-size:11px;font-weight:700;color:var(--accent);margin-bottom:8px">STAT 4</div>
        ${row(field('stat4_num','Number',s.hp_stat4_num),field('stat4_lbl','Label',s.hp_stat4_lbl))}
      </div>
    </div>
  `)}
`)}

<!-- ZONES -->
${panel('zones',`
  ${card('📍 Service Areas Section','Cities and zones where FixGrid operates',`
    ${row(field('zones_tag','Section Tag Label',s.hp_zones_tag),field('zones_title','Section Heading',s.hp_zones_title))}
    ${field('zones_sub','Section Subtitle',s.hp_zones_sub)}
  `)}
  ${card('🗺️ Areas / Zones List','Each row = one area shown on the homepage map section',`
    <div class="ws-info"><b>Status Color:</b> 🟢 Green = Active · 🟡 Yellow = Expanding · 🔵 Blue = Coming Soon</div>
    ${buildZones(s.hp_zones_list)}
  `)}
`)}

<!-- CORPORATE -->
${panel('corp',`
  ${card('🏢 Corporate / B2B Section','Section targeting business customers',`
    ${row(field('corp_tag','Section Tag Label',s.hp_corp_tag),field('corp_title','Section Heading',s.hp_corp_title))}
    ${field('corp_sub','Description Paragraph',s.hp_corp_sub,'textarea')}
  `)}
  ${card('✅ Corporate Benefit Points','Bullet points listed on the left side of the corporate section',`
    ${buildList('cpT','cp-v',s.hp_corp_points,'e.g. Dedicated account manager')}
  `)}
  ${card('📦 AMC Contract Card','The highlighted card shown beside the corporate text',`
    ${row(field('corp_card_tag','Card Tag Label',s.hp_corp_card_tag),field('corp_card_title','Card Title',s.hp_corp_card_title))}
    ${field('corp_card_sub','Card Description',s.hp_corp_card_sub,'textarea')}
    <div style="margin-top:14px;font-weight:600;font-size:13px;margin-bottom:6px;color:var(--text)">Services included in AMC</div>
    ${buildList('ciT','ci-v',s.hp_corp_items,'e.g. IT Support')}
  `)}
`)}

<!-- TESTIMONIALS -->
${panel('testi',`
  ${card('💬 Customer Reviews Section','Testimonials shown as cards on the homepage',`
    ${row(field('testi_tag','Section Tag Label',s.hp_testi_tag),field('testi_title','Section Heading',s.hp_testi_title))}
    ${field('testi_sub','Section Subtitle',s.hp_testi_sub)}
  `)}
  ${card('⭐ Review Cards','Each row = one review card on the homepage',`
    <div class="ws-info"><b>Initials</b> = 2 letters shown in avatar circle (e.g. RK for Rahul Kumar). <b>Color</b> = avatar background. <b>Location</b> = shown below the name.</div>
    ${buildTesti(s.hp_testi_list)}
  `)}
`)}

<!-- APP -->
${panel('app',`
  ${card('📱 App Download Section','Section promoting the mobile app with store links',`
    ${row(field('app_tag','Section Tag Label',s.hp_app_tag),field('app_title1','Title Line 1 (normal)',s.hp_app_title1),field('app_title2','Title Line 2 (highlighted color)',s.hp_app_title2))}
    ${field('app_sub','Description Paragraph',s.hp_app_sub,'textarea')}
    ${row(field('app_store_url','Apple App Store URL',s.hp_app_store_url,'url','Leave blank if not on iOS'),field('app_play_url','Google Play Store URL',s.hp_app_play_url,'url','Leave blank if not on Android'))}
  `)}
  ${card('📋 App Feature List','Bullet points shown beside the app screenshot',`
    ${buildList('afT','af-v',s.hp_app_feats,'e.g. Real-time technician tracking')}
  `)}
`)}

<!-- CTA -->
${panel('cta',`
  ${card('🎯 Call-To-Action Banner','Bottom banner that prompts visitors to book',`
    <div class="ws-info">Make the headline action-oriented. Use "Book" or "Get" as the first word.</div>
    ${row(field('cta_title1','Headline Part 1 (normal text)',s.hp_cta_title1),field('cta_title2','Headline Part 2 (shown in accent color)',s.hp_cta_title2))}
    ${field('cta_sub','Subtitle / Supporting text',s.hp_cta_sub)}
    ${row(field('cta_btn','Primary Button Text',s.hp_cta_btn,'text','e.g. Book Now'),field('cta_wa_btn','WhatsApp Button Text',s.hp_cta_wa_btn,'text','e.g. Chat on WhatsApp'))}
  `)}
`)}

<!-- FOOTER -->
${panel('footer',`
  ${card('🦶 Footer Content','Bottom section of the homepage',`
    ${field('footer_about','Brand Description / About Paragraph',s.hp_footer_about,'textarea')}
    ${row(field('footer_tagline','Bottom Tagline (shown bottom-right)',s.hp_footer_tagline,'text','e.g. Trusted service, every time'),field('footer_copy','Copyright Text',s.hp_footer_copy,'text','e.g. © 2025 Hridya Tech Pvt Ltd'))}
  `)}
  ${card('🔗 Navigation Bar Links','Links shown in the top navbar across the site',`
    <div class="ws-info">Format: <b>Label|#anchor</b> pairs joined by <b>|</b><br>Example: <b>Services|#services|How it Works|#how|Areas|#zones|Contact|#contact</b></div>
    ${field('nav_links','Nav Links (Label|#anchor pairs)',s.hp_nav_links)}
    ${field('nav_cta','Navbar CTA Button Text',s.hp_nav_cta,'text','e.g. Book Now')}
  `)}
`)}

<!-- SEO -->
${panel('seo',`
  ${card('🔍 SEO — Search Engine Settings','Controls how your site appears in Google search results and browser tabs',`
    <div class="ws-info"><b>Page Title:</b> Keep under 60 characters · <b>Meta Description:</b> Keep under 160 characters for best Google display.</div>
    ${field('seo_title','Page Title (shown in Google + browser tab)',s.hp_seo_title,'text','Max 60 characters')}
    ${field('seo_desc','Meta Description (shown under title in Google results)',s.hp_seo_desc,'textarea','Max 160 characters')}
  `)}
`)}

<div style="text-align:right;margin-top:12px;padding-bottom:20px">
  <button class="btn btn-primary" onclick="saveWebsite()" style="padding:13px 32px;font-size:15px">💾 Save All Homepage Changes</button>
</div>
`;

  setContent(html);
}

// Tab switcher
function wsTab(id) {
  document.querySelectorAll('.ws-panel').forEach(p=>p.style.display='none');
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  const el = document.getElementById('wsp_'+id);
  if (el) el.style.display='block';
  event.target.classList.add('active');
}

// Add row to a ws-table
function wsAdd(tid, cols) {
  const tbody = document.querySelector('#'+tid+' tbody');
  if (!tbody) return;
  const cells = cols.map(([cls,ph]) => {
    if (cls==='te-c') return `<td><input class="${cls}" type="color" value="#1E88E5" style="width:44px;height:34px;padding:2px;border:1px solid var(--border);border-radius:6px;cursor:pointer;background:var(--bg3)"></td>`;
    if (cls==='zn-c') return `<td><select class="${cls}" style="width:100%;padding:7px 8px;border:1px solid var(--border);border-radius:8px;background:var(--bg3);color:var(--text);font-size:13px"><option value="green">🟢 Active</option><option value="yellow">🟡 Expanding</option><option value="blue">🔵 Coming Soon</option><option value="red">🔴 Paused</option><option value="gray">⚫ Inactive</option></select></td>`;
    if (cls==='st-d'||cls==='ft-d'||cls==='te-x') return `<td><textarea class="${cls}" rows="2" placeholder="${ph}" style="width:100%;box-sizing:border-box;resize:none;padding:6px 8px;border:1px solid var(--border);border-radius:6px;background:var(--bg2);color:var(--text);font-size:12px;font-family:inherit"></textarea></td>`;
    return `<td><input class="${cls}" value="" placeholder="${ph}" style="width:100%;box-sizing:border-box;padding:6px 8px;border:1px solid var(--border);border-radius:6px;background:var(--bg2);color:var(--text);font-size:12px"></td>`;
  });
  const tr = document.createElement('tr');
  tr.innerHTML = cells.join('') + `<td><button type="button" onclick="this.closest('tr').remove()" style="background:none;border:none;color:var(--red);cursor:pointer;padding:4px 8px;font-size:18px;line-height:1">✕</button></td>`;
  tbody.appendChild(tr);
}

// Collect table rows → pipe string
function wsCollect(tid, classes) {
  const tbody = document.querySelector('#'+tid+' tbody');
  if (!tbody) return null;
  return Array.from(tbody.querySelectorAll('tr'))
    .map(tr => classes.map(c => { const el=tr.querySelector('.'+c); return el?el.value:''; }).join('|'))
    .filter(r => r.replace(/\|/g,'').trim())
    .join('|');
}

// Save all homepage content
async function saveWebsite() {
  const body = {};
  // Plain fields
  ['hero_badge','hero_title1','hero_title2','hero_title3','hero_subtitle','hero_btn1','hero_btn2',
   'hero_trust1','hero_trust2','hero_trust3','hero_stat1_num','hero_stat1_lbl','hero_stat2_num',
   'hero_stat2_lbl','hero_stat3_num','hero_stat3_lbl','wa_number',
   'services_tag','services_title','services_sub',
   'how_tag','how_title','how_sub',
   'why_title','why_sub',
   'stat1_num','stat1_lbl','stat2_num','stat2_lbl','stat3_num','stat3_lbl','stat4_num','stat4_lbl',
   'zones_tag','zones_title','zones_sub',
   'corp_tag','corp_title','corp_sub','corp_card_tag','corp_card_title','corp_card_sub',
   'testi_tag','testi_title','testi_sub',
   'app_tag','app_title1','app_title2','app_sub','app_store_url','app_play_url',
   'cta_title1','cta_title2','cta_sub','cta_btn','cta_wa_btn',
   'footer_about','footer_tagline','footer_copy','nav_links','nav_cta',
   'seo_title','seo_desc',
  ].forEach(k => { const el=document.getElementById('ws_'+k); if(el) body['hp_'+k]=el.value; });

  // Table fields
  const t = (key,tid,cls) => { const v=wsCollect(tid,cls); if(v!==null) body['hp_'+key]=v; };
  t('marquee',      'mqT',['mq-v']);
  t('services_list','svT',['sv-n','sv-e','sv-p']);
  t('how_steps',    'stT',['st-t','st-d']);
  t('why_feats',    'ftT',['ft-e','ft-t','ft-d']);
  t('zones_list',   'znT',['zn-n','zn-c','zn-r']);
  t('corp_points',  'cpT',['cp-v']);
  t('corp_items',   'ciT',['ci-v']);
  t('testi_list',   'teT',['te-n','te-i','te-c','te-l','te-x']);
  t('app_feats',    'afT',['af-v']);

  const r = await api('POST', '/admin/save-homepage', body);
  if (r.success) toast('✅ Homepage saved!', 'success');
  else toast(r.message||'Error saving', 'error');
}


// Dynamically inject Google Maps script using key stored in app_settings

// ═══════════════════════════════════════════════════════════
//  ZONE MANAGEMENT — renderZones()
//  Full CRUD: create zones, assign/remove engineers, map view
// ═══════════════════════════════════════════════════════════

async function renderZones() {
  setContent('<div class="loading"><div class="spinner"></div> Loading zones...</div>');
  const r = await api('GET', '/admin/zones');
  if (!r.success) { setContent('<div class="empty">⚠ Failed to load zones</div>'); return; }
  const engR = await api('GET', '/admin/engineers');
  if (engR.success) allEngineers = engR.data || [];
  const zones = r.data || [];
  const activeCount = zones.filter(z => z.is_active).length;
  const totalEngs   = zones.reduce((s,z) => s + (parseInt(z.engineer_count)||0), 0);

  setContent(`
  <style>
    #zmWrap{display:flex;gap:14px;height:calc(100vh - 180px);min-height:520px}
    #zmSidebar{width:300px;flex-shrink:0;display:flex;flex-direction:column;overflow:hidden;border-radius:var(--radius);border:1px solid var(--border);background:var(--bg2)}
    #zmSidebar .zm-head{padding:13px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
    #zmSidebar .zm-head h4{margin:0;font-size:13px;font-weight:700}
    #zmList{flex:1;overflow-y:auto;padding:8px}
    .zm-card{padding:10px 12px;border-radius:8px;border:1px solid var(--border);background:var(--bg3);margin-bottom:6px;cursor:pointer;transition:all .14s}
    .zm-card:hover,.zm-card.active{border-color:var(--accent);background:var(--nav-active-bg)}
    .zm-card-name{font-size:12px;font-weight:700;margin-bottom:3px;display:flex;align-items:center;gap:6px}
    .zm-card-meta{font-size:11px;color:var(--muted);display:flex;flex-wrap:wrap;gap:6px}
    .zm-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
    #zmMapWrap{flex:1;border-radius:var(--radius);overflow:hidden;border:1px solid var(--border);position:relative;min-height:400px}
    #zmMapEl{width:100%;height:100%;min-height:480px}
    .zm-stat-bar{display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap}
    .zm-stat{background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:8px 14px;font-size:12px;display:flex;align-items:center;gap:7px}
    .zm-stat strong{font-size:16px;font-family:'Geist Mono',monospace}
    .zone-modal-body{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .map-container{height:280px;border-radius:10px;overflow:hidden;border:1px solid var(--border);background:#0d1117;margin-top:8px}
    .map-hint{font-size:11px;color:var(--muted);margin-top:4px}
    .eng-row{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--border)}
    .eng-row:last-child{border-bottom:none}
    .eng-avatar{width:36px;height:36px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px;flex-shrink:0}
    .eng-info{flex:1;min-width:0}
    .eng-name{font-weight:600;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .eng-meta{font-size:11px;color:var(--muted)}
    .status-dot{width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:4px}
    .dot-available{background:#22c55e}.dot-busy{background:#f59e0b}.dot-offline{background:#6b7280}
    @media(max-width:768px){#zmWrap{flex-direction:column;height:auto}#zmSidebar{width:100%;height:240px}#zmMapWrap{height:360px}}
    @media(max-width:600px){.zone-modal-body{grid-template-columns:1fr}}
  </style>

  <div class="zm-stat-bar">
    <div class="zm-stat">\uD83D\uDDFE Total <strong>${zones.length}</strong></div>
    <div class="zm-stat"><span style="color:var(--green)">\u25CF</span> Active <strong>${activeCount}</strong></div>
    <div class="zm-stat">\uD83D\uDC77 Engineers <strong>${totalEngs}</strong></div>
    <div style="flex:1"></div>
    <button class="btn btn-primary btn-sm" onclick="openZoneModal()">+ Create Zone</button>
  </div>

  <div id="zmWrap">
    <div id="zmSidebar">
      <div class="zm-head">
        <h4>\uD83D\uDDFE Zones (${zones.length})</h4>
        <button class="btn btn-ghost btn-sm" onclick="zmFitAll()" title="Fit all" style="font-size:11px;padding:4px 8px">\u229E All</button>
      </div>
      <div id="zmList">
        ${zones.length === 0
          ? '<div style="text-align:center;padding:24px;color:var(--muted);font-size:12px">No zones yet.<br><br><button class="btn btn-primary btn-sm" onclick="openZoneModal()">+ Create First Zone</button></div>'
          : zones.map(z => `
            <div class="zm-card" id="zmcard-${z.id}" onclick="zmSelectZone(${z.id})">
              <div class="zm-card-name">
                <div class="zm-dot" style="background:${z.is_active ? '#22D99F' : '#7E85A8'}"></div>
                ${z.name}
              </div>
              <div class="zm-card-meta">
                <span>\uD83D\uDCCD ${z.city}${z.state ? ', '+z.state : ''}</span>
                <span>\uD83D\uDC77 ${z.engineer_count||0} eng</span>
                ${z.radius_km ? '<span>\u2B55 '+z.radius_km+'km</span>' : ''}
              </div>
            </div>`).join('')
        }
      </div>
    </div>
    <div id="zmMapWrap">
      <div id="zmMapEl" style="width:100%;height:100%;min-height:480px"></div>
    </div>
  </div>

  <!-- CREATE / EDIT ZONE MODAL -->
  <div id="zoneModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1000;align-items:center;justify-content:center;padding:16px">
    <div style="background:var(--bg2);border-radius:16px;width:100%;max-width:680px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 80px rgba(0,0,0,.4)">
      <div style="padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <h3 style="margin:0;font-size:17px" id="zoneModalTitle">Create Zone</h3>
        <button class="btn btn-ghost btn-sm" onclick="closeZoneModal()" style="font-size:18px;padding:4px 10px">\u2715</button>
      </div>
      <div style="padding:24px">
        <input type="hidden" id="zoneEditId" value="">
        <div class="zone-modal-body">
          <div><label class="form-label">Zone Name *</label><input type="text" id="zName" class="form-input" placeholder="e.g. Cyber City" style="width:100%"></div>
          <div><label class="form-label">City *</label><input type="text" id="zCity" class="form-input" placeholder="e.g. Gurgaon" style="width:100%"></div>
          <div><label class="form-label">State</label><input type="text" id="zState" class="form-input" placeholder="e.g. Haryana" style="width:100%"></div>
          <div><label class="form-label">Radius (km)</label><input type="number" id="zRadius" class="form-input" value="5" min="0.5" max="100" step="0.5" style="width:100%"></div>
        </div>
        <div style="margin-top:14px">
          <label class="form-label">Description</label>
          <textarea id="zDesc" class="form-input" rows="2" placeholder="Optional description" style="width:100%;resize:vertical"></textarea>
        </div>
        <div style="margin-top:16px">
          <label class="form-label">Zone Centre Location</label>
          <div style="display:flex;gap:8px;margin-bottom:8px">
            <input type="number" id="zLat" class="form-input" placeholder="Latitude"  step="0.0001" style="flex:1">
            <input type="number" id="zLng" class="form-input" placeholder="Longitude" step="0.0001" style="flex:1">
            <button class="btn btn-secondary btn-sm" onclick="locateMe()" title="Use my location">\uD83D\uDCCD My Location</button>
          </div>
          <div class="map-container" id="zonePicker"></div>
          <div class="map-hint">Click on the map to set zone centre. Circle shows coverage radius.</div>
        </div>
        <div style="display:flex;gap:10px;margin-top:20px;justify-content:flex-end">
          <button class="btn btn-ghost" onclick="closeZoneModal()">Cancel</button>
          <button class="btn btn-primary" id="zoneSaveBtn" onclick="saveZone()">\uD83D\uDCBE Save Zone</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ENGINEER ASSIGNMENT MODAL -->
  <div id="engModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1000;align-items:center;justify-content:center;padding:16px">
    <div style="background:var(--bg2);border-radius:16px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 80px rgba(0,0,0,.4)">
      <div style="padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <h3 style="margin:0;font-size:17px" id="engModalTitle">Engineers in Zone</h3>
        <button class="btn btn-ghost btn-sm" onclick="closeEngModal()" style="font-size:18px;padding:4px 10px">\u2715</button>
      </div>
      <div style="padding:20px">
        <div style="display:flex;gap:8px;margin-bottom:16px">
          <select id="addEngSelect" class="form-input" style="flex:1"><option value="">— Select engineer to add —</option></select>
          <button class="btn btn-primary btn-sm" onclick="addEngineerToZone()">+ Add</button>
        </div>
        <div id="engList" style="min-height:60px"><div class="loading"><div class="spinner"></div></div></div>
      </div>
    </div>
  </div>
  `);

  window._zmZones = zones;
  setTimeout(() => initZoneMap(), 150);
}

// Google Maps API key from app settings (already loaded globally)
// ── ZONE MAP (overview) ──────────────────────────────────────────────────
var _zmMap = null, _zmLayers = {}, _zmSelectedId = null;
var zonePickerMap = null, zonePickerCircle = null, zonePickerMarker = null;

async function initZoneMap() {
  const el = document.getElementById('zmMapEl');
  if (!el) return;
  await _lmLoadLeaflet();
  if (typeof L === 'undefined') return;
  if (_zmMap) { try { _zmMap.remove(); } catch(e2) {} _zmMap = null; }
  _zmLayers = {};
  _zmMap = L.map(el, { zoomControl: true }).setView([28.4595, 77.0266], 11);
  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    attribution: '© OpenStreetMap © CARTO', subdomains: 'abcd', maxZoom: 19
  }).addTo(_zmMap);
  const zones = window._zmZones || [];
  zones.filter(z => z.latitude && z.longitude).forEach(z => zmAddZoneLayer(z));
  setTimeout(() => { _zmMap.invalidateSize(); zmFitAll(); }, 300);
}

function zmAddZoneLayer(z) {
  if (!_zmMap || typeof L === 'undefined') return;
  const lat = parseFloat(z.latitude), lng = parseFloat(z.longitude);
  const radius = parseFloat(z.radius_km || 5) * 1000;
  const color  = z.is_active ? '#4F7CFF' : '#7E85A8';
  const engCount = parseInt(z.engineer_count) || 0;

  const circle = L.circle([lat, lng], {
    radius, color, fillColor: color, fillOpacity: 0.12,
    weight: 2, opacity: 0.7, dashArray: z.is_active ? null : '6 4'
  }).addTo(_zmMap);

  const dot = L.circleMarker([lat, lng], {
    radius: 7, color: '#fff', fillColor: color, fillOpacity: 1, weight: 2
  }).addTo(_zmMap);

  const labelHtml =
    '<div style="background:rgba(0,0,0,.72);color:#fff;padding:3px 8px;' +
    'border-radius:6px;font-size:11px;font-weight:700;white-space:nowrap;' +
    'border:1px solid ' + color + ';pointer-events:none">' +
    z.name + (engCount > 0 ? ' \u00B7 ' + engCount + '\u{1F477}' : '') + '</div>';
  const labelIcon = L.divIcon({ html: labelHtml, className: '', iconAnchor: [0, 22] });
  const labelMark = L.marker([lat, lng], { icon: labelIcon, interactive: false, zIndexOffset: 500 }).addTo(_zmMap);

  const popHtml =
    '<div style="font-family:sans-serif;min-width:200px">' +
    '<b style="font-size:13px">' + z.name + '</b><br>' +
    '<span style="font-size:11px;color:#888">\uD83D\uDCCD ' + z.city + (z.state ? ', ' + z.state : '') + '</span><br>' +
    '<span style="font-size:11px">Radius: <b>' + (z.radius_km || 5) + ' km</b></span><br>' +
    '<span style="font-size:11px">Engineers: <b>' + engCount + '</b></span><br>' +
    '<span style="font-size:11px">Status: <b style="color:' + (z.is_active ? '#16a34a' : '#dc2626') + '">' + (z.is_active ? 'Active' : 'Inactive') + '</b></span><br><br>' +
    '<button onclick="openEngineerModal(' + z.id + ',\'' + z.name.replace(/'/g,"\\'") + '\')" style="font-size:11px;padding:4px 10px;background:#4F7CFF;color:#fff;border:none;border-radius:6px;cursor:pointer;margin-right:4px">\uD83D\uDC77 Engineers</button>' +
    '<button onclick="openZoneModal(' + z.id + ')" style="font-size:11px;padding:4px 10px;background:#374151;color:#fff;border:none;border-radius:6px;cursor:pointer">\u270F\uFE0F Edit</button>' +
    '</div>';

  circle.bindPopup(popHtml, { maxWidth: 260 });
  dot.bindPopup(popHtml, { maxWidth: 260 });
  circle.on('click', () => zmHighlightCard(z.id));
  dot.on('click',    () => zmHighlightCard(z.id));
  _zmLayers[z.id] = { circle, dot, labelMark };
}

function zmSelectZone(id) {
  zmHighlightCard(id);
  const z = (window._zmZones || []).find(x => x.id === id);
  if (!z || !z.latitude || !z.longitude || !_zmMap || typeof L === 'undefined') return;
  const bounds = L.circle([parseFloat(z.latitude), parseFloat(z.longitude)],
    { radius: parseFloat(z.radius_km || 5) * 1000 }).getBounds();
  _zmMap.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
  if (_zmLayers[id]) setTimeout(() => _zmLayers[id].dot.openPopup(), 300);
}

function zmHighlightCard(id) {
  _zmSelectedId = id;
  document.querySelectorAll('.zm-card').forEach(c =>
    c.classList.toggle('active', c.id === 'zmcard-' + id));
}

function zmFitAll() {
  if (!_zmMap || typeof L === 'undefined') return;
  const zones = (window._zmZones || []).filter(z => z.latitude && z.longitude);
  if (!zones.length) { _zmMap.setView([28.4595, 77.0266], 10); return; }
  if (zones.length === 1) {
    zmSelectZone(zones[0].id); return;
  }
  _zmMap.fitBounds(
    L.latLngBounds(zones.map(z => [parseFloat(z.latitude), parseFloat(z.longitude)])),
    { padding: [60, 60], maxZoom: 13 }
  );
}

// ── ZONE PICKER (modal: create / edit) ────────────────────────────────────
async function initZonePicker() {
  const el = document.getElementById('zonePicker');
  if (!el) return;
  el.style.background = '#0d1117';
  el.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#888;font-size:12px;gap:6px"><div class="spinner"></div> Loading map\u2026</div>';
  await _lmLoadLeaflet();
  if (typeof L === 'undefined') { el.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#888;font-size:12px">Map unavailable</div>'; return; }
  if (zonePickerMap) { try { zonePickerMap.remove(); } catch(e2) {} zonePickerMap = null; }
  el.innerHTML = '';
  const defLat = parseFloat(document.getElementById('zLat')?.value) || 28.4595;
  const defLng = parseFloat(document.getElementById('zLng')?.value) || 77.0266;
  const defR   = (parseFloat(document.getElementById('zRadius')?.value) || 5) * 1000;
  zonePickerMap    = L.map(el, { zoomControl: true }).setView([defLat, defLng], 12);
  zonePickerCircle = L.circle([defLat, defLng], { radius: defR, color: '#4F7CFF', fillColor: '#4F7CFF', fillOpacity: 0.15, weight: 2 }).addTo(zonePickerMap);
  zonePickerMarker = L.marker([defLat, defLng], { draggable: true, title: 'Zone Centre \u2014 drag to reposition' }).addTo(zonePickerMap);
  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { attribution: '© OpenStreetMap © CARTO', subdomains: 'abcd', maxZoom: 19 }).addTo(zonePickerMap);
  zonePickerMap.on('click', e => updateMapPin(e.latlng.lat, e.latlng.lng));
  zonePickerMarker.on('dragend', e => { const p = e.target.getLatLng(); updateMapPin(p.lat, p.lng); });
  document.getElementById('zRadius')?.addEventListener('input', function() {
    if (zonePickerCircle) zonePickerCircle.setRadius((parseFloat(this.value) || 5) * 1000);
  });
  ['zLat','zLng'].forEach(id => document.getElementById(id)?.addEventListener('change', syncMapFromInputs));
  setTimeout(() => zonePickerMap.invalidateSize(), 100);
}

function updateMapPin(lat, lng) {
  if (zonePickerMarker) zonePickerMarker.setLatLng([lat, lng]);
  if (zonePickerCircle) zonePickerCircle.setLatLng([lat, lng]);
  if (zonePickerMap)    zonePickerMap.panTo([lat, lng]);
  const le = document.getElementById('zLat'); if (le) le.value = lat.toFixed(6);
  const lg = document.getElementById('zLng'); if (lg) lg.value = lng.toFixed(6);
}

function syncMapFromInputs() {
  const lat = parseFloat(document.getElementById('zLat')?.value);
  const lng = parseFloat(document.getElementById('zLng')?.value);
  if (!isNaN(lat) && !isNaN(lng)) updateMapPin(lat, lng);
}

function locateMe() {
  if (!navigator.geolocation) { alert('Geolocation not supported.'); return; }
  navigator.geolocation.getCurrentPosition(
    pos => updateMapPin(pos.coords.latitude, pos.coords.longitude),
    ()  => alert('Could not get location. Enter coordinates manually.')
  );
}

// ── ZONE MODAL ────────────────────────────────────────────────────────────
async function openZoneModal(id = null) {
  currentZoneId = id;
  document.getElementById('zoneModalTitle').textContent = id ? 'Edit Zone' : 'Create Zone';
  document.getElementById('zoneSaveBtn').textContent    = id ? '\uD83D\uDCBE Update Zone' : '\uD83D\uDCBE Save Zone';
  ['zoneEditId','zName','zCity','zState','zDesc','zLat','zLng'].forEach(f => {
    const el = document.getElementById(f); if (el) el.value = '';
  });
  const radEl = document.getElementById('zRadius'); if (radEl) radEl.value = '5';
  if (id) {
    const r = await api('GET', '/admin/zones?action=detail&id=' + id);
    if (r.success && r.data) {
      const z = r.data;
      document.getElementById('zoneEditId').value = z.id;
      document.getElementById('zName').value      = z.name        || '';
      document.getElementById('zCity').value      = z.city        || '';
      document.getElementById('zState').value     = z.state       || '';
      document.getElementById('zDesc').value      = z.description || '';
      document.getElementById('zLat').value       = z.latitude    || '';
      document.getElementById('zLng').value       = z.longitude   || '';
      document.getElementById('zRadius').value    = z.radius_km   || 5;
    }
  }
  document.getElementById('zoneModal').style.display = 'flex';
  setTimeout(async () => {
    await initZonePicker();
    if (id) setTimeout(syncMapFromInputs, 400);
  }, 100);
}

function closeZoneModal() {
  document.getElementById('zoneModal').style.display = 'none';
  if (zonePickerMap) { try { zonePickerMap.remove(); } catch(e2) {} zonePickerMap = null; }
  zonePickerCircle = null; zonePickerMarker = null;
}

async function saveZone() {
  const id     = document.getElementById('zoneEditId').value;
  const name   = document.getElementById('zName').value.trim();
  const city   = document.getElementById('zCity').value.trim();
  const state  = document.getElementById('zState').value.trim();
  const desc   = document.getElementById('zDesc').value.trim();
  const lat    = document.getElementById('zLat').value;
  const lng    = document.getElementById('zLng').value;
  const radius = document.getElementById('zRadius').value;

  if (!name) { alert('Zone name is required.'); return; }
  if (!city) { alert('City is required.');      return; }

  const btn = document.getElementById('zoneSaveBtn');
  btn.disabled = true; btn.textContent = 'Saving…';

  const payload = {
    action      : id ? 'update' : 'create',
    name, city, state,
    description : desc,
    latitude    : lat    ? parseFloat(lat)    : null,
    longitude   : lng    ? parseFloat(lng)    : null,
    radius_km   : radius ? parseFloat(radius) : 5,
  };
  if (id) payload.id = parseInt(id);

  const r = await api('POST', '/admin/zones', payload);
  btn.disabled = false;
  btn.textContent = id ? '💾 Update Zone' : '💾 Save Zone';

  if (r.success) {
    closeZoneModal();
    renderZones();
    showToast(r.message || 'Zone saved', 'success');
  } else {
    alert(r.message || 'Failed to save zone');
  }
}

async function toggleZone(id) {
  const r = await api('POST', '/admin/zones', { action: 'toggle', id });
  if (r.success) { renderZones(); showToast('Zone status updated', 'success'); }
  else alert(r.message || 'Failed to update zone');
}

async function deleteZone(id, name) {
  if (!confirm('Delete zone "' + name + '"?\n\nThis will also remove all engineer assignments for this zone.')) return;
  const r = await api('POST', '/admin/zones', { action: 'delete', id });
  if (r.success) { renderZones(); showToast('Zone deleted', 'success'); }
  else alert(r.message || 'Failed to delete zone');
}

// ── ENGINEER ASSIGNMENT MODAL ─────────────────────────────────────────────
async function openEngineerModal(zoneId, zoneName) {
  currentZoneId = zoneId;
  document.getElementById('engModalTitle').textContent = '👷 Engineers — ' + zoneName;
  document.getElementById('engModal').style.display = 'flex';
  await refreshEngList();
  await refreshAddDropdown();
}

function closeEngModal() {
  document.getElementById('engModal').style.display = 'none';
  currentZoneId = null;
}

async function refreshEngList() {
  const listEl = document.getElementById('engList');
  if (!listEl) return;
  listEl.innerHTML = '<div class="loading"><div class="spinner"></div></div>';

  const r = await api('GET', '/admin/zone-engineers?zone_id=' + currentZoneId);
  if (!r.success) { listEl.innerHTML = '<div class="empty">Failed to load engineers</div>'; return; }

  const engs = r.data || [];
  if (engs.length === 0) {
    listEl.innerHTML = '<div class="empty" style="padding:20px;text-align:center;color:var(--muted)">No engineers assigned yet.<br>Use the dropdown above to add engineers.</div>';
    return;
  }

  listEl.innerHTML = engs.map(e => `
    <div class="eng-row" id="erow-${e.id}">
      <div class="eng-avatar">${(e.name||'?')[0].toUpperCase()}</div>
      <div class="eng-info">
        <div class="eng-name">${e.name}</div>
        <div class="eng-meta">
          <span class="status-dot dot-${e.status || 'offline'}"></span>${e.status || 'offline'}
          · ${e.phone}
          ${e.city ? ' · ' + e.city : ''}
        </div>
      </div>
      <div style="display:flex;gap:6px;flex-shrink:0">
        <button class="btn btn-xs ${e.is_available ? 'btn-success' : 'btn-ghost'}"
          title="${e.is_available ? 'Available in zone — click to pause' : 'Paused — click to activate'}"
          onclick="toggleEngAvail(${e.id})">
          ${e.is_available ? '✅' : '⏸'}
        </button>
        <button class="btn btn-xs btn-danger" onclick="removeEngFromZone(${e.id},'${e.name.replace(/'/g,"\\'")}')">✕</button>
      </div>
    </div>
  `).join('');
}

async function refreshAddDropdown() {
  const sel = document.getElementById('addEngSelect');
  if (!sel) return;

  // Get engineers NOT yet in this zone
  const r = await api('GET', '/admin/zone-engineers?action=available&zone_id=' + currentZoneId);
  const list = r.success ? (r.data || []) : allEngineers;

  sel.innerHTML = '<option value="">— Select engineer to add —</option>'
    + list.map(e => `<option value="${e.id}">${e.name} — ${e.phone}${e.city ? ' (' + e.city + ')' : ''}</option>`).join('');
}

async function addEngineerToZone() {
  const sel = document.getElementById('addEngSelect');
  const engId = parseInt(sel?.value);
  if (!engId) { alert('Please select an engineer.'); return; }

  const r = await api('POST', '/admin/zone-engineers', {
    action: 'assign', zone_id: currentZoneId, engineer_id: engId
  });
  if (r.success) {
    showToast(r.message || 'Engineer added', 'success');
    sel.value = '';
    await refreshEngList();
    await refreshAddDropdown();
    // Refresh zone card engineer count
    renderZoneCard(currentZoneId);
  } else {
    alert(r.message || 'Failed to add engineer');
  }
}

async function removeEngFromZone(engId, engName) {
  if (!confirm('Remove ' + engName + ' from this zone?')) return;
  const r = await api('POST', '/admin/zone-engineers', {
    action: 'remove', zone_id: currentZoneId, engineer_id: engId
  });
  if (r.success) {
    showToast(engName + ' removed', 'success');
    await refreshEngList();
    await refreshAddDropdown();
  } else alert(r.message || 'Failed to remove');
}

async function toggleEngAvail(engId) {
  const r = await api('POST', '/admin/zone-engineers', {
    action: 'toggle_availability', zone_id: currentZoneId, engineer_id: engId
  });
  if (r.success) { await refreshEngList(); }
  else alert(r.message || 'Failed to update');
}

// Refresh just the engineer count on a zone card after assignment changes
async function renderZoneCard(zoneId) {
  const r = await api('GET', '/admin/zones?action=detail&id=' + zoneId);
  if (!r.success) return;
  const z = r.data;
  const countEl = document.querySelector('#zcard-' + zoneId + ' .zone-stat b');
  if (countEl) countEl.textContent = (z.engineers || []).length;
}

// ── TOAST HELPER — delegates to existing admin toast() ───────────────────
function showToast(msg, type) { toast(msg, type); }

</script>
</body>
</html>
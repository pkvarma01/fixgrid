<?php header('Content-Type: text/html; charset=utf-8'); header('Cache-Control: no-store, no-cache, must-revalidate'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<script>

var API = 'https://www.fixgrid.in/api'; // Change to your API URL
var token = localStorage.getItem('fsm_cust_token');
var userData = (function(){try{return JSON.parse(localStorage.getItem('fsm_cust_user'));}catch(e){return null;}})();
var selectedService = '';
var currentJobId = null;
var ratingValue = 0;
var loginPhone = localStorage.getItem('fsm_login_phone') || '';

// ---- INIT ----
if (token && userData) {
  showScreen('s-home');
  document.getElementById('homeGreet').textContent = userData.name.split(' ')[0];
  document.getElementById('profileInitial').textContent = userData.name[0];
  document.getElementById('profileName').textContent = userData.name;
  document.getElementById('profilePhone').textContent = userData.phone;
  loadActiveJobs();
}

// ---- API ----
async function api(method, path, data) {
  var opts = {method, headers: {'Authorization': 'Bearer ' + token}};
  if (data && method !== 'GET') {
    opts.headers['Content-Type'] = 'application/json';
    opts.body = JSON.stringify(data);
  }
  try {
    var r = await fetch(API + path, opts);
    return r.json();
  } catch(e) { return {success:false, message:'Network error'}; }
}

// ---- AUTH ----
async function register() {
  var name = document.getElementById('regName').value.trim();
  var phone = document.getElementById('regPhone').value.trim();
  if (!name || !phone) return toast('Name and phone required', 'error');

  var res = await api('POST', '/auth/register', {name, phone});
  if (res.success) { loginPhone = res.data ? res.data.phone : phone; localStorage.setItem('fsm_login_phone', loginPhone); showScreen('s-otp'); toast('OTP sent!', 'success'); return; }
  if (false) { saveSession(res);
    showScreen('s-home');
    if(userData){document.getElementById('homeGreet').textContent=userData.name.split(' ')[0];document.getElementById('profileInitial').textContent=userData.name[0];document.getElementById('profileName').textContent=userData.name;document.getElementById('profilePhone').textContent=userData.phone;}
  } else toast(res.message, 'error');
}

async function requestOtp() {
  var phone = document.getElementById('loginPhone').value.trim();
  if (!phone) return toast('Enter phone number', 'error');
  loginPhone = phone; localStorage.setItem('fsm_login_phone', loginPhone);
  var res = await api('POST', '/auth/register', {phone});
  if (res.success) {
    document.getElementById('otpSubtext').textContent = 'Sent to ' + phone;
    if (res.otp) toast('Dev OTP: ' + res.otp); // Remove in production
    showScreen('s-otp');
  } else toast(res.message, 'error');
}

function otpNext(el, nextId) {
  if (el.value && nextId) document.getElementById(nextId).focus();
}

</script>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>FSM - Book a Service</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent}
:root{--bg:#F5F7FF;--card:#fff;--accent:#4F6EF7;--accent-d:#3755D8;--green:#22C55E;--amber:#F59E0B;--red:#EF4444;--text:#1A1D2E;--muted:#6B7280;--hint:#A9AFCB;--border:#E5E8F5}
body{font-family:'Nunito',sans-serif;background:var(--bg);color:var(--text);max-width:430px;margin:0 auto;min-height:100vh;display:flex;flex-direction:column;position:relative;overflow-x:hidden}

/* Screens */
.screen{display:none;flex-direction:column;min-height:100vh}.screen.active{display:flex}

/* Top bar */
.topbar{background:var(--accent);color:#fff;padding:16px 20px;display:flex;align-items:center;gap:12px}
.topbar-back{cursor:pointer;opacity:.8;display:flex;align-items:center}
.topbar-title{font-size:17px;font-weight:700;flex:1}
.topbar-action{font-size:13px;cursor:pointer;opacity:.9}

/* Onboarding */
.onboard{background:linear-gradient(160deg,#4F6EF7 0%,#7B5CF7 100%);flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px 32px;color:#fff;text-align:center}
.onboard-icon{font-size:64px;margin-bottom:24px}
.onboard-title{font-size:28px;font-weight:800;line-height:1.2;margin-bottom:12px}
.onboard-sub{font-size:15px;opacity:.85;line-height:1.6}
.onboard-form{background:var(--card);border-radius:24px 24px 0 0;padding:28px 24px}
.onboard-form h3{font-size:18px;font-weight:700;margin-bottom:20px}

/* Forms */
.form-group{margin-bottom:16px}
.form-label{font-size:13px;font-weight:600;color:var(--muted);margin-bottom:6px;display:block}
.form-input{width:100%;background:#F5F7FF;border:1.5px solid var(--border);color:var(--text);padding:12px 16px;border-radius:12px;font-size:15px;font-family:'Nunito',sans-serif;outline:none;transition:border .15s}
.form-input:focus{border-color:var(--accent)}
.form-select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236B7280' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;padding-right:36px}

/* Buttons */
.btn{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:14px;border-radius:14px;font-size:16px;font-weight:700;cursor:pointer;border:none;font-family:'Nunito',sans-serif;transition:all .15s}
.btn-accent{background:var(--accent);color:#fff}.btn-accent:active{background:var(--accent-d)}
.btn-outline{background:transparent;border:2px solid var(--border);color:var(--text)}
.btn-sm{padding:9px 16px;font-size:13px;width:auto;border-radius:10px}
.btn-green{background:var(--green);color:#fff}
.btn-red{background:var(--red);color:#fff}

/* Cards */
.card{background:var(--card);border-radius:16px;padding:18px;margin-bottom:12px;box-shadow:0 1px 4px rgba(0,0,0,.06)}

/* Services grid */
.services-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.service-btn{background:var(--card);border:2px solid var(--border);border-radius:14px;padding:14px 8px;text-align:center;cursor:pointer;transition:all .15s}
.service-btn:active,.service-btn.selected{border-color:var(--accent);background:#EEF2FF}
.service-icon{font-size:28px;margin-bottom:4px}
.service-label{font-size:12px;font-weight:600;color:var(--text)}

/* Status steps */
.status-steps{display:flex;flex-direction:column;gap:0;padding:0 4px}
.step{display:flex;gap:14px;position:relative;padding-bottom:20px}
.step:last-child{padding-bottom:0}
.step::before{content:'';position:absolute;left:14px;top:28px;bottom:0;width:2px;background:var(--border)}
.step:last-child::before{display:none}
.step-circle{width:28px;height:28px;border-radius:50%;border:2px solid var(--border);background:var(--card);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:12px;font-weight:700;color:var(--hint);position:relative;z-index:1}
.step.done .step-circle{background:var(--green);border-color:var(--green);color:#fff}
.step.active-step .step-circle{background:var(--accent);border-color:var(--accent);color:#fff}
.step-text{padding-top:4px}
.step-name{font-size:14px;font-weight:600}
.step-desc{font-size:12px;color:var(--muted);margin-top:2px}

/* Map mock */
.map-mock{width:100%;height:220px;background:linear-gradient(135deg,#e8efff 0%,#d5e3ff 100%);border-radius:16px;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center;margin-bottom:12px}
.map-grid{position:absolute;inset:0;background-image:linear-gradient(rgba(79,110,247,.07) 1px,transparent 1px),linear-gradient(90deg,rgba(79,110,247,.07) 1px,transparent 1px);background-size:30px 30px}
.map-pin{position:absolute;font-size:28px;filter:drop-shadow(0 2px 4px rgba(0,0,0,.2))}
.map-road{position:absolute;border-radius:4px;background:rgba(255,255,255,.7)}
.map-pulse{position:absolute;width:16px;height:16px;border-radius:50%;background:var(--accent);animation:pulse 1.5s ease-in-out infinite}
.map-pulse::after{content:'';position:absolute;inset:-6px;border-radius:50%;border:2px solid var(--accent);animation:ring 1.5s ease-in-out infinite}
@keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.2)}}
@keyframes ring{0%{opacity:1;transform:scale(1)}100%{opacity:0;transform:scale(2.5)}}

/* Bottom nav */
.bottom-nav{background:var(--card);border-top:1px solid var(--border);display:flex;padding:8px 0 max(8px,env(safe-area-inset-bottom));position:sticky;bottom:0}
.nav-btn{flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;padding:6px;cursor:pointer;color:var(--hint);font-size:10px;font-weight:600;border:none;background:none;font-family:'Nunito',sans-serif}
.nav-btn.active{color:var(--accent)}
.nav-icon{font-size:22px}

/* Content area */
.content{flex:1;padding:20px;overflow-y:auto}

/* OTP input */
.otp-inputs{display:flex;gap:10px;justify-content:center;margin:20px 0}
.otp-in{width:50px;height:56px;text-align:center;font-size:22px;font-weight:700;background:#F5F7FF;border:2px solid var(--border);border-radius:12px;font-family:'Nunito',sans-serif;color:var(--text);outline:none}
.otp-in:focus{border-color:var(--accent)}

/* Rating stars */
.stars{display:flex;gap:8px;justify-content:center;margin:12px 0}
.star{font-size:36px;cursor:pointer;transition:transform .1s;opacity:.3}
.star.on{opacity:1}
.star:hover{transform:scale(1.15)}

/* Toast */
.toast{position:fixed;bottom:80px;left:50%;transform:translateX(-50%) translateY(40px);background:#1A1D2E;color:#fff;border-radius:12px;padding:12px 20px;font-size:13px;font-weight:600;opacity:0;transition:all .3s;white-space:nowrap;z-index:9999}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0)}

.badge{display:inline-block;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:700}
.badge-green{background:#DCFCE7;color:#166534}
.badge-blue{background:#DBEAFE;color:#1E3A8A}
.badge-amber{background:#FEF3C7;color:#92400E}
</style>
</head>
<body>

<!-- REGISTER SCREEN -->
<div class="screen active" id="s-register">
  <div class="onboard">
    <div class="onboard-icon">🔧</div>
    <div class="onboard-title">Book a Service Engineer</div>
    <div class="onboard-sub">Fast, reliable field service at your doorstep</div>
  </div>
  <div class="onboard-form">
    <h3>Get Started</h3>
    <div class="form-group">
      <label class="form-label">Full Name</label>
      <input class="form-input" id="regName" placeholder="Your full name">
    </div>
    <div class="form-group">
      <label class="form-label">Mobile Number</label>
      <input class="form-input" id="regPhone" type="tel" placeholder="+91 98765 43210">
    </div>
    <button class="btn btn-accent" onclick="register()">Continue →</button>
    <button class="btn btn-outline" style="margin-top:10px" onclick="showScreen('s-login')">Already registered? Login</button>
  </div>
</div>

<!-- LOGIN SCREEN -->
<div class="screen" id="s-login">
  <div class="onboard">
    <div class="onboard-icon">👋</div>
    <div class="onboard-title">Welcome Back!</div>
    <div class="onboard-sub">Enter your mobile to get an OTP</div>
  </div>
  <div class="onboard-form">
    <h3>Login with OTP</h3>
    <div class="form-group">
      <label class="form-label">Mobile Number</label>
      <input class="form-input" id="loginPhone" type="tel" placeholder="+91 98765 43210">
    </div>
    <button class="btn btn-accent" onclick="requestOtp()">Send OTP</button>
    <button class="btn btn-outline" style="margin-top:10px" onclick="showScreen('s-register')">New user? Register</button>
  </div>
</div>

<!-- OTP SCREEN -->
<div class="screen" id="s-otp">
  <div class="onboard">
    <div class="onboard-icon">📱</div>
    <div class="onboard-title">Enter OTP</div>
    <div class="onboard-sub" id="otpSubtext">Sent to your mobile</div>
  </div>
  <div class="onboard-form">
    <div class="otp-inputs">
      <input class="otp-in" id="o1" maxlength="1" oninput="otpNext(this,'o2')">
      <input class="otp-in" id="o2" maxlength="1" oninput="otpNext(this,'o3')">
      <input class="otp-in" id="o3" maxlength="1" oninput="otpNext(this,'o4')">
      <input class="otp-in" id="o4" maxlength="1" oninput="otpNext(this,'o5')">
      <input class="otp-in" id="o5" maxlength="1" oninput="otpNext(this,'o6')">
      <input class="otp-in" id="o6" maxlength="1" oninput="otpNext(this,null)">
    </div>
    <button class="btn btn-accent" onclick="verifyOtp()">Verify OTP</button>
    <p style="text-align:center;margin-top:14px;font-size:13px;color:var(--muted)">Didn't receive? <span style="color:var(--accent);cursor:pointer;font-weight:600" onclick="requestOtp()">Resend</span></p>
  </div>
</div>

<!-- HOME SCREEN -->
<div class="screen" id="s-home">
  <div class="topbar" style="padding-bottom:20px">
    <div style="flex:1">
      <div style="opacity:.8;font-size:13px">Good day 👋</div>
      <div style="font-size:18px;font-weight:800" id="homeGreet">User</div>
    </div>
    <div style="cursor:pointer" onclick="showScreen('s-history')">📋</div>
  </div>
  <div class="content" style="margin-top:-10px">
    <div class="card">
      <div style="font-size:15px;font-weight:700;margin-bottom:14px">What service do you need?</div>
      <div class="services-grid" id="servicesGrid">
        <div class="service-btn" onclick="selectService(this,'AC Repair')"><div class="service-icon">❄️</div><div class="service-label">AC Repair</div></div>
        <div class="service-btn" onclick="selectService(this,'Plumbing')"><div class="service-icon">🔧</div><div class="service-label">Plumbing</div></div>
        <div class="service-btn" onclick="selectService(this,'Electrical')"><div class="service-icon">⚡</div><div class="service-label">Electrical</div></div>
        <div class="service-btn" onclick="selectService(this,'Appliance')"><div class="service-icon">🏠</div><div class="service-label">Appliance</div></div>
        <div class="service-btn" onclick="selectService(this,'Carpentry')"><div class="service-icon">🪚</div><div class="service-label">Carpentry</div></div>
        <div class="service-btn" onclick="selectService(this,'Painting')"><div class="service-icon">🎨</div><div class="service-label">Painting</div></div>
        <div class="service-btn" onclick="selectService(this,'CCTV')"><div class="service-icon">📹</div><div class="service-label">CCTV</div></div>
        <div class="service-btn" onclick="selectService(this,'Pest Control')"><div class="service-icon">🐜</div><div class="service-label">Pest Control</div></div>
        <div class="service-btn" onclick="selectService(this,'Other')"><div class="service-icon">🔩</div><div class="service-label">Other</div></div>
      </div>
      <button class="btn btn-accent" style="margin-top:16px" onclick="goBook()">Book Now</button>
    </div>

    <!-- Active jobs -->
    <div id="activeJobSection"></div>
  </div>
  <div class="bottom-nav">
    <button class="nav-btn active"><div class="nav-icon">🏠</div>Home</button>
    <button class="nav-btn" onclick="showScreen('s-history')"><div class="nav-icon">📋</div>History</button>
    <button class="nav-btn" onclick="showScreen('s-profile')"><div class="nav-icon">👤</div>Profile</button>
  </div>
</div>

<!-- BOOK SERVICE SCREEN -->
<div class="screen" id="s-book">
  <div class="topbar">
    <div class="topbar-back" onclick="showScreen('s-home')">← </div>
    <div class="topbar-title">Book Service</div>
  </div>
  <div class="content">
    <div class="form-group">
      <label class="form-label">Service Type</label>
      <input class="form-input" id="bookService" readonly>
    </div>
    <div class="form-group">
      <label class="form-label">Describe the Problem</label>
      <textarea class="form-input" id="bookDesc" rows="3" placeholder="E.g., AC not cooling, water leaking from pipe..."></textarea>
    </div>
    <div class="form-group">
      <label class="form-label">Priority</label>
      <select class="form-input form-select" id="bookPriority">
        <option value="normal">Normal</option>
        <option value="high">High - Within today</option>
        <option value="urgent">Urgent - ASAP</option>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Your Address</label>
      <textarea class="form-input" id="bookAddress" rows="2" placeholder="House/Flat no, Street, Area, City..."></textarea>
    </div>
    <div class="form-group" style="display:flex;gap:10px">
      <div style="flex:1"><label class="form-label">Latitude</label><input class="form-input" id="bookLat" placeholder="Auto-detect"></div>
      <div style="flex:1"><label class="form-label">Longitude</label><input class="form-input" id="bookLng" placeholder="Auto-detect"></div>
    </div>
    <button class="btn btn-outline btn-sm" style="margin-bottom:16px" onclick="getLocation()">📍 Use My Location</button>
    <button class="btn btn-accent" onclick="submitBooking()">🔧 Confirm Booking</button>
  </div>
</div>

<!-- TRACK ENGINEER SCREEN -->
<div class="screen" id="s-track">
  <div class="topbar">
    <div class="topbar-back" onclick="showScreen('s-home')">← </div>
    <div class="topbar-title">Track Engineer</div>
    <div class="topbar-action" id="jobNum"></div>
  </div>
  <div class="content">
    <!-- Simulated map -->
    <div class="map-mock">
      <div class="map-grid"></div>
      <div class="map-road" style="width:200px;height:8px;top:55%;left:10%;transform:rotate(-5deg)"></div>
      <div class="map-road" style="width:8px;height:180px;top:10%;left:50%"></div>
      <div class="map-pin" style="bottom:30%;left:42%">📍</div>
      <div class="map-pulse" style="top:30%;right:28%"></div>
      <div style="position:absolute;bottom:12px;right:12px;background:rgba(255,255,255,.9);border-radius:8px;padding:6px 10px;font-size:11px;font-weight:700;color:var(--accent)">Engineer nearby</div>
    </div>

    <div class="card">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
        <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#4F6EF7,#7B5CF7);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:18px" id="engInitials">R</div>
        <div>
          <div style="font-weight:700;font-size:15px" id="trackEngName">Engineer</div>
          <div style="font-size:13px;color:var(--muted)" id="trackEngPhone">—</div>
        </div>
        <div style="margin-left:auto" id="trackStatus"></div>
      </div>
      <div class="status-steps" id="trackSteps"></div>
    </div>

    <div class="card" id="completeCard" style="display:none">
      <div style="text-align:center;margin-bottom:12px;font-size:15px;font-weight:700">Rate your experience</div>
      <div class="stars" id="ratingStars">
        <span class="star" onclick="setStar(1)">★</span>
        <span class="star" onclick="setStar(2)">★</span>
        <span class="star" onclick="setStar(3)">★</span>
        <span class="star" onclick="setStar(4)">★</span>
        <span class="star" onclick="setStar(5)">★</span>
      </div>
      <textarea class="form-input" id="ratingFeedback" rows="2" placeholder="Optional feedback..." style="margin-bottom:10px"></textarea>
      <button class="btn btn-accent" onclick="submitRating()">Submit Rating</button>
    </div>
  </div>
</div>

<!-- HISTORY SCREEN -->
<div class="screen" id="s-history">
  <div class="topbar">
    <div class="topbar-back" onclick="showScreen('s-home')">← </div>
    <div class="topbar-title">Job History</div>
  </div>
  <div class="content" id="historyList">
    <div style="text-align:center;padding:40px;color:var(--hint)">Loading...</div>
  </div>
</div>

<!-- PROFILE SCREEN -->
<div class="screen" id="s-profile">
  <div class="topbar">
    <div class="topbar-back" onclick="showScreen('s-home')">← </div>
    <div class="topbar-title">My Profile</div>
  </div>
  <div class="content">
    <div style="text-align:center;padding:24px 0">
      <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#4F6EF7,#7B5CF7);margin:0 auto 12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:28px;font-weight:700" id="profileInitial">?</div>
      <div style="font-size:20px;font-weight:700" id="profileName">—</div>
      <div style="color:var(--muted);font-size:14px" id="profilePhone">—</div>
    </div>
    <div class="card">
      <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border)"><span style="color:var(--muted)">Email</span><span id="profileEmail">—</span></div>
      <div style="display:flex;justify-content:space-between;padding:10px 0"><span style="color:var(--muted)">Address</span><span id="profileAddress" style="max-width:200px;text-align:right;font-size:13px">—</span></div>
    </div>
    <button class="btn btn-outline" onclick="logout()" style="margin-top:8px">Logout</button>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>

async function verifyOtp() {
  if (!loginPhone) loginPhone = localStorage.getItem('fsm_login_phone') || '';
  var otp = ['o1','o2','o3','o4','o5','o6'].map(function(id){ return document.getElementById(id).value; }).join('');
  if (otp.length < 6) return toast('Enter complete OTP', 'error');
  var res = await api('POST', '/auth/verify-otp', {phone: loginPhone, otp: otp});
  if (res.success) {
    token = res.data.token;
    userData = res.data.customer;
    localStorage.setItem('fsm_cust_token', token);
    localStorage.setItem('fsm_cust_user', JSON.stringify(userData));
    showScreen('s-home');
    if (userData) {
      document.getElementById('homeGreet').textContent = userData.name.split(' ')[0];
      document.getElementById('profileInitial').textContent = userData.name[0];
      document.getElementById('profileName').textContent = userData.name;
      document.getElementById('profilePhone').textContent = userData.phone;
    }
  } else {
    toast(res.message || 'Invalid OTP', 'error');
  }
}

function saveSession(res) { if(res&&res.data){res.token=res.data.token;res.user=res.data.customer||res.data;}
  token = res.token;
  userData = res.user;
  localStorage.setItem('fsm_cust_token', token);
  localStorage.setItem('fsm_cust_user', JSON.stringify(userData));
  if (userData) {
    document.getElementById('profileInitial').textContent = userData.name[0];
    document.getElementById('profileName').textContent = userData.name;
    document.getElementById('profilePhone').textContent = userData.phone;
  }
}

function logout() {
  api('POST', '/auth/logout', {});
  localStorage.clear();
  token = null; userData = null;
  showScreen('s-register');
}

// ---- BOOKING ----
function selectService(el, name) {
  document.querySelectorAll('.service-btn').forEach(b => b.classList.remove('selected'));
  el.classList.add('selected');
  selectedService = name;
}

function goBook() {
  if (!selectedService) return toast('Select a service first', 'error');
  document.getElementById('bookService').value = selectedService;
  showScreen('s-book');
}

function getLocation() {
  if (!navigator.geolocation) return toast('Geolocation not supported');
  navigator.geolocation.getCurrentPosition(pos => {
    document.getElementById('bookLat').value = pos.coords.latitude.toFixed(6);
    document.getElementById('bookLng').value = pos.coords.longitude.toFixed(6);
    toast('Location detected ✓');
  }, () => toast('Location access denied', 'error'));
}

async function submitBooking() {
  var data = {
    service_id: {'Plumbing':1,'Electrical':2,'AC Repair':3,'Carpentry':4,'Painting':5,'Pest Control':6,'Appliance':2,'CCTV':2,'Other':1}[document.getElementById('bookService').value] || 1,
    description:  document.getElementById('bookDesc').value,
    priority:     document.getElementById('bookPriority').value,
    address:      document.getElementById('bookAddress').value,
    latitude:     parseFloat(document.getElementById('bookLat').value) || 28.6139,
    longitude:    parseFloat(document.getElementById('bookLng').value) || 77.2090,
  };
  if (!data.address) return toast('Enter your address', 'error');

  var res = await api('POST', '/customer/create-job', data);
  if (res.success) {
    currentJobId = res.data ? res.data.id : null;
    toast('Booking confirmed! 🎉');
    if(currentJobId) loadTrack(currentJobId);
  } else toast(res.message, 'error');
}

// ---- TRACKING ----
async function loadTrack(jobId) {
  currentJobId = jobId;
  var res = await api('GET', '/customer/track-engineer?job_id=' + jobId);
  if (!res.success) return;

  var j = res.data ? (res.data.job || res.data) : res.job;
  var engLoc = res.data ? res.data.engineer_location : null;

  document.getElementById('jobNum').textContent = j.job_number;

  var engN=j&&j.engineer_name?j.engineer_name:'Searching...'; var engP=j&&j.engineer_phone?j.engineer_phone:''; document.getElementById('engInitials').textContent=engN[0]||'?'; document.getElementById('trackEngName').textContent=engN; document.getElementById('trackEngPhone').textContent=engP;

  var allSteps = ['pending','assigned','accepted','on_the_way','arrived','working','completed'];
  var stepLabels = {
    pending:   ['Submitted','Your request has been received'],
    assigned:  ['Engineer Found','We found an engineer near you'],
    accepted:  ['Accepted','Engineer confirmed the job'],
    on_the_way:['On the Way','Engineer is heading to you'],
    arrived:   ['Arrived','Engineer is at your location'],
    working:   ['Working','Service in progress'],
    completed: ['Completed','Job done successfully!'],
  };
  var currentIdx = allSteps.indexOf(j.status);

  document.getElementById('trackSteps').innerHTML = allSteps.map((s, i) => {
    var cls = i < currentIdx ? 'done' : i === currentIdx ? 'active-step' : '';
    var icon = i < currentIdx ? '✓' : i+1;
    return `<div class="step ${cls}">
      <div class="step-circle">${icon}</div>
      <div class="step-text">
        <div class="step-name">${stepLabels[s][0]}</div>
        <div class="step-desc">${stepLabels[s][1]}</div>
      </div>
    </div>`;
  }).join('');

  document.getElementById('completeCard').style.display = j.status === 'completed' ? 'block' : 'none';
  document.getElementById('trackStatus').innerHTML = `<span class="badge badge-blue">${j.status.replace('_',' ')}</span>`;

  showScreen('s-track');
}

function setStar(n) {
  ratingValue = n;
  document.querySelectorAll('.star').forEach((s, i) => s.classList.toggle('on', i < n));
}

async function submitRating() {
  if (!ratingValue) return toast('Select a rating', 'error');
  var res = await api('POST', '/customer/rate-job', {
    job_id: currentJobId,
    rating: ratingValue,
    feedback: document.getElementById('ratingFeedback').value
  });
  toast(res.message, res.success ? '' : 'error');
  if (res.success) showScreen('s-home');
}

// ---- HISTORY ----
async function loadHistory() {
  var res = await api('GET', '/customer/jobs');
  var cont = document.getElementById('historyList');
  if (!res.success) { cont.innerHTML = '<div style="text-align:center;padding:40px;color:var(--hint)">Failed to load</div>'; return; }
  var histJobs=Array.isArray(res.data)?res.data:[]; if(!histJobs.length) { cont.innerHTML = '<div style="text-align:center;padding:40px;color:var(--hint)">No jobs yet</div>'; return; }

  var statusColors = {pending:'amber',assigned:'blue',completed:'green'};
  cont.innerHTML = histJobs.map(j => `
    <div class="card" onclick="loadTrack(${j.id})" style="cursor:pointer">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
        <div style="font-weight:700">${j.service_type}</div>
        <span class="badge badge-${statusColors[j.status]||'blue'}">${j.status.replace('_',' ')}</span>
      </div>
      <div style="font-size:13px;color:var(--muted);margin-bottom:4px">${j.address}</div>
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--hint)">
        <span>${j.engineer_name || 'Assigning...'}</span>
        <span>${new Date(j.created_at).toLocaleDateString()}</span>
      </div>
      ${j.rating ? `<div style="margin-top:6px;font-size:12px">★ ${j.rating} — ${j.feedback||''}</div>` : ''}
    </div>`).join('');
}

</script>
<script>

async function loadActiveJobs() {
  var res = await api('GET', '/customer/jobs');
  if (!res.success) return;
  var jobs=Array.isArray(res.data)?res.data:[]; var active=jobs.filter(j => !['completed','cancelled'].includes(j.status));
  var sec = document.getElementById('activeJobSection');
  if (!active.length) { sec.innerHTML = ''; return; }
  sec.innerHTML = `<div style="font-weight:700;margin-bottom:10px;font-size:15px">Active Jobs</div>` +
    active.map(j => `
      <div class="card" onclick="loadTrack(${j.id})" style="cursor:pointer;border-left:3px solid var(--accent)">
        <div style="display:flex;justify-content:space-between">
          <div style="font-weight:700">${j.service_type}</div>
          <span class="badge badge-blue">${j.status.replace('_',' ')}</span>
        </div>
        <div style="font-size:12px;color:var(--muted);margin-top:4px">Tap to track →</div>
      </div>`).join('');
}

// ---- UTILS ----
function showScreen(id) {
  document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  if (id === 's-history') loadHistory();
  if (id === 's-profile' && userData) {
    document.getElementById('profileEmail').textContent = userData.email || '—';
    document.getElementById('profileAddress').textContent = userData.address || '—';
  }
}

function toast(msg, type='') {
  var t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'toast show';
  setTimeout(() => t.classList.remove('show'), 3000);
}

</script>

</body>
</html>

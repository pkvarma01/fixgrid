/**
 * FixGrid — Main Website JavaScript v3
 * Mobile menu + backdrop + ESC · Nav scroll class · Scroll reveal ·
 * Active nav · Booking form · Service picker · Contact form ·
 * Back to top · Counter animation · Safe area support
 */

const WA_NUMBER = '919810519169';

// ── MOBILE MENU ───────────────────────────────────────────────
function toggleMob() {
  const open = document.getElementById('mobNav').classList.contains('open');
  open ? closeMob() : openMob();
}

function openMob() {
  const m = document.getElementById('mobNav');
  const h = document.getElementById('hamBtn');
  const b = document.getElementById('mobBackdrop');
  if (!m || !h) return;
  m.classList.add('open');
  if (b) b.classList.add('open');
  document.body.style.overflow = 'hidden';
  const sp = h.querySelectorAll('span');
  sp[0].style.transform = 'rotate(45deg) translate(5px,5px)';
  sp[1].style.opacity   = '0';
  sp[2].style.transform = 'rotate(-45deg) translate(5px,-5px)';
}

function closeMob() {
  const m = document.getElementById('mobNav');
  const h = document.getElementById('hamBtn');
  const b = document.getElementById('mobBackdrop');
  if (!m || !h) return;
  m.classList.remove('open');
  if (b) b.classList.remove('open');
  document.body.style.overflow = '';
  const sp = h.querySelectorAll('span');
  sp[0].style.transform = '';
  sp[1].style.opacity   = '';
  sp[2].style.transform = '';
}

// Close on ESC key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeMob();
});

// ── NAV SCROLL CLASS ──────────────────────────────────────────
function initNavScroll() {
  const nav = document.getElementById('mainNav');
  if (!nav) return;
  function check() {
    nav.classList.toggle('scrolled', window.scrollY > 24);
  }
  window.addEventListener('scroll', check, { passive: true });
  check();
}

// ── SCROLL REVEAL ─────────────────────────────────────────────
function initReveal() {
  // Skip if prefers-reduced-motion
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.querySelectorAll('.reveal').forEach(el => el.classList.add('visible'));
    return;
  }

  const io = new IntersectionObserver(function(entries) {
    entries.forEach(function(e) {
      if (e.isIntersecting) {
        e.target.classList.add('visible');
        io.unobserve(e.target);
      }
    });
  }, { threshold: 0.08, rootMargin: '0px 0px -36px 0px' });

  document.querySelectorAll('.reveal').forEach(el => io.observe(el));
}

// ── ACTIVE NAV LINK ───────────────────────────────────────────
function setActiveNavLink() {
  const path = window.location.pathname;
  document.querySelectorAll('.nav-links a, .mob-nav a').forEach(function(a) {
    a.classList.remove('active');
    if (a.classList.contains('nav-book') || a.classList.contains('mob-book')) return;
    const href = a.getAttribute('href') || '';
    if (
      href === path ||
      (path === '/' && href === 'index.php') ||
      (path.endsWith('/') && href === 'index.php') ||
      (href !== 'index.php' && href !== '/' && path.includes(href.split('.')[0]))
    ) {
      a.classList.add('active');
    }
  });
}

// ── SMOOTH SCROLL (navbar-height aware) ───────────────────────
function initSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach(function(a) {
    a.addEventListener('click', function(e) {
      const id = a.getAttribute('href');
      if (id === '#') return;
      const target = document.querySelector(id);
      if (!target) return;
      e.preventDefault();
      closeMob();
      const navH = parseInt(getComputedStyle(document.documentElement)
        .getPropertyValue('--nav-h') || '64', 10);
      const top = target.getBoundingClientRect().top + window.scrollY - navH - 8;
      window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
    });
  });
}

// ── BOOKING FORM ──────────────────────────────────────────────
function submitBook() {
  const n  = document.getElementById('bName');
  const p  = document.getElementById('bPhone');
  const sv = document.getElementById('bService');
  const l  = document.getElementById('bLoc');
  const t  = document.getElementById('bTime');
  if (!n || !p || !sv || !l) return;

  const name     = n.value.trim();
  const phone    = p.value.trim();
  const service  = sv.value;
  const location = l.value.trim();

  if (!name || !phone || !service || !location) {
    showFormError('Please fill Name, Phone, Service and Location.');
    return;
  }

  let msg = 'Hi FixGrid!%0A%0A'
    + 'Name: '        + encodeURIComponent(name)
    + '%0APhone: '    + encodeURIComponent(phone)
    + '%0AService: '  + encodeURIComponent(service)
    + '%0ALocation: ' + encodeURIComponent(location);

  if (t && t.value.trim())
    msg += '%0APreferred Time: ' + encodeURIComponent(t.value.trim());

  const btn = document.querySelector('.book-btn');
  if (btn) {
    const orig = btn.textContent;
    btn.textContent = '✓ Opening WhatsApp…';
    btn.disabled = true;
    setTimeout(() => { btn.textContent = orig; btn.disabled = false; }, 2500);
  }

  window.open('https://wa.me/' + WA_NUMBER + '?text=' + msg, '_blank');

  // Meta Pixel — Lead event (booking form submitted)
  if (typeof fbq === 'function') {
    fbq('track', 'Lead', {
      content_name:     service,
      content_category: 'Home Service Booking',
      value:            0,
      currency:         'INR'
    });
  }
}

function showFormError(msg) {
  document.querySelector('.form-error-msg')?.remove();
  const err = document.createElement('div');
  err.className = 'form-error-msg';
  err.textContent = '⚠ ' + msg;
  Object.assign(err.style, {
    background: '#FEF2F2',
    color: '#DC2626',
    border: '1px solid #FECACA',
    borderRadius: '9px',
    padding: '11px 15px',
    fontSize: '13px',
    marginBottom: '10px',
  });
  document.querySelector('.book-btn')?.insertAdjacentElement('beforebegin', err);
  setTimeout(() => err.parentNode && err.remove(), 4000);
}

// ── SERVICE CARD SELECT ───────────────────────────────────────
function pickService(name) {
  const sel = document.getElementById('bService');
  if (sel) {
    const stem = name.split('&')[0].trim();
    for (let i = 0; i < sel.options.length; i++) {
      if (sel.options[i].text.startsWith(stem)) { sel.selectedIndex = i; break; }
    }
    const contact = document.getElementById('contact');
    if (contact) {
      const navH = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--nav-h') || '64', 10);
      const top = contact.getBoundingClientRect().top + window.scrollY - navH - 8;
      window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
      return;
    }
  }
  window.location.href = 'index.php#contact';
}

// ── CONTACT FORM ──────────────────────────────────────────────
function submitContactForm() {
  const name    = document.getElementById('cName');
  const phone   = document.getElementById('cPhone');
  const service = document.getElementById('cService');
  const loc     = document.getElementById('cLocation');
  const msg_el  = document.getElementById('cMessage');
  if (!name || !phone || !service) return;

  const n = name.value.trim();
  const p = phone.value.trim();
  const s = service.value;
  const l = loc    ? loc.value.trim()    : '';
  const m = msg_el ? msg_el.value.trim() : '';

  if (!n || !p || !s) { alert('Please fill all required fields.'); return; }

  let msg = 'Hi FixGrid!%0A%0A'
    + 'Name: '    + encodeURIComponent(n)
    + '%0APhone: '  + encodeURIComponent(p)
    + '%0AService: '+ encodeURIComponent(s);
  if (l) msg += '%0ALocation: ' + encodeURIComponent(l);
  if (m) msg += '%0AMessage: '  + encodeURIComponent(m);

  window.open('https://wa.me/' + WA_NUMBER + '?text=' + msg, '_blank');

  // Meta Pixel — Contact + Lead events
  if (typeof fbq === 'function') {
    fbq('track', 'Contact');
    fbq('track', 'Lead', {
      content_name:     s,
      content_category: 'Contact Page Booking',
      value:            0,
      currency:         'INR'
    });
  }
}

// ── COUNTER ANIMATION ─────────────────────────────────────────
function animateCounters() {
  const counters = document.querySelectorAll('[data-count]');
  if (!counters.length) return;
  const io = new IntersectionObserver(function(entries) {
    entries.forEach(function(e) {
      if (!e.isIntersecting) return;
      const el     = e.target;
      const target = parseInt(el.dataset.count, 10);
      const suffix = el.dataset.suffix || '';
      let current  = 0;
      const steps  = 52;
      const inc    = target / steps;
      const timer  = setInterval(function() {
        current += inc;
        el.textContent = (current >= target ? target : Math.floor(current)) + suffix;
        if (current >= target) clearInterval(timer);
      }, 28);
      io.unobserve(el);
    });
  }, { threshold: 0.6 });
  counters.forEach(el => io.observe(el));
}

// ── BACK TO TOP ───────────────────────────────────────────────
function initBackToTop() {
  const btn = document.getElementById('backToTop');
  if (!btn) return;
  window.addEventListener('scroll', function() {
    const show = window.scrollY > 450;
    btn.style.opacity       = show ? '1' : '0';
    btn.style.pointerEvents = show ? 'auto' : 'none';
    btn.style.transform     = show ? 'translateY(0)' : 'translateY(10px)';
  }, { passive: true });
  btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
}

// ── CLOSE MOB NAV ON RESIZE TO DESKTOP ───────────────────────
function initResizeHandler() {
  const mq = window.matchMedia('(min-width: 900px)');
  mq.addEventListener('change', function(e) {
    if (e.matches) closeMob();
  });
}

// ── INIT ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  initNavScroll();
  initReveal();
  setActiveNavLink();
  initSmoothScroll();
  animateCounters();
  initBackToTop();
  initResizeHandler();
});

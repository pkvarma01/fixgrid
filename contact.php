<?php
/**
 * FixGrid — Contact & Booking Page (contact.php)
 * FIX: Replaced local h() + hardcoded $s/$navLinks/$services with shared
 *      partials/init.php. All pages now use the single h() helper.
 */
require_once __DIR__ . '/partials/init.php';

// Pre-select service from query string (e.g. contact.php?service=AC+Repair)
$preService = isset($_GET['service']) ? h($_GET['service']) : '';

// Contact page uses named services list — derive from $services (already parsed)
$serviceNames = array_column($services, 'name');
$serviceNames[] = 'AMC Contract';
$serviceNames[] = 'Other';

// Zones list — can be moved to init.php later if needed
$zones = ['Cyber City','MG Road','Sohna Road','Golf Course Rd','Udyog Vihar',
          'Sector 14-17','South City','Manesar','Aurangabad','Patna','Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Book a Service — FixGrid</title>
<meta name="description" content="Book a verified professional in under 2 minutes. 60-minute response across Gurgaon NCR and Bihar.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<?php include __DIR__ . '/partials/pixel.php'; ?>
</head>
<body>

<nav class="nav" id="mainNav">
  <div class="nav-wrap">
    <a href="index.php" class="nav-logo"><span class="logo-text">Fix<span>Grid</span></span></a>
    <div class="nav-links">
      <?php foreach ($navLinks as $nl): ?>
        <a href="<?= h($nl['href']) ?>"><?= h($nl['label']) ?></a>
      <?php endforeach; ?>
      <a href="contact.php" class="nav-book active"><?= h($s['hp_nav_cta']) ?></a>
    </div>
    <button class="hamburger" onclick="toggleMob()" id="hamBtn" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>
<nav class="mob-nav" id="mobNav">
  <?php foreach ($navLinks as $nl): ?>
    <a href="<?= h($nl['href']) ?>" onclick="closeMob()"><?= h($nl['label']) ?></a>
  <?php endforeach; ?>
  <a href="contact.php" class="mob-book" onclick="closeMob()"><?= h($s['hp_nav_cta']) ?></a>
</nav>

<!-- PAGE HERO -->
<section class="page-hero">
  <div class="container">
    <div class="breadcrumb">
      <a href="index.php">Home</a>
      <span>›</span>
      <span class="current">Book a Service</span>
    </div>
    <h1>Book a <em>Technician</em></h1>
    <p>Fill in the form below and we'll connect you with a verified professional within 60 minutes.</p>
  </div>
</section>

<!-- CONTACT GRID -->
<section class="section" style="background:#fff">
  <div class="container">
    <div class="contact-grid">

      <!-- Contact Info -->
      <div class="contact-info reveal">
        <h2>Get in Touch</h2>
        <p>Have a question or need a custom quote? Reach us through any of these channels — we're available 7 days a week.</p>

        <div class="contact-methods">
          <a href="tel:<?= h($s['company_phone']) ?>" class="contact-method" style="text-decoration:none">
            <div class="cm-icon">📞</div>
            <div>
              <div class="cm-label">Call Us</div>
              <div class="cm-value"><?= h($s['company_phone']) ?></div>
            </div>
          </a>
          <a href="mailto:<?= h($s['company_email']) ?>" class="contact-method" style="text-decoration:none">
            <div class="cm-icon">✉️</div>
            <div>
              <div class="cm-label">Email Us</div>
              <div class="cm-value"><?= h($s['company_email']) ?></div>
            </div>
          </a>
          <a href="https://wa.me/<?= h($s['hp_wa_number']) ?>" class="contact-method" style="text-decoration:none" target="_blank">
            <div class="cm-icon">💬</div>
            <div>
              <div class="cm-label">WhatsApp</div>
              <div class="cm-value">Chat with us instantly</div>
            </div>
          </a>
        </div>

        <!-- Trust badges -->
        <div style="margin-top:32px;padding:20px;background:var(--light);border-radius:14px">
          <div style="font-family:'Poppins',sans-serif;font-size:13px;font-weight:700;color:var(--blue);margin-bottom:12px">Why Book with FixGrid?</div>
          <?php foreach ([
            ['✅','Verified professionals only'],
            ['⚡','60-minute response time'],
            ['💰','Transparent, upfront pricing'],
            ['⭐','7-day service guarantee'],
            ['🧾','GST invoices available'],
          ] as $badge): ?>
            <div style="display:flex;align-items:center;gap:10px;font-size:13px;color:var(--text);margin-bottom:7px">
              <span><?= $badge[0] ?></span><span><?= h($badge[1]) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Booking Form -->
      <div class="contact-form-wrap reveal">
        <h3>⚡ Book a Technician</h3>

        <div class="form-row">
          <div class="form-group">
            <label>Your Name *</label>
            <input type="text" id="cName" placeholder="Full name">
          </div>
          <div class="form-group">
            <label>Phone Number *</label>
            <input type="tel" id="cPhone" placeholder="+91 98765 43210">
          </div>
        </div>

        <div class="form-group">
          <label>Service Required *</label>
          <select id="cService">
            <option value="">Select a service</option>
            <?php foreach ($serviceNames as $svc): ?>
              <option value="<?= h($svc) ?>" <?= $preService === h($svc) ? 'selected' : '' ?>><?= h($svc) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Your Location *</label>
          <select id="cLocation">
            <option value="">Select your area</option>
            <?php foreach ($zones as $z): ?>
              <option><?= h($z) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Preferred Date</label>
            <input type="date" id="cDate" min="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-group">
            <label>Preferred Time</label>
            <input type="time" id="cTime">
          </div>
        </div>

        <div class="form-group">
          <label>Additional Notes</label>
          <textarea id="cMessage" placeholder="Describe the issue or any special requirements..."></textarea>
        </div>

        <button class="book-btn" onclick="submitContactForm()" style="font-size:15px;padding:15px">
          ⚡ Send Booking Request via WhatsApp
        </button>

        <p style="text-align:center;font-size:11px;color:rgba(255,255,255,.4);margin-top:12px">
          By submitting, you agree to be contacted by a FixGrid representative.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- MAP PLACEHOLDER -->
<section style="background:var(--light);padding:60px 0">
  <div class="container">
    <div class="sh center reveal">
      <div class="tag">Service Areas</div>
      <h2 class="sec-title">We Cover Gurgaon & Bihar</h2>
      <p class="sec-sub">Active zones across multiple sectors. Check if we cover your area.</p>
    </div>
    <div class="zones-grid reveal" style="margin-top:32px">
      <?php foreach ([
        ['name'=>'Cyber City','color'=>'green'],['name'=>'MG Road','color'=>'green'],
        ['name'=>'Sohna Road','color'=>'green'],['name'=>'Golf Course Rd','color'=>'green'],
        ['name'=>'Udyog Vihar','color'=>'green'],['name'=>'Sector 14-17','color'=>'green'],
        ['name'=>'South City','color'=>'green'],['name'=>'Manesar','color'=>'green'],
        ['name'=>'Aurangabad','color'=>'yellow'],['name'=>'Patna','color'=>'yellow'],
      ] as $z): ?>
        <div class="zone"><div class="zdot <?= $z['color'] ?>"></div><?= h($z['name']) ?></div>
      <?php endforeach; ?>
      <div class="zone" style="border-style:dashed;opacity:.45"><div class="zdot gray"></div>More coming soon</div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="footer-inner">
    <div class="fbrand">
      <div class="fbrand-text">Fix<span>Grid</span></div>
      <p><?= h($s['hp_footer_about']) ?></p>
    </div>
    <div class="fcol">
      <h4>Services</h4>
      <?php foreach (array_slice($services, 0, 6) as $svc): ?>
        <a href="services.php"><?= h($svc['name']) ?></a>
      <?php endforeach; ?>
    </div>
    <div class="fcol">
      <h4>Company</h4>
      <a href="index.php">Home</a>
      <a href="services.php">Services</a>
      <a href="about.php">About Us</a>
      <a href="index.php#corporate">Corporate / AMC</a>
    </div>
    <div class="fcol">
      <h4>Contact</h4>
      <a href="tel:<?= h($s['company_phone']) ?>"><?= h($s['company_phone']) ?></a>
      <a href="mailto:<?= h($s['company_email']) ?>"><?= h($s['company_email']) ?></a>
      <a href="https://wa.me/<?= h($s['hp_wa_number']) ?>">💬 WhatsApp</a>
    </div>
  </div>
  <div class="foot-bot">
    <div class="fcopy"><?= h($s['hp_footer_copy']) ?></div>
    <div class="ftagline"><?= h($s['hp_footer_tagline']) ?></div>
  </div>
</footer>

<button id="backToTop" style="position:fixed;bottom:24px;right:24px;width:44px;height:44px;border-radius:50%;background:var(--blue);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:18px;box-shadow:0 4px 16px rgba(0,0,0,.2);transition:opacity .3s;opacity:0;pointer-events:none;z-index:200">↑</button>

<script src="main.js"></script>
<script>
// Extend submitContactForm for this page
function submitContactForm() {
  var name = document.getElementById('cName').value.trim();
  var phone = document.getElementById('cPhone').value.trim();
  var service = document.getElementById('cService').value;
  var location = document.getElementById('cLocation').value;
  var date = document.getElementById('cDate').value;
  var time = document.getElementById('cTime').value;
  var msg = document.getElementById('cMessage').value.trim();

  if (!name || !phone || !service || !location) {
    alert('Please fill your name, phone, service and location.');
    return;
  }

  var text = 'Hi FixGrid!%0A%0AName: ' + encodeURIComponent(name)
    + '%0APhone: ' + encodeURIComponent(phone)
    + '%0AService: ' + encodeURIComponent(service)
    + '%0ALocation: ' + encodeURIComponent(location);
  if (date) text += '%0ADate: ' + encodeURIComponent(date);
  if (time) text += '%0ATime: ' + encodeURIComponent(time);
  if (msg) text += '%0ANotes: ' + encodeURIComponent(msg);

  window.open('https://wa.me/<?= $s['hp_wa_number'] ?>?text=' + text, '_blank');
}
</script>
</body>
</html>

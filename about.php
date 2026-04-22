<?php
/**
 * FixGrid — About Page (about.php)
 * FIX: Replaced local h() + hardcoded $s/$navLinks with shared partials/init.php
 *      which provides h(), $s (DB-merged), $navLinks, and $services.
 */
require_once __DIR__ . '/partials/init.php';
// All pages now use h() — h() is retired
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>About FixGrid — Your Local Professional Service Network</title>
<meta name="description" content="Learn about FixGrid — a trusted network of verified professionals delivering fast, reliable home and business services across Gurgaon NCR and Bihar.">
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
        <a href="<?= h($nl['href']) ?>" <?= $nl['href']==='about.php'?'class="active"':'' ?>><?= h($nl['label']) ?></a>
      <?php endforeach; ?>
      <a href="contact.php" class="nav-book"><?= h($s['hp_nav_cta']) ?></a>
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

<!-- ABOUT HERO -->
<section class="about-hero">
  <div class="container">
    <div class="breadcrumb">
      <a href="index.php">Home</a>
      <span>›</span>
      <span class="current">About Us</span>
    </div>
    <h1>Building <em>Trust</em>, One Service at a Time</h1>
    <p>FixGrid was founded with a single mission: make professional home and business services fast, reliable and transparent for everyone.</p>
  </div>
</section>

<!-- MISSION -->
<section class="section" style="background:#fff">
  <div class="container">
    <div class="mission-grid">
      <div class="mission-text reveal">
        <div class="tag">Our Mission</div>
        <h2>Making Professional Services Accessible</h2>
        <p>We saw a problem: finding reliable, fairly-priced professional help was hard. Long waits, surprise charges, and unverified workers left customers frustrated.</p>
        <p>FixGrid changed that. We built a platform where every professional is background-checked, every price is transparent, and every booking is guaranteed.</p>
        <p>Today, we serve hundreds of homes and businesses across Gurgaon NCR and Bihar — with a 60-minute response promise and a 7-day service guarantee on every job.</p>
        <div style="margin-top:24px;display:flex;gap:12px;flex-wrap:wrap">
          <a href="contact.php" class="btn-primary">Book a Service →</a>
          <a href="services.php" class="btn-outline">View Services</a>
        </div>
      </div>
      <div class="mission-visual reveal">
        <div class="mv-item">
          <div class="mv-num">500+</div>
          <div>
            <div class="mv-title">Happy Clients Served</div>
            <div class="mv-desc">Growing family of satisfied customers across Gurgaon and Bihar trusting FixGrid every day.</div>
          </div>
        </div>
        <div class="mv-item">
          <div class="mv-num">60</div>
          <div>
            <div class="mv-title">Minutes Average Response</div>
            <div class="mv-desc">Zone-based dispatch ensures the nearest verified professional reaches you fast.</div>
          </div>
        </div>
        <div class="mv-item">
          <div class="mv-num">8+</div>
          <div>
            <div class="mv-title">Service Categories</div>
            <div class="mv-desc">From AC repair to IT support — everything you need, one platform.</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- VALUES -->
<section class="section" style="background:var(--light)">
  <div class="container">
    <div class="sh center reveal">
      <div class="tag">Our Values</div>
      <h2 class="sec-title">What Drives Us</h2>
      <p class="sec-sub">Every decision at FixGrid comes back to these core principles.</p>
    </div>
    <div class="stats-grid reveal" style="max-width:860px;margin:0 auto">
      <?php foreach ([
        ['icon'=>'🔒','title'=>'Trust First','desc'=>'Every professional is ID-verified, skill-tested and background-screened before joining our network.'],
        ['icon'=>'⚡','title'=>'Speed Matters','desc'=>'We built our dispatch system around speed. 60 minutes is the promise, not the exception.'],
        ['icon'=>'💰','title'=>'Transparent Pricing','desc'=>'Fixed quotes upfront. You know the price before you approve. Zero hidden charges, ever.'],
        ['icon'=>'⭐','title'=>'Quality Guaranteed','desc'=>'7-day service guarantee. Not happy? We send someone again at no cost.'],
      ] as $v): ?>
        <div class="stat-card" style="text-align:left;padding:28px">
          <div style="font-size:28px;margin-bottom:12px"><?= $v['icon'] ?></div>
          <div class="stat-n" style="font-size:18px;margin-bottom:6px;color:var(--blue)"><?= h($v['title']) ?></div>
          <div class="stat-l" style="font-size:13px;color:var(--muted);line-height:1.7"><?= h($v['desc']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- TEAM -->
<section class="section" style="background:#fff">
  <div class="container">
    <div class="sh center reveal">
      <div class="tag">The Team</div>
      <h2 class="sec-title">People Behind FixGrid</h2>
      <p class="sec-sub">A passionate team working to transform the home services industry.</p>
    </div>
    <div class="team-grid">
      <?php foreach ([
        ['initials'=>'PH','name'=>'PH Digital Team','role'=>'Founders & Leadership','color'=>'#0B3C5D'],
        ['initials'=>'OE','name'=>'Operations Experts','role'=>'Service Quality & Dispatch','color'=>'#1E88E5'],
        ['initials'=>'VP','name'=>'Verified Pros','role'=>'500+ Certified Technicians','color'=>'#FF6F00'],
        ['initials'=>'CS','name'=>'Customer Success','role'=>'Support & Experience','color'=>'#22C55E'],
      ] as $tm): ?>
        <div class="team-card reveal">
          <div class="team-av" style="background:<?= h($tm['color']) ?>"><?= h($tm['initials']) ?></div>
          <div class="team-name"><?= h($tm['name']) ?></div>
          <div class="team-role"><?= h($tm['role']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- JOIN CTA -->
<section style="background:var(--blue);padding:80px 0;text-align:center">
  <div class="container reveal">
    <div class="tag" style="background:rgba(255,255,255,.1);color:rgba(255,255,255,.7);border-color:rgba(255,255,255,.15)">Join Our Network</div>
    <h2 style="font-family:'Poppins',sans-serif;font-size:clamp(26px,4vw,40px);font-weight:900;color:#fff;margin-bottom:12px;letter-spacing:-.02em">Are You a Skilled Professional?</h2>
    <p style="color:rgba(255,255,255,.55);font-size:15px;margin-bottom:32px;max-width:460px;margin-left:auto;margin-right:auto">Join FixGrid's network and get consistent work, fair pay and a platform that supports your growth.</p>
    <a href="/engineer-app/engineer.php" class="btn-primary" style="font-size:15px;padding:15px 30px">Apply as a Technician →</a>
  </div>
</section>

<!-- CTA -->
<section class="cta-sec">
  <div class="container reveal">
    <h2>Experience the <em>FixGrid Difference</em></h2>
    <p>Join hundreds of happy customers who trust FixGrid for fast, reliable and transparent professional services.</p>
    <div class="cta-btns">
      <a href="contact.php" class="btn-primary" style="font-size:15px;padding:15px 30px">⚡ Book a Service Now</a>
      <a href="https://wa.me/<?= h($s['hp_wa_number']) ?>?text=Hi%20FixGrid%2C%20I%20want%20to%20know%20more" class="wa-btn">💬 WhatsApp Us</a>
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
      <?php foreach (['AC Repair','Electrician','IT Support','CCTV Installation','Plumbing','Appliance Repair'] as $svc): ?>
        <a href="services.php"><?= h($svc) ?></a>
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
      <a href="contact.php">Book Now</a>
    </div>
  </div>
  <div class="foot-bot">
    <div class="fcopy"><?= h($s['hp_footer_copy']) ?></div>
    <div class="ftagline"><?= h($s['hp_footer_tagline']) ?></div>
  </div>
</footer>

<button id="backToTop" style="position:fixed;bottom:24px;right:24px;width:44px;height:44px;border-radius:50%;background:var(--blue);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:18px;box-shadow:0 4px 16px rgba(0,0,0,.2);transition:opacity .3s;opacity:0;pointer-events:none;z-index:200">↑</button>

<script src="main.js"></script>
</body>
</html>

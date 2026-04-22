<?php
/**
 * FixGrid — Services Page (services.php)
 * FIX: Was doing require_once 'index.php' which rendered the full homepage HTML.
 *      Now uses the shared partials/init.php which provides $s, $navLinks,
 *      $services, h(), and parts() without any HTML output.
 */
require_once __DIR__ . '/partials/init.php';
// h(), parts(), $s, $navLinks, $services are now provided by init.php

$serviceData = [
  ['name'=>'AC Repair & Service','icon'=>'❄️','price'=>'From ₹499','desc'=>'Complete AC servicing, gas refill, repair and installation by certified technicians.','feats'=>['Split & window AC service','Gas charging & leak fix','Installation & uninstallation','AMC packages available']],
  ['name'=>'Electrician','icon'=>'⚡','price'=>'From ₹299','desc'=>'Licensed electricians for wiring, repairs, switches, fan installation and more.','feats'=>['Wiring & rewiring','Switch & socket repair','Fan & light installation','MCB & fuse box work']],
  ['name'=>'IT Support','icon'=>'💻','price'=>'From ₹499','desc'=>'On-site and remote IT support for homes and businesses by certified engineers.','feats'=>['PC & laptop repair','Software installation','Network troubleshooting','Data backup & recovery']],
  ['name'=>'CCTV Installation','icon'=>'📷','price'=>'From ₹2,999','desc'=>'Professional CCTV camera setup, installation and maintenance for your premises.','feats'=>['HD camera installation','DVR/NVR setup','Remote monitoring setup','Existing system repair']],
  ['name'=>'Plumbing','icon'=>'🔧','price'=>'From ₹199','desc'=>'Expert plumbers for pipe repairs, leakage fixes, tap installation and drainage.','feats'=>['Pipe leak repairs','Tap & shower fitting','Drainage unblocking','Water heater service']],
  ['name'=>'Appliance Repair','icon'=>'🏠','price'=>'From ₹399','desc'=>'Repair of washing machines, refrigerators, microwaves and all major appliances.','feats'=>['Washing machine repair','Refrigerator service','Microwave repair','Dishwasher service']],
  ['name'=>'Cleaning Services','icon'=>'🧹','price'=>'From ₹799','desc'=>'Deep cleaning, sofa cleaning, carpet cleaning and bathroom sanitization.','feats'=>['Home deep cleaning','Sofa & carpet cleaning','Kitchen cleaning','Post-construction clean']],
  ['name'=>'Networking & WiFi','icon'=>'📡','price'=>'From ₹999','desc'=>'WiFi setup, network optimization, router configuration and LAN/WAN setup.','feats'=>['WiFi setup & config','Router installation','LAN/WAN cabling','Network speed fix']],
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Services — FixGrid Professional Services Network</title>
<meta name="description" content="Explore all FixGrid services: AC repair, electrician, IT support, CCTV, plumbing, cleaning and more. Book in 2 minutes, 60-minute response.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<?php include __DIR__ . '/partials/pixel.php'; ?>
</head>
<body>

<?php
// Include navbar manually (no partial dir available in this snippet context)
?>
<nav class="nav" id="mainNav">
  <div class="nav-wrap">
    <a href="index.php" class="nav-logo">
      <span class="logo-text">Fix<span>Grid</span></span>
    </a>
    <div class="nav-links">
      <?php foreach ($navLinks as $nl): ?>
        <a href="<?= h($nl['href']) ?>" <?= $nl['href']==='services.php'?'class="active"':'' ?>><?= h($nl['label']) ?></a>
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

<!-- PAGE HERO -->
<section class="services-hero">
  <div class="container">
    <div class="breadcrumb">
      <a href="index.php">Home</a>
      <span>›</span>
      <span class="current">Services</span>
    </div>
    <h1>All Services, <em>One Platform</em></h1>
    <p>Certified professionals for every home and business need. Book in under 2 minutes — 60-minute response guaranteed.</p>
  </div>
</section>

<!-- SERVICES GRID -->
<section class="section" style="background:#fff">
  <div class="container">
    <div class="service-detail-grid">
      <?php foreach ($serviceData as $i => $svc): ?>
        <div class="service-detail-card reveal" style="animation-delay:<?= $i * 0.05 ?>s">
          <div class="sdc-head">
            <div class="sdc-icon"><?= $svc['icon'] ?></div>
            <div>
              <div class="sdc-name"><?= h($svc['name']) ?></div>
              <div class="sdc-price"><?= h($svc['price']) ?></div>
            </div>
          </div>
          <p class="sdc-desc"><?= h($svc['desc']) ?></p>
          <div class="sdc-feats">
            <?php foreach ($svc['feats'] as $feat): ?>
              <div class="sdc-feat"><?= h($feat) ?></div>
            <?php endforeach; ?>
          </div>
          <a href="contact.php?service=<?= urlencode($svc['name']) ?>" class="btn-primary" style="width:100%;justify-content:center;font-size:13px;padding:11px">Book <?= h($svc['name']) ?> →</a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- AMC SECTION -->
<section class="section" style="background:var(--light)">
  <div class="container">
    <div class="corp-grid">
      <div class="corp-text reveal">
        <div class="tag">For Businesses</div>
        <h2>Annual Maintenance Contracts</h2>
        <p>Save time and money with our comprehensive AMC packages. Get priority service, fixed costs and dedicated account management for your business.</p>
        <div class="corp-pts">
          <div class="corp-pt"><div class="cpchk">✓</div>Dedicated account manager</div>
          <div class="corp-pt"><div class="cpchk">✓</div>Priority 2-hour response SLA</div>
          <div class="corp-pt"><div class="cpchk">✓</div>GST invoices & digital reports</div>
          <div class="corp-pt"><div class="cpchk">✓</div>Verified enterprise engineers</div>
        </div>
        <a href="contact.php" class="btn-primary">Get AMC Quote →</a>
      </div>
      <div class="corp-card reveal">
        <div class="corp-ctag">Enterprise Ready</div>
        <h3>What's Included in AMC</h3>
        <p>One agreement, fixed monthly cost, complete coverage for all your facility needs.</p>
        <div class="corp-items">
          <div class="citem">IT Support</div>
          <div class="citem">CCTV Maintenance</div>
          <div class="citem">Network Upkeep</div>
          <div class="citem">Server Monitoring</div>
          <div class="citem">AC Servicing</div>
          <div class="citem">Electrical Checks</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-sec">
  <div class="container reveal">
    <h2>Ready to <em>Book a Service?</em></h2>
    <p>Choose any service and get a verified professional at your doorstep within 60 minutes.</p>
    <div class="cta-btns">
      <a href="contact.php" class="btn-primary" style="font-size:15px;padding:15px 30px">⚡ Book a Technician Now</a>
      <a href="https://wa.me/<?= h($s['hp_wa_number']) ?>?text=Hi%20FixGrid%2C%20I%20need%20a%20service" class="wa-btn">💬 WhatsApp Us</a>
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
      <?php foreach (array_slice($serviceData, 0, 6) as $sv): ?>
        <a href="services.php"><?= h($sv['name']) ?></a>
      <?php endforeach; ?>
    </div>
    <div class="fcol">
      <h4>Company</h4>
      <a href="index.php">Home</a>
      <a href="index.php#how">How it Works</a>
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

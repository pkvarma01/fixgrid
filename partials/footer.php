<?php
/**
 * FixGrid — Footer Partial (partials/footer.php)
 * Requires partials/init.php to be loaded first (provides $s, $services, h())
 *
 * FIX: Removed standalone fh() definition — now uses shared h() from init.php
 */
if (!isset($s)) {
    $s = [
        'hp_footer_about'   => 'Your Local Professional Service Network. Verified professionals for every home and business need across Bihar and Gurgaon NCR.',
        'hp_footer_tagline' => '⚡ Fast. Reliable. On-Demand.',
        'hp_footer_copy'    => '© 2026 FixGrid. All rights reserved. Managed by PH Digital Technology Services Pvt Ltd',
        'company_phone'     => '+919810519169',
        'company_email'     => 'support@fixgrid.in',
        'hp_wa_number'      => '919810519169',
    ];
}
if (!isset($services)) $services = [];
?>
<footer>
  <div class="footer-inner">
    <div class="fbrand">
      <div class="fbrand-text">Fix<span>Grid</span></div>
      <p><?= h($s['hp_footer_about']) ?></p>
    </div>
    <div class="fcol">
      <h4>Services</h4>
      <?php
      $footerServices = $services ?: [
        ['name' => 'AC Repair'], ['name' => 'Electrician'], ['name' => 'IT Support'],
        ['name' => 'CCTV Installation'], ['name' => 'Plumbing'], ['name' => 'Appliance Repair'],
      ];
      foreach (array_slice($footerServices, 0, 6) as $sv): ?>
        <a href="services.php"><?= h($sv['name']) ?></a>
      <?php endforeach; ?>
    </div>
    <div class="fcol">
      <h4>Company</h4>
      <a href="index.php">Home</a>
      <a href="index.php#how">How it Works</a>
      <a href="about.php">About Us</a>
      <a href="index.php#corporate">Corporate / AMC</a>
      <a href="/engineer-app/engineer.php">Become a Professional</a>
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

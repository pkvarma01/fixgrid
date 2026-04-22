<?php
/**
 * FixGrid — Navbar Partial (partials/navbar.php)
 * Requires partials/init.php to be loaded first ($navLinks, $s, h())
 */
if (!isset($navLinks)) {
    $navLinks = [
        ['label' => 'Services',    'href' => 'services.php'],
        ['label' => 'How it Works','href' => 'index.php#how'],
        ['label' => 'Areas',       'href' => 'index.php#zones'],
        ['label' => 'Corporate',   'href' => 'index.php#corporate'],
        ['label' => 'About',       'href' => 'about.php'],
    ];
}
if (!isset($s)) $s = ['hp_nav_cta' => '⚡ Book Now'];
?>
<nav class="nav" id="mainNav">
  <div class="nav-wrap">
    <a href="index.php" class="nav-logo">
      <img src="/logo.png" alt="FixGrid" class="logo-img">
      <span class="logo-text">Fix<span>Grid</span></span>
    </a>
    <div class="nav-links">
      <?php foreach ($navLinks as $nl): ?>
        <a href="<?= h($nl['href']) ?>"><?= h($nl['label']) ?></a>
      <?php endforeach; ?>
      <a href="contact.php" class="nav-book"><?= h($s['hp_nav_cta'] ?? '⚡ Book Now') ?></a>
    </div>
    <button class="hamburger" onclick="toggleMob()" id="hamBtn" aria-label="Menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>

<!-- Mobile nav backdrop (click to close) -->
<div class="mob-backdrop" id="mobBackdrop" onclick="closeMob()"></div>

<!-- Mobile nav drawer -->
<nav class="mob-nav" id="mobNav" aria-label="Mobile navigation">
  <?php foreach ($navLinks as $nl): ?>
    <a href="<?= h($nl['href']) ?>" onclick="closeMob()"><?= h($nl['label']) ?></a>
  <?php endforeach; ?>
  <a href="contact.php" class="mob-book" onclick="closeMob()"><?= h($s['hp_nav_cta'] ?? '⚡ Book Now') ?></a>
</nav>

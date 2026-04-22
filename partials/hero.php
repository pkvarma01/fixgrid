<?php
/**
 * FixGrid — Marquee + Hero Section (partials/hero.php)
 * Requires: $s, $services, $marqueeItems — provided by partials/init.php
 */
?>

<!-- MARQUEE (sits between navbar and hero) -->
<div class="marquee">
  <div class="marquee-track">
    <?php $mi = array_merge($marqueeItems, $marqueeItems); foreach ($mi as $m): ?>
      <div class="mitem"><?= h($m) ?></div>
    <?php endforeach; ?>
  </div>
</div>

<!-- HERO -->
<section class="hero" id="home">
  <div class="hero-bg-grid"></div>
  <div class="hero-glow hero-glow-1"></div>
  <div class="hero-glow hero-glow-2"></div>
  <div class="hero-inner">
    <div class="hero-lhs reveal">
      <div class="hero-badge">
        <div class="hero-pulse"></div>
        <?= h($s['hp_hero_badge']) ?>
      </div>
      <h1 class="hero-title">
        <?= h($s['hp_hero_title1']) ?><br>
        <?= h($s['hp_hero_title2']) ?><br>
        <em><?= h($s['hp_hero_title3']) ?></em>
      </h1>
      <p class="hero-desc"><?= h($s['hp_hero_subtitle']) ?></p>
      <div class="hero-btns">
        <a href="#contact" class="btn-primary"><?= h($s['hp_hero_btn1']) ?></a>
        <a href="services.php" class="btn-ghost"><?= h($s['hp_hero_btn2']) ?> →</a>
      </div>
      <div class="hero-trust">
        <?php foreach (['hp_hero_trust1','hp_hero_trust2','hp_hero_trust3'] as $tk): ?>
          <div class="trust-chip"><div class="trust-tick">✓</div><?= h($s[$tk]) ?></div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Booking Card -->
    <div class="hero-card" id="contact">
      <div class="book-pill">FREE QUOTE · 60 MIN RESPONSE</div>
      <div class="book-title">Book a Technician</div>
      <div class="frow">
        <div class="fg"><label>Your Name</label><input type="text" id="bName" placeholder="Full name"></div>
        <div class="fg"><label>Phone</label><input type="tel" id="bPhone" placeholder="+91 98765 43210"></div>
      </div>
      <div class="fg">
        <label>Service Required</label>
        <select id="bService">
          <option value="">Select a service</option>
          <?php foreach ($services as $sv): ?>
            <option><?= h($sv['name']) ?></option>
          <?php endforeach; ?>
          <option>AMC Contract</option>
          <option>Other</option>
        </select>
      </div>
      <div class="fg"><label>Your Location</label><input type="text" id="bLoc" placeholder="Area / Sector / City"></div>
      <div class="fg"><label>Preferred Time</label><input type="text" id="bTime" placeholder="e.g. Today 3PM, Tomorrow morning"></div>
      <button class="book-btn" onclick="submitBook()">⚡ Book Now — Get Free Quote</button>
      <div class="book-stats">
        <div><div class="bstat-n"><?= h($s['hp_hero_stat1_num']) ?></div><div class="bstat-l"><?= h($s['hp_hero_stat1_lbl']) ?></div></div>
        <div><div class="bstat-n"><?= h($s['hp_hero_stat2_num']) ?></div><div class="bstat-l"><?= h($s['hp_hero_stat2_lbl']) ?></div></div>
        <div><div class="bstat-n"><?= h($s['hp_hero_stat3_num']) ?></div><div class="bstat-l"><?= h($s['hp_hero_stat3_lbl']) ?></div></div>
      </div>
    </div>
  </div>
</section>

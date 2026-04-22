<?php
/**
 * FixGrid — Testimonials Section (partials/testimonials.php)
 * Requires: $s, $testis — provided by partials/init.php
 */
?>
<!-- TESTIMONIALS -->
<section class="section" style="background:var(--light)">
  <div class="container">
    <div class="sh center reveal">
      <div class="tag"><?= h($s['hp_testi_tag']) ?></div>
      <h2 class="sec-title"><?= h($s['hp_testi_title']) ?></h2>
      <p class="sec-sub"><?= h($s['hp_testi_sub']) ?></p>
    </div>
    <div class="testi-grid reveal">
      <?php foreach ($testis as $t): ?>
        <div class="testi">
          <div class="testi-stars">★★★★★</div>
          <p class="testi-text">"<?= h($t['text']) ?>"</p>
          <div class="testi-author">
            <div class="testi-av" style="background:<?= h($t['color']) ?>"><?= h($t['initials']) ?></div>
            <div>
              <div class="testi-name"><?= h($t['name']) ?></div>
              <div class="testi-loc">📍 <?= h($t['location']) ?></div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php
/**
 * FixGrid — CTA Banner Section (partials/cta.php)
 * Requires: $s — provided by partials/init.php
 */
?>
<!-- CTA -->
<section class="cta-sec">
  <div class="container reveal">
    <h2><?= h($s['hp_cta_title1']) ?> <em><?= h($s['hp_cta_title2']) ?></em></h2>
    <p><?= h($s['hp_cta_sub']) ?></p>
    <div class="cta-btns">
      <a href="#contact" class="btn-primary" style="font-size:15px;padding:15px 30px"><?= h($s['hp_cta_btn']) ?></a>
      <a href="https://wa.me/<?= h($s['hp_wa_number']) ?>?text=Hi%20FixGrid%2C%20I%20need%20a%20service" class="wa-btn"><?= h($s['hp_cta_wa_btn']) ?></a>
    </div>
  </div>
</section>

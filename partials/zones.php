<?php
/**
 * FixGrid — Service Zones Section (partials/zones.php)
 * Requires: $s, $zones — provided by partials/init.php
 *
 * FIX: Zone data has 3 fields (name|color|city). The 'city' field is now
 *      captured in index.php and displayed here as a subtle label.
 */
?>
<!-- ZONES -->
<section class="section" id="zones" style="background:var(--light);padding-top:60px;padding-bottom:60px">
  <div class="container">
    <div class="sh reveal">
      <div class="tag"><?= h($s['hp_zones_tag']) ?></div>
      <h2 class="sec-title"><?= h($s['hp_zones_title']) ?></h2>
      <p class="sec-sub"><?= h($s['hp_zones_sub']) ?></p>
    </div>
    <div class="zones-grid reveal">
      <?php foreach ($zones as $z):
        $cls = in_array($z['color'], ['green','yellow','red','blue','gray']) ? $z['color'] : 'green'; ?>
        <div class="zone">
          <div class="zdot <?= $cls ?>"></div>
          <?= h($z['name']) ?>
          <?php if (!empty($z['city'])): ?>
            <span style="opacity:.45;font-size:11px;margin-left:4px">— <?= h($z['city']) ?></span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <div class="zone" style="border-style:dashed;opacity:.45"><div class="zdot gray"></div>More coming soon</div>
    </div>
  </div>
</section>

<?php
/**
 * FixGrid — Why FixGrid + Stats Section (partials/why_fixgrid.php)
 * Requires: $s, $whyFeats — provided by partials/init.php
 */
?>
<!-- WHY FIXGRID -->
<section class="section" style="background:#fff">
  <div class="container">
    <div class="why-grid">
      <div class="why-panel reveal">
        <h2><?= h($s['hp_why_title']) ?></h2>
        <div class="why-panel-sub"><?= h($s['hp_why_sub']) ?></div>
        <?php foreach ($whyFeats as $wf): ?>
          <div class="why-feat">
            <div class="wfi"><?= $wf['icon'] ?></div>
            <div>
              <div class="wft"><?= h($wf['title']) ?></div>
              <div class="wfd"><?= h($wf['desc']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="stats-grid reveal">
        <div class="stat-card dark"><div class="stat-n"><?= h($s['hp_stat1_num']) ?></div><div class="stat-l"><?= h($s['hp_stat1_lbl']) ?></div></div>
        <div class="stat-card"><div class="stat-n or"><?= h($s['hp_stat2_num']) ?></div><div class="stat-l"><?= h($s['hp_stat2_lbl']) ?></div></div>
        <div class="stat-card"><div class="stat-n"><?= h($s['hp_stat3_num']) ?></div><div class="stat-l"><?= h($s['hp_stat3_lbl']) ?></div></div>
        <div class="stat-card dark"><div class="stat-n"><?= h($s['hp_stat4_num']) ?></div><div class="stat-l"><?= h($s['hp_stat4_lbl']) ?></div></div>
      </div>
    </div>
  </div>
</section>

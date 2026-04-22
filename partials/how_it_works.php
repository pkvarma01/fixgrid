<?php
/**
 * FixGrid — How It Works Section (partials/how_it_works.php)
 * Requires: $s, $steps — provided by partials/init.php
 *
 * FIX: This file was placed in the root directory (how_it_works.php) instead of
 *      partials/. index.php was including partials/how_it_works.php which did not
 *      exist, causing the "How It Works" section to be completely missing from the
 *      homepage. Move/copy this file to partials/how_it_works.php to fix it.
 */
?>
<!-- HOW IT WORKS -->
<section class="section" id="how" style="background:var(--light)">
  <div class="container">
    <div class="sh center reveal">
      <div class="tag"><?= h($s['hp_how_tag']) ?></div>
      <h2 class="sec-title"><?= h($s['hp_how_title']) ?></h2>
      <p class="sec-sub"><?= h($s['hp_how_sub']) ?></p>
    </div>
    <div class="steps reveal">
      <?php foreach ($steps as $i => $st): ?>
        <div class="step">
          <div class="step-n"><?= $i + 1 ?></div>
          <div class="step-t"><?= h($st['title']) ?></div>
          <p class="step-d"><?= h($st['desc']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

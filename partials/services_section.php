<?php
/**
 * FixGrid — Services Section (partials/services_section.php)
 * Requires: $s, $services — provided by partials/init.php
 */
?>
<!-- SERVICES -->
<section class="section" id="services" style="background:#fff">
  <div class="container">
    <div class="sh reveal">
      <div class="tag"><?= h($s['hp_services_tag']) ?></div>
      <h2 class="sec-title"><?= h($s['hp_services_title']) ?></h2>
      <p class="sec-sub"><?= h($s['hp_services_sub']) ?></p>
    </div>
    <div class="svc-grid reveal">
      <?php foreach ($services as $sv): ?>
        <a class="svc-card" href="services.php" onclick="pickService('<?= h(addslashes($sv['name'])) ?>')">
          <div class="svc-icon"><?= $sv['icon'] ?></div>
          <div class="svc-name"><?= h($sv['name']) ?></div>
          <div class="svc-price"><?= h($sv['price']) ?></div>
        </a>
      <?php endforeach; ?>
    </div>
    <div style="text-align:center;margin-top:32px">
      <a href="services.php" class="btn-outline">View All Services →</a>
    </div>
  </div>
</section>

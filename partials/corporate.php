<?php
/**
 * FixGrid — Corporate & AMC Section (partials/corporate.php)
 * Requires: $s, $corpPoints, $corpItems — provided by partials/init.php
 */
?>
<!-- CORPORATE -->
<section class="section" id="corporate" style="background:#fff">
  <div class="container">
    <div class="corp-grid">
      <div class="corp-text reveal">
        <div class="tag"><?= h($s['hp_corp_tag']) ?></div>
        <h2><?= h($s['hp_corp_title']) ?></h2>
        <p><?= h($s['hp_corp_sub']) ?></p>
        <div class="corp-pts">
          <?php foreach ($corpPoints as $pt): ?>
            <div class="corp-pt"><div class="cpchk">✓</div><?= h($pt) ?></div>
          <?php endforeach; ?>
        </div>
        <a href="contact.php" class="btn-primary" style="font-size:14px;padding:12px 22px">Request Corporate Proposal →</a>
      </div>
      <div class="corp-card reveal">
        <div class="corp-ctag"><?= h($s['hp_corp_card_tag']) ?></div>
        <h3><?= h($s['hp_corp_card_title']) ?></h3>
        <p><?= h($s['hp_corp_card_sub']) ?></p>
        <div class="corp-items">
          <?php foreach ($corpItems as $ci): ?>
            <div class="citem"><?= h($ci) ?></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

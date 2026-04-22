<?php
/**
 * FixGrid — App Download Section (partials/app_section.php)
 * Requires: $s, $services, $appFeats — provided by partials/init.php
 */
?>
<!-- APP SECTION -->
<section class="app-sec" id="app">
  <div class="app-inner">
    <div class="app-lhs reveal">
      <div class="tag" style="background:rgba(255,255,255,.08);color:rgba(255,255,255,.6);border-color:rgba(255,255,255,.12)"><?= h($s['hp_app_tag']) ?></div>
      <h2><?= h($s['hp_app_title1']) ?><br><em><?= h($s['hp_app_title2']) ?></em></h2>
      <p><?= h($s['hp_app_sub']) ?></p>
      <div class="app-feats">
        <?php foreach ($appFeats as $af): ?>
          <div class="app-feat"><div class="af-tick">✓</div><?= h($af) ?></div>
        <?php endforeach; ?>
      </div>
      <div class="store-btns">
        <a class="store-btn" href="<?= h($s['hp_app_store_url']) ?>">
          <div class="store-i">📱</div>
          <div><span class="store-sub">Download on the</span><span class="store-nm">App Store</span></div>
        </a>
        <a class="store-btn" href="<?= h($s['hp_app_play_url']) ?>">
          <div class="store-i">🤖</div>
          <div><span class="store-sub">Get it on</span><span class="store-nm">Google Play</span></div>
        </a>
      </div>
    </div>
    <div class="phone-wrap reveal">
      <div class="phone">
        <div class="pscreen">
          <div class="pbar"><div class="pnotch"></div></div>
          <div class="pbody">
            <div class="p-logo">
              <div style="font-family:'Poppins',sans-serif;font-weight:900;font-size:18px;color:#fff;letter-spacing:-.02em">Fix<span style="color:#FF6F00">Grid</span></div>
            </div>
            <div class="p-search">🔍 Search for a service...</div>
            <div style="font-size:7.5px;color:rgba(255,255,255,.32);margin-bottom:6px;font-weight:700;letter-spacing:.06em">POPULAR SERVICES</div>
            <div class="p-cats">
              <?php foreach (array_slice($services, 0, 4) as $sv): ?>
                <div class="p-cat">
                  <div class="p-cat-i"><?= $sv['icon'] ?></div>
                  <div class="p-cat-n"><?= h(mb_substr($sv['name'], 0, 8)) ?></div>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="p-btn">⚡ Book Now — 60 min</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

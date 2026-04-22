<?php
/**
 * FixGrid — Homepage (index.php)
 *
 * Structure:
 *   partials/init.php          — DB settings, h(), parts(), $s, $navLinks, $services
 *   partials/navbar.php        — Top navigation
 *   partials/hero.php          — Hero banner + booking card + marquee strip
 *   partials/services_section.php — Service cards grid
 *   partials/how_it_works.php  — Step-by-step process
 *   partials/why_fixgrid.php   — Why us + stats
 *   partials/zones.php         — Service area zones
 *   partials/corporate.php     — Corporate / AMC section
 *   partials/testimonials.php  — Customer reviews
 *   partials/app_section.php   — Mobile app download
 *   partials/cta.php           — Bottom CTA banner
 *   partials/footer.php        — Footer
 *   style.css                  — All styles
 *   main.js                    — All scripts
 */

require_once __DIR__ . '/partials/init.php';

// ── Extra parsed data needed only on the homepage ─────────────────────────
$stParts  = parts($s['hp_how_steps']);
$steps    = [];
for ($i = 0; $i + 1 < count($stParts); $i += 2)
    $steps[] = ['title' => $stParts[$i], 'desc' => $stParts[$i + 1]];

$wfParts   = parts($s['hp_why_feats']);
$whyFeats  = [];
for ($i = 0; $i + 2 < count($wfParts); $i += 3)
    $whyFeats[] = ['icon' => $wfParts[$i], 'title' => $wfParts[$i + 1], 'desc' => $wfParts[$i + 2]];

$znParts = parts($s['hp_zones_list']);
$zones   = [];
for ($i = 0; $i < count($znParts); $i += 3)
    $zones[] = ['name' => $znParts[$i], 'color' => $znParts[$i + 1] ?? 'green'];

$corpPoints   = parts($s['hp_corp_points']);
$corpItems    = parts($s['hp_corp_items']);

$teParts = parts($s['hp_testi_list']);
$testis  = [];
for ($i = 0; $i + 4 < count($teParts); $i += 5)
    $testis[] = [
        'name'     => $teParts[$i],
        'initials' => $teParts[$i + 1],
        'color'    => $teParts[$i + 2],
        'location' => $teParts[$i + 3],
        'text'     => $teParts[$i + 4],
    ];

$appFeats     = parts($s['hp_app_feats']);
$marqueeItems = parts($s['hp_marquee']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= h($s['hp_seo_title']) ?></title>
<meta name="description" content="<?= h($s['hp_seo_desc']) ?>">
<link rel="icon" type="image/png" href="/logo.png">
<meta name="theme-color" content="#0B3C5D">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<?php include __DIR__ . '/partials/pixel.php'; ?>
</head>
<body>

<?php include __DIR__ . '/partials/navbar.php'; ?>
<?php include __DIR__ . '/partials/hero.php'; ?>
<?php include __DIR__ . '/partials/services_section.php'; ?>
<?php include __DIR__ . '/partials/how_it_works.php'; ?>
<?php include __DIR__ . '/partials/why_fixgrid.php'; ?>
<?php include __DIR__ . '/partials/zones.php'; ?>
<?php include __DIR__ . '/partials/corporate.php'; ?>
<?php include __DIR__ . '/partials/testimonials.php'; ?>
<?php include __DIR__ . '/partials/app_section.php'; ?>
<?php include __DIR__ . '/partials/cta.php'; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>

<button id="backToTop" style="position:fixed;bottom:24px;right:24px;width:44px;height:44px;border-radius:50%;background:var(--blue);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:18px;box-shadow:0 4px 16px rgba(0,0,0,.2);transition:opacity .3s;opacity:0;pointer-events:none;z-index:200">↑</button>

<script src="main.js"></script>
</body>
</html>

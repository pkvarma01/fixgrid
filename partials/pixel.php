<?php
/**
 * FixGrid — Meta (Facebook) Pixel Tracking (partials/pixel.php)
 *
 * Include this file at the END of every page's <head> section:
 *   <?php include __DIR__ . '/partials/pixel.php'; ?>   (from root pages)
 *   <?php include dirname(__DIR__) . '/partials/pixel.php'; ?>  (from sub-folders)
 *
 * To update your Pixel ID: change FIXGRID_PIXEL_ID below. One place, all pages.
 *
 * Standard events fired automatically:
 *   PageView     — every page load
 *   ViewContent  — services, about, contact pages
 *   Lead         — when booking form is submitted (WhatsApp redirect)
 *   Contact      — contact page form submission
 *
 * To fire a custom event from JS anywhere on the site:
 *   fbq('track', 'Lead', { content_name: 'AC Repair', content_category: 'Booking' });
 */

// ── Your Meta Pixel ID ────────────────────────────────────────────────────
// Get this from: Meta Business Suite → Events Manager → Pixels → Your Pixel → ID
define('FIXGRID_PIXEL_ID', '825832546652945');  // ← Replace with your actual Pixel ID

// Don't load pixel if ID is not yet configured
if (FIXGRID_PIXEL_ID === 'YOUR_PIXEL_ID_HERE') return;

// Detect current page for ViewContent event
$_pixel_page = basename($_SERVER['PHP_SELF'] ?? '', '.php');
$_pixel_events = ['PageView'];  // Always fire PageView

// Fire ViewContent on key pages
$_pixel_content_map = [
    'index'    => ['name' => 'Homepage',         'category' => 'Home'],
    'services' => ['name' => 'Services Page',    'category' => 'Services'],
    'about'    => ['name' => 'About Us',         'category' => 'About'],
    'contact'  => ['name' => 'Book a Technician','category' => 'Booking'],
];
if (isset($_pixel_content_map[$_pixel_page])) {
    $_pixel_events[] = 'ViewContent:' . json_encode($_pixel_content_map[$_pixel_page]);
}
?>
<!-- Meta Pixel Code — FixGrid -->
<script>
!function(f,b,e,v,n,t,s){
  if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
  n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];
  s.parentNode.insertBefore(t,s)
}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');

fbq('init', '<?= htmlspecialchars(FIXGRID_PIXEL_ID) ?>');
fbq('track', 'PageView');
<?php foreach ($_pixel_events as $ev): if ($ev === 'PageView') continue; ?>
<?php [$ename, $edata] = explode(':', $ev, 2); ?>
fbq('track', '<?= $ename ?>', <?= $edata ?>);
<?php endforeach; ?>
</script>
<noscript>
  <img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=<?= htmlspecialchars(FIXGRID_PIXEL_ID) ?>&ev=PageView&noscript=1"/>
</noscript>
<!-- End Meta Pixel Code -->

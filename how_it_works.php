<?php
/**
 * FixGrid — how_it_works.php (root)
 *
 * FIX: This file previously called h() without loading init.php, causing a
 *      fatal error when accessed directly. The actual partial content has been
 *      moved to partials/how_it_works.php (where index.php expects it).
 *      This root file now redirects to the homepage How It Works section.
 */
header('Location: index.php#how', true, 301);
exit;

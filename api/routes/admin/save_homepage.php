<?php
// routes/admin/save_homepage.php — Admin: save homepage content
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();

$allowed_prefixes = ['hp_'];
$allowed_keys = array_keys([
    'hp_hero_badge','hp_hero_title1','hp_hero_title2','hp_hero_title3','hp_hero_subtitle',
    'hp_hero_btn1','hp_hero_btn2','hp_hero_trust1','hp_hero_trust2','hp_hero_trust3',
    'hp_hero_stat1_num','hp_hero_stat1_lbl','hp_hero_stat2_num','hp_hero_stat2_lbl',
    'hp_hero_stat3_num','hp_hero_stat3_lbl',
    'hp_marquee',
    'hp_services_tag','hp_services_title','hp_services_sub','hp_services_list',
    'hp_how_tag','hp_how_title','hp_how_sub','hp_how_steps',
    'hp_why_title','hp_why_sub','hp_why_feats',
    'hp_stat1_num','hp_stat1_lbl','hp_stat2_num','hp_stat2_lbl','hp_stat3_num','hp_stat3_lbl','hp_stat4_num','hp_stat4_lbl',
    'hp_zones_tag','hp_zones_title','hp_zones_sub','hp_zones_list',
    'hp_corp_tag','hp_corp_title','hp_corp_sub','hp_corp_points',
    'hp_corp_card_tag','hp_corp_card_title','hp_corp_card_sub','hp_corp_items',
    'hp_testi_tag','hp_testi_title','hp_testi_sub','hp_testi_list',
    'hp_app_tag','hp_app_title1','hp_app_title2','hp_app_sub','hp_app_feats',
    'hp_app_store_url','hp_app_play_url',
    'hp_cta_title1','hp_cta_title2','hp_cta_sub','hp_cta_btn','hp_cta_wa_btn',
    'hp_wa_number',
    'hp_footer_about','hp_footer_tagline','hp_footer_copy',
    'hp_nav_links','hp_nav_cta',
    'hp_seo_title','hp_seo_desc',
]);

try {
    $stmt = $db->prepare("INSERT INTO app_settings (setting_key, setting_value)
        VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_at=NOW()");
    $saved = 0;
    foreach ($input as $key => $val) {
        if (strpos($key, 'hp_') === 0) {
            $stmt->execute([$key, $val]);
            $saved++;
        }
    }
    jsonResponse(true, ['saved' => $saved], "$saved homepage settings saved");
} catch (Exception $e) {
    jsonResponse(false, null, 'DB error: ' . $e->getMessage(), 500);
}

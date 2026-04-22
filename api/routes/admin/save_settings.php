<?php
// routes/admin/save_settings.php — Save app settings to DB
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();

$allowed = [
    'company_name','company_phone','company_email','currency','currency_symbol',
    'gps_interval','assign_radius_km','otp_expiry_min','platform_charge_pct',
    'payment_gateway','google_maps_key',
    // WhatsApp
    'whatsapp_provider','whatsapp_enabled',
    'twilio_account_sid','twilio_auth_token','twilio_whatsapp_from',
    'meta_wa_token','meta_wa_phone_id','meta_wa_template',
    // Email SMTP
    'smtp_enabled','smtp_host','smtp_port','smtp_user','smtp_pass',
    'smtp_from_email','smtp_from_name',
    // FCM
    'fcm_enabled','fcm_project_id','fcm_web_api_key','fcm_sender_id','fcm_app_id','fcm_vapid_key','fcm_web_api_key','fcm_messaging_sender_id','fcm_app_id','fcm_vapid_key',
    // Note: fcm_service_account_json saved separately via /admin/save-fcm route
    'fcm_server_key',
    // Razorpay
    'razorpay_key_id', 'razorpay_key_secret',
    // Sandbox.co.in KYC
    'sandbox_api_key', 'sandbox_api_secret', 'sandbox_auth_token', 'sandbox_base_url', 'kyc_required', 'kyc_auto_approve',
    // GST
    'company_gstin', 'company_pan', 'gst_rate', 'hsn_code',
    // Visit charge
    'visit_base_charge','visit_per_km_rate','visit_free_km','visit_max_km_charge',
];

try {
    $stmt = $db->prepare("INSERT INTO app_settings (setting_key, setting_value)
        VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_at=NOW()");

    $saved = 0;
    foreach ($allowed as $key) {
        if (array_key_exists($key, $input)) {
            $val = $input[$key];
            // Skip placeholder values that shouldn't overwrite real secrets
            if (in_array($val, ['***configured***','***set***','***active***'])) continue;
            if (in_array($key, ['smtp_pass','twilio_auth_token','meta_wa_token','fcm_server_key','sandbox_api_key','sandbox_api_secret','sandbox_auth_token']) && $val === '') {
                continue;
            }
            $stmt->execute([$key, $val]);
            $saved++;
        }
    }
    jsonResponse(true, ['saved' => $saved], "$saved settings saved");
} catch (Exception $e) {
    error_log("[save_settings] Error: " . $e->getMessage());
    jsonResponse(false, null, 'Database error: ' . $e->getMessage());
}

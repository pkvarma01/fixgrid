<?php
// routes/admin/settings.php — Read settings from DB (app_settings table)
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();

// Fetch all settings into a key => value map
$rows = $db->query("SELECT setting_key, setting_value FROM app_settings")->fetchAll(PDO::FETCH_KEY_PAIR);

// Provide sensible defaults for any missing keys
$defaults = [
    'company_name'     => 'Hridya Tech',
    'company_phone'    => '',
    'company_email'    => 'admin@fsm.com',
    'currency'         => 'INR',
    'currency_symbol'  => '₹',
    'gps_interval'     => '5',
    'assign_radius_km' => '20',
    'otp_expiry_min'   => '10',
    'google_maps_key'  => '',
    'fcm_server_key'   => '',
    'fcm_web_api_key'  => '',
    'fcm_sender_id'    => '',
    'fcm_app_id'       => '',
    'fcm_vapid_key'    => '',
    'payment_gateway'    => 'cash_upi',
    'razorpay_key_id'    => '',
    'razorpay_key_secret'=> '',
    'company_gstin'      => '',
    'company_pan'        => '',
    'gst_rate'           => '0',
    'hsn_code'           => '',
    'visit_base_charge'  => '100',
    'visit_per_km_rate'  => '10',
    'visit_free_km'      => '3',
    'visit_max_km_charge'=> '200',
    // Sandbox.co.in KYC
    'sandbox_api_key'    => '',
    'sandbox_api_secret' => '',
    'sandbox_auth_token' => '',
    'sandbox_base_url'   => 'https://test-api.sandbox.co.in',
    'kyc_required'       => '1',
    'kyc_auto_approve'   => '0',
];

$settings = array_merge($defaults, $rows);

// Mask sensitive keys in response
if (!empty($settings['fcm_server_key']))    $settings['fcm_server_key']    = '***configured***';
if (!empty($settings['sandbox_api_key']))    $settings['sandbox_api_key']    = '***set***';
if (!empty($settings['sandbox_api_secret']))  $settings['sandbox_api_secret']  = '***set***';
if (!empty($settings['sandbox_auth_token']))  $settings['sandbox_auth_token']  = '***active***';

jsonResponse(true, $settings);

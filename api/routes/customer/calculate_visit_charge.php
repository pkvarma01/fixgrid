<?php
// routes/customer/calculate_visit_charge.php
// Preview visit charge before booking — called by app when user picks a service
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('customer');
$db = getDB();

$serviceId = (int)($input['service_id'] ?? 0);
$custLat   = (float)($input['latitude']  ?? 0);
$custLng   = (float)($input['longitude'] ?? 0);

if (!$serviceId || !$custLat || !$custLng) {
    jsonResponse(false, null, 'service_id, latitude, longitude required', 422);
}

// Get service
$svc = $db->prepare("SELECT * FROM service_types WHERE id=? AND is_active=1");
$svc->execute([$serviceId]);
$service = $svc->fetch();
if (!$service) jsonResponse(false, null, 'Invalid service', 404);

// Get nearest available engineer distance
$nearEng = $db->prepare("
    SELECT e.id, e.name,
        ROUND(6371 * ACOS(
            COS(RADIANS(?)) * COS(RADIANS(e.latitude)) *
            COS(RADIANS(e.longitude) - RADIANS(?)) +
            SIN(RADIANS(?)) * SIN(RADIANS(e.latitude))
        ), 2) AS distance_km
    FROM engineers e
    WHERE e.is_active=1 AND e.status='available'
      AND e.latitude IS NOT NULL AND e.longitude IS NOT NULL
    HAVING distance_km <= 50
    ORDER BY distance_km ASC
    LIMIT 1
");
$nearEng->execute([$custLat, $custLng, $custLat]);
$nearest = $nearEng->fetch();
$distKm  = $nearest ? (float)$nearest['distance_km'] : null;

$calc = calculateVisitCharge($db, $service, $distKm);

jsonResponse(true, [
    'fixed_charge'  => $calc['fixed'],
    'km_charge'     => $calc['km_charge'],
    'total_visit'   => $calc['total'],
    'distance_km'   => $distKm,
    'formula'       => $calc['formula'],
    'nearest_engineer' => $nearest ? $nearest['name'] : null,
]);

// ── Helper ────────────────────────────────────────────────
function calculateVisitCharge($db, $service, $distKm) {
    $fixed      = (float)($service['visit_charge'] ?? 0);
    $perKmRate  = (float)($service['per_km_rate']  ?? 0);

    // Fall back to global settings if service has no per_km_rate
    if ($perKmRate <= 0) {
        $perKmRate = (float)getSettingValue('visit_per_km_rate', 0);
    }

    $freeKm     = (float)getSettingValue('visit_free_km', 0);
    $maxKmCharge = (float)getSettingValue('visit_max_km_charge', 9999);

    $kmCharge = 0;
    if ($perKmRate > 0 && $distKm !== null) {
        $billableKm = max(0, $distKm - $freeKm);
        $kmCharge   = min($billableKm * $perKmRate, $maxKmCharge);
        $kmCharge   = round($kmCharge, 2);
    }

    $total = $fixed + $kmCharge;

    // Build human-readable formula string
    $parts = [];
    if ($fixed > 0)    $parts[] = "₹{$fixed} base";
    if ($kmCharge > 0) $parts[] = "₹{$kmCharge} travel (" . round($distKm - $freeKm, 1) . "km × ₹{$perKmRate})";
    $formula = implode(' + ', $parts) ?: 'Free visit';

    return ['fixed' => $fixed, 'km_charge' => $kmCharge, 'total' => $total, 'formula' => $formula];
}

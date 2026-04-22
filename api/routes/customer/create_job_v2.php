<?php
// routes/customer/create_job_v2.php
// Smart broadcast: ranks engineers by proximity + rating + completed jobs + skill match
require_once dirname(__DIR__, 2) . '/config.php';
$customer = requireAuth('customer');
$db = getDB();

$serviceId      = (int)($input['service_id']            ?? 0);
$description    = trim($input['description']            ?? '');
$address        = trim($input['address']                ?? '');
$lat            = (float)($input['latitude']            ?? 0);
$lng            = (float)($input['longitude']           ?? 0);
$scheduledDate  = trim($input['scheduled_date']         ?? '');
$slotId         = (int)($input['slot_id']               ?? 0);
$isEmergency    = (bool)($input['is_emergency']         ?? false);
$promoCode      = trim(strtoupper($input['promo_code']  ?? ''));
$preferredEngId = (int)($input['preferred_engineer_id'] ?? 0);
$payFromWallet  = (bool)($input['pay_from_wallet']      ?? false);

if (!$serviceId || !$description || !$address || !$lat || !$lng) {
    jsonResponse(false, null, 'service_id, description, address, latitude, longitude are required', 422);
}

$svc = $db->prepare('SELECT * FROM service_types WHERE id=? AND is_active=1');
$svc->execute([$serviceId]);
$service = $svc->fetch();
if (!$service) jsonResponse(false, null, 'Invalid or inactive service', 422);

$photoUrl = null;
if (!empty($_FILES['photo']['tmp_name'])) $photoUrl = uploadFile($_FILES['photo'], 'jobs');

// ── Pricing ───────────────────────────────────────────────────────────────────
$baseAmount  = (float)($service['base_price'] ?? 0);
$fixedVisit  = (float)($service['visit_charge'] ?? 0);
$perKmRate   = (float)($service['per_km_rate'] ?? 0);
if ($perKmRate <= 0) $perKmRate = (float)getSettingValue('visit_per_km_rate', 0);

$freeKm      = (float)getSettingValue('visit_free_km', 0);
$maxKmCharge = (float)getSettingValue('visit_max_km_charge', 9999);

$distKm = null; $kmCharge = 0;
if ($lat && $lng && $perKmRate > 0) {
    $nearStmt = $db->prepare(
        "SELECT ROUND(6371*ACOS(COS(RADIANS(?))*COS(RADIANS(e.latitude))*COS(RADIANS(e.longitude)-RADIANS(?))+SIN(RADIANS(?))*SIN(RADIANS(e.latitude))),2) AS dist
         FROM engineers e WHERE e.is_active=1 AND e.latitude IS NOT NULL HAVING dist<=50 ORDER BY dist ASC LIMIT 1"
    );
    $nearStmt->execute([$lat, $lng, $lat]);
    $distKm = $nearStmt->fetchColumn() ?: null;
    if ($distKm !== null) {
        $billableKm = max(0, $distKm - $freeKm);
        $kmCharge   = min(round($billableKm * $perKmRate, 2), $maxKmCharge);
    }
}

$visitCharge  = round($fixedVisit + $kmCharge, 2);
$emergencyFee = 0;
if ($isEmergency) {
    $pct = (float)getSettingValue('emergency_surcharge_pct', 30);
    $emergencyFee = round($baseAmount * $pct / 100, 2);
}

$discountAmount = 0; $promoResult = null;
if ($promoCode) {
    $promoResult = validatePromoCode($promoCode, $baseAmount + $emergencyFee, $customer['id']);
    if ($promoResult['valid']) $discountAmount = $promoResult['discount'];
    else jsonResponse(false, null, $promoResult['message'] ?? 'Invalid promo code', 422);
}

$finalAmount = max(0, $baseAmount + $emergencyFee + $visitCharge - $discountAmount);

// ── Wallet ────────────────────────────────────────────────────────────────────
$db->prepare("INSERT IGNORE INTO customer_wallet (customer_id, balance) VALUES (?,0)")->execute([$customer['id']]);
$walletStmt = $db->prepare('SELECT balance FROM customer_wallet WHERE customer_id=?');
$walletStmt->execute([$customer['id']]);
$walletBalance = (float)($walletStmt->fetchColumn() ?: 0);

if ($payFromWallet && $finalAmount > 0 && $walletBalance < $finalAmount) {
    jsonResponse(false, null, 'Insufficient wallet balance. Available: ₹' . number_format($walletBalance, 2) . ', Required: ₹' . number_format($finalAmount, 2), 422);
}

// ── Create Job ────────────────────────────────────────────────────────────────
$jobNumber = generateJobNumber();
$db->prepare("INSERT INTO jobs (job_number,customer_id,service_id,service_type,description,address,latitude,longitude,photo,amount,visit_charge,km_charge,engineer_distance_km,emergency_fee,discount_amount,final_amount,is_emergency,scheduled_date,slot_id,promo_code,preferred_engineer_id,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'pending')")
   ->execute([$jobNumber,$customer['id'],$serviceId,$service['name'],$description,$address,$lat,$lng,$photoUrl,$baseAmount,$visitCharge,$kmCharge,$distKm,$emergencyFee,$discountAmount,$finalAmount,$isEmergency?1:0,$scheduledDate?:null,$slotId?:null,$promoCode?:null,$preferredEngId?:null]);
$jobId = $db->lastInsertId();

if ($promoResult && $promoResult['valid']) {
    $db->prepare("UPDATE promo_codes SET used_count=used_count+1 WHERE code=?")->execute([$promoCode]);
}

$db->prepare('INSERT INTO chat_rooms (job_id,customer_id) VALUES (?,?)')->execute([$jobId, $customer['id']]);

if ($payFromWallet && $finalAmount > 0) {
    $db->prepare("UPDATE customer_wallet SET balance=balance-? WHERE customer_id=?")->execute([$finalAmount, $customer['id']]);
    $db->prepare("INSERT INTO wallet_transactions (customer_id,type,amount,description,ref_id) VALUES (?,?,?,?,?)")->execute([$customer['id'], 'debit', $finalAmount, 'Payment for job #' . $jobNumber, $jobId]);
    $walletBalance -= $finalAmount;
}

// ══════════════════════════════════════════════════════════════════════════════
// SMART JOB NOTIFICATION — Zone Priority with Nearby Fallback
//
// STEP 1: Find zones whose radius covers the job GPS location
// STEP 2: If zone engineers exist → notify ONLY them (zone priority)
// STEP 3: If NO zone engineers available → fallback to nearby broadcast
//         with "📍 Outside Your Zone" badge so engineers know context
// STEP 4: Preferred engineer always notified regardless of zone
// ══════════════════════════════════════════════════════════════════════════════

$zoneNotifyCount    = 0;
$broadcastCount     = 0;
$notifiedIds        = [];
$zoneEngineersExist = false;

try {

    // ── STEP 1: Find matching zones ──────────────────────────────────────────
    // FIX: HAVING cannot reference z.radius_km alongside a computed alias in MySQL.
    //      Wrapping in a subquery lets the outer WHERE compare both values cleanly.
    $zoneStmt = $db->prepare("
        SELECT * FROM (
            SELECT z.id, z.name, z.radius_km,
                ROUND(6371 * ACOS(
                    COS(RADIANS(:lat1)) * COS(RADIANS(z.latitude)) *
                    COS(RADIANS(z.longitude) - RADIANS(:lng)) +
                    SIN(RADIANS(:lat2)) * SIN(RADIANS(z.latitude))
                ), 2) AS distance_km
            FROM zones z
            WHERE z.is_active   = 1
              AND z.latitude  IS NOT NULL
              AND z.longitude IS NOT NULL
        ) AS sub
        WHERE sub.distance_km <= sub.radius_km
        ORDER BY sub.distance_km ASC
        LIMIT 1
    ");
    $zoneStmt->execute([":lat1" => $lat, ":lat2" => $lat, ":lng" => $lng]);
    // CLOSEST ZONE WINS — only notify engineers from the single closest matching
    // zone. This prevents adjacent/overlapping zones from receiving the same job.
    $bestZone     = $zoneStmt->fetch();
    $matchedZones = $bestZone ? [$bestZone] : [];

    // ── STEP 2: Check if any zone engineers exist in the closest zone ─────────
    foreach ($matchedZones as $zone) {
        $chkStmt = $db->prepare("
            SELECT COUNT(*) AS cnt
            FROM zone_engineers ze
            JOIN engineers e ON e.id = ze.engineer_id
            WHERE ze.zone_id      = ?
              AND ze.is_available = 1
              AND e.is_active     = 1
        ");
        $chkStmt->execute([$zone["id"]]);
        if ((int)$chkStmt->fetchColumn() > 0) {
            $zoneEngineersExist = true;
            break;
        }
    }

    if ($zoneEngineersExist) {

        // ── STEP 2a: ZONE PRIORITY — notify zone engineers only ───────────────
        foreach ($matchedZones as $zone) {
            $engStmt = $db->prepare("
                SELECT e.id, e.name, e.device_token
                FROM zone_engineers ze
                JOIN engineers e ON e.id = ze.engineer_id
                WHERE ze.zone_id      = :zone_id
                  AND ze.is_available = 1
                  AND e.is_active     = 1
            ");
            $engStmt->execute([":zone_id" => $zone["id"]]);
            $zoneEngineers = $engStmt->fetchAll();

            $title = $isEmergency
                ? "🚨 URGENT Job in Your Zone!"
                : ($scheduledDate && $scheduledDate > date("Y-m-d")
                    ? "📅 Scheduled Job in Your Zone"
                    : "🗾 New Job in Your Zone");

            $body = "Job #" . $jobNumber . " — " . $service["name"] . " | " . $zone["name"];
            if ($scheduledDate && $scheduledDate > date("Y-m-d")) {
                $body .= " | Date: " . $scheduledDate;
                if ($slotId) {
                    $slotRow = $db->prepare("SELECT label FROM time_slots WHERE id=?");
                    $slotRow->execute([$slotId]);
                    $slot = $slotRow->fetch();
                    if ($slot) $body .= " (" . $slot["label"] . ")";
                }
            }

            foreach ($zoneEngineers as $eng) {
                if (isset($notifiedIds[$eng["id"]])) continue;
                $notifiedIds[$eng["id"]] = true;

                $db->prepare("INSERT IGNORE INTO job_offers (job_id, engineer_id) VALUES (?,?)")
                   ->execute([$jobId, $eng["id"]]);

                sendPushNotification(
                    $eng["device_token"] ?? "",
                    $title, $body,
                    ["job_id" => $jobId, "type" => "zone_job", "zone" => $zone["name"]]
                );
                logNotification(
                    $eng["id"], "engineer", $title, $body,
                    ["job_id" => $jobId, "zone_id" => $zone["id"], "type" => "zone_job"]
                );
                $zoneNotifyCount++;
            }
        }

    } else {

        // ── STEP 3: FALLBACK — no zone engineers, broadcast to nearby ─────────
        // Try available engineers first, then fallback to all active (any status)
        $nearbyEngineers = findEngineersForBroadcast($db, $lat, $lng, $serviceId, $preferredEngId, false);
        if (empty($nearbyEngineers)) {
            // No 'available' engineers nearby — try all active engineers regardless of status
            $nearbyEngineers = findEngineersForBroadcast($db, $lat, $lng, $serviceId, $preferredEngId, true);
        }

        $title = $isEmergency
            ? "🚨 URGENT Job — Outside Your Zone"
            : ($scheduledDate && $scheduledDate > date("Y-m-d")
                ? "📅 Scheduled Job — Outside Your Zone"
                : "📍 New Job — Outside Your Zone");

        $body = "Job #" . $jobNumber . " — " . $service["name"]
              . " | No zone engineers available in this area";

        if ($scheduledDate && $scheduledDate > date("Y-m-d")) {
            $body .= " | Date: " . $scheduledDate;
        }

        foreach ($nearbyEngineers as $eng) {
            if (isset($notifiedIds[$eng["id"]])) continue;
            $notifiedIds[$eng["id"]] = true;

            $db->prepare("INSERT IGNORE INTO job_offers (job_id, engineer_id) VALUES (?,?)")
               ->execute([$jobId, $eng["id"]]);

            sendPushNotification(
                $eng["device_token"] ?? "",
                $title,
                $body . " | Distance: " . round($eng["distance_km"], 1) . "km",
                ["job_id" => $jobId, "type" => "nearby_fallback", "outside_zone" => true]
            );
            logNotification(
                $eng["id"], "engineer", $title, $body,
                ["job_id" => $jobId, "type" => "nearby_fallback", "outside_zone" => true]
            );
            $broadcastCount++;
        }
    }

    // ── STEP 4: Preferred engineer — always notify regardless of zone ─────────
    if ($preferredEngId) {
        $prefStmt = $db->prepare("SELECT id, name, device_token FROM engineers WHERE id=? AND is_active=1");
        $prefStmt->execute([$preferredEngId]);
        $pref = $prefStmt->fetch();
        if ($pref && !isset($notifiedIds[$pref["id"]])) {
            $notifiedIds[$pref["id"]] = true;
            $db->prepare("INSERT IGNORE INTO job_offers (job_id, engineer_id) VALUES (?,?)")
               ->execute([$jobId, $pref["id"]]);
            $ptitle = "⭐ Customer Requested You";
            $pbody  = "Job #" . $jobNumber . " — " . $service["name"] . ". Customer specifically requested you.";
            sendPushNotification($pref["device_token"] ?? "", $ptitle, $pbody,
                ["job_id" => $jobId, "type" => "preferred_request"]);
            logNotification($pref["id"], "engineer", $ptitle, $pbody, ["job_id" => $jobId]);
            $zoneNotifyCount++;
        }
    }

} catch (Exception $e) {
    error_log("[JobNotify] " . $e->getMessage());
}
// ─────────────────────────────────────────────────────────────────────────────

$jobFullStmt = $db->prepare("SELECT j.*, s.name AS service_name FROM jobs j JOIN service_types s ON j.service_id=s.id WHERE j.id=?");
$jobFullStmt->execute([$jobId]);
$jobFull = $jobFullStmt->fetch();

$totalNotified = $zoneNotifyCount + $broadcastCount;
if ($zoneNotifyCount > 0) {
    $msg = 'Job created! ' . $zoneNotifyCount . ' zone engineer(s) notified.';
} elseif ($broadcastCount > 0) {
    $msg = 'Job created! No zone engineers available — notified ' . $broadcastCount . ' nearby engineer(s) outside zone.';
} elseif ($scheduledDate) {
    $msg = 'Job scheduled! Engineers will be notified.';
} else {
    $msg = 'Job created! Searching for engineers near you.';
}

jsonResponse(true, [
    'job'                => $jobFull,
    'base_amount'        => $baseAmount,
    'visit_charge'       => $visitCharge,
    'emergency_fee'      => $emergencyFee,
    'discount_applied'   => $discountAmount > 0 ? $promoCode : null,
    'discount_amount'    => $discountAmount,
    'final_amount'       => $finalAmount,
    'wallet_balance'     => $walletBalance,
    'paid_from_wallet'   => $payFromWallet && $finalAmount > 0,
    'engineers_notified'  => $totalNotified,
    'zone_notified'       => $zoneNotifyCount,
    'broadcast_notified'  => $broadcastCount,
    'zone_engineers_found'=> $zoneEngineersExist,
], $msg);

// ════════════════════════════════════════════════════════════════════════════════
// SMART ENGINEER MATCHING
// Ranks engineers by a weighted score:
//   40% — Proximity (closer = higher score, within radius)
//   25% — Skill match (has the required skill for this service)
//   20% — Avg rating (from ratings table)
//   15% — Completed jobs count (experience)
// Returns top 5 by score, preferred engineer always first if available
// ════════════════════════════════════════════════════════════════════════════════
function findEngineersForBroadcast(PDO $db, float $lat, float $lng, int $serviceId, int $preferredEngId = 0, bool $anyStatus = false): array {
    $radius = (float)getSettingValue('assign_radius_km', 20);

    // Get required skill for this service (if any)
    $skillRow = $db->query("SELECT required_skill_id FROM service_types WHERE id=$serviceId")->fetch();
    $requiredSkillId = (int)($skillRow['required_skill_id'] ?? 0);

    // Fetch all candidate engineers within radius with scoring data
    $sql = "
        SELECT
            e.*,
            -- Distance in km
            ROUND(
                6371 * ACOS(
                    COS(RADIANS(:lat1)) * COS(RADIANS(e.latitude))
                    * COS(RADIANS(e.longitude) - RADIANS(:lng))
                    + SIN(RADIANS(:lat2)) * SIN(RADIANS(e.latitude))
                ), 2
            ) AS distance_km,
            -- Avg rating from ratings table (0–5)
            COALESCE((
                SELECT ROUND(AVG(r.rating), 2)
                FROM ratings r
                WHERE r.engineer_id = e.id
            ), 0) AS avg_rating,
            -- Completed jobs count (experience)
            COALESCE((
                SELECT COUNT(*)
                FROM jobs j
                WHERE j.engineer_id = e.id AND j.status = 'completed'
            ), 0) AS completed_jobs,
            -- Skill match: 1 if engineer has required skill, 0 if no skill required or matches
            CASE
                WHEN :skill_id = 0 THEN 1
                WHEN EXISTS (
                    SELECT 1 FROM engineer_skills es WHERE es.engineer_id = e.id AND es.skill_id = :skill_id2
                ) THEN 1
                ELSE 0
            END AS skill_match
        FROM engineers e
        WHERE e.is_active = 1
          AND (e.status = 'available' OR :any_status = 1)
          AND e.latitude IS NOT NULL
          AND e.longitude IS NOT NULL
        HAVING distance_km <= :radius
        ORDER BY distance_km ASC
        LIMIT 50
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':lat1'       => $lat,
        ':lat2'       => $lat,
        ':lng'        => $lng,
        ':skill_id'   => $requiredSkillId,
        ':skill_id2'  => $requiredSkillId,
        ':any_status' => $anyStatus ? 1 : 0,
        ':radius'   => $radius,
    ]);
    $candidates = $stmt->fetchAll();

    if (!$candidates) return [];

    // ── Compute normalized scores ─────────────────────────────────────────────
    $maxDist     = max(array_column($candidates, 'distance_km')) ?: 1;
    $maxJobs     = max(array_column($candidates, 'completed_jobs')) ?: 1;

    foreach ($candidates as &$eng) {
        // Proximity score: 1 = closest, 0 = at edge of radius
        $proximityScore = 1 - ($eng['distance_km'] / $maxDist);

        // Rating score: normalize 0–5 → 0–1
        $ratingScore = $eng['avg_rating'] / 5;

        // Experience score: normalize by max completed jobs in candidate pool
        $expScore = $eng['completed_jobs'] / $maxJobs;

        // Skill match score: 1 or 0
        $skillScore = (float)$eng['skill_match'];

        // Weighted total (weights sum to 1.0)
        $eng['match_score'] =
            ($proximityScore * 0.40) +
            ($skillScore     * 0.25) +
            ($ratingScore    * 0.20) +
            ($expScore       * 0.15);
    }
    unset($eng);

    // ── Sort by match score descending ───────────────────────────────────────
    usort($candidates, fn($a, $b) => $b['match_score'] <=> $a['match_score']);

    // ── Preferred engineer always goes first ─────────────────────────────────
    $results = [];
    if ($preferredEngId) {
        foreach ($candidates as $c) {
            if ((int)$c['id'] === $preferredEngId) {
                $results[] = $c;
                break;
            }
        }
    }

    // Fill remaining slots (up to 5 total, skip if already skill_match=0 when skill required)
    $limit = 5 - count($results);
    foreach ($candidates as $c) {
        if ($limit <= 0) break;
        if ($preferredEngId && (int)$c['id'] === $preferredEngId) continue;
        // If skill is required, prefer skill-matched engineers but still include others if < 3 found
        if ($requiredSkillId && !$c['skill_match'] && count($results) >= 3) continue;
        $results[] = $c;
        $limit--;
    }

    return $results;
}

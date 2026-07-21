<?php
/**
 * Inbound webhook — IPMS posts the road inspection result here once their
 * inspector has been out to the site.
 *
 * Give this URL to the IPMS team:
 *   https://upad.infragovservices.com/api/webhooks/ipms_inspection_result.php
 *
 * Expected JSON payload — field names match their "Inspect" modal
 * (confirmed from their screenshots, 2026-07-21). ⚠️ The "status"/"approved"
 * wrapper and exact signature scheme are still placeholders — confirm with
 * the IPMS team, since their modal itself has no explicit approve/reject
 * toggle. We derive approved/rejected from overall_condition + severity
 * below; ask them to confirm that mapping or send an explicit status field.
 * {
 *   "application_id": 789,              // echoed back from our request
 *   "road_id": "RD-2026-021",           // their own generated id
 *   "inspection_date": "2026-07-19",
 *   "engineer_assigned": "Juan Dela Cruz",
 *   "road_condition": "Good",           // Excellent | Good | Fair | Poor | Critical
 *   "surface_condition": "Good",
 *   "drainage_condition": "Fair",
 *   "sidewalk_condition": "Good",
 *   "streetlight_condition": "Poor",
 *   "traffic_sign_condition": "Good",
 *   "overall_condition": "Good",        // Excellent | Good | Fair | Poor | Critical
 *   "severity": "Low",                  // placeholder scale — confirm values with IPMS
 *   "recommendation": "No action needed", // placeholder — confirm their dropdown options
 *   "gps_latitude": 14.6760,
 *   "gps_longitude": 121.0437,
 *   "remarks": "Observations for the record...",
 *   "photo_urls": ["https://ipms.infragovservices.com/uploads/xyz.jpg"]
 * }
 *
 * Auth: expects an X-IPMS-Signature header containing an HMAC-SHA256 of the
 * raw request body, signed with the shared IPMS_WEBHOOK_SECRET.
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/roads_integration.php';

header('Content-Type: application/json');

// ── 1. Verify the request really came from IPMS ─────────────────────────────
$signature = $_SERVER['HTTP_X_IPMS_SIGNATURE'] ?? '';
$rawBody   = file_get_contents('php://input');

$expectedSignature = hash_hmac('sha256', $rawBody, IPMS_WEBHOOK_SECRET);
if (!$signature || !hash_equals($expectedSignature, $signature)) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

$data = json_decode($rawBody, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload']);
    exit;
}

$applicationId    = (int) ($data['application_id'] ?? 0);
$externalRefId    = $data['road_id']              ?? null;
$overallCondition = $data['overall_condition']    ?? null;  // Excellent|Good|Fair|Poor|Critical
$severity         = $data['severity']             ?? null;  // ⚠️ scale not yet confirmed with IPMS
$recommendation   = $data['recommendation']        ?? null;  // ⚠️ dropdown options not yet confirmed
$remarks          = $data['remarks']              ?? '';
$inspectedAt      = $data['inspection_date']       ?? null;
$engineerAssigned = $data['engineer_assigned']     ?? null;

if (!$applicationId || !$overallCondition) {
    http_response_code(422);
    echo json_encode(['error' => 'Missing application_id or overall_condition']);
    exit;
}

// ⚠️ PLACEHOLDER mapping — their modal has no explicit approve/reject toggle,
// so we derive it here (see INTEGRATION_CONTRACT.md section 3 for the full
// decision table). Confirm this logic with IPMS, or ask them to just send
// an explicit "status" field instead, which would be more reliable.
$needsAction = in_array($overallCondition, ['Poor', 'Critical'], true)
    || in_array($recommendation, ['Immediate Repair', 'Escalate to District Engineer'], true);
$status = $needsAction ? 'rejected' : 'approved';

$notes = trim(sprintf(
    "Overall: %s | Severity: %s | Recommendation: %s\nEngineer: %s | Inspected: %s\nRemarks: %s",
    $overallCondition,
    $severity ?? 'N/A',
    $recommendation ?? 'N/A',
    $engineerAssigned ?? 'N/A',
    $inspectedAt ?? 'N/A',
    $remarks
));

$db = Database::getInstance()->getConnection();

try {
    $db->beginTransaction();

    // ── 2. Update our tracking record ───────────────────────────────────────
    $db->prepare(
        "UPDATE road_inspection_requests
            SET status = 'completed', external_ref_id = ?, response_payload = ?, responded_at = NOW(),
                overall_condition = ?, severity = ?, recommendation = ?,
                engineer_assigned = ?, inspection_date = ?
          WHERE application_id = ? AND status IN ('sent', 'pending')
          ORDER BY id DESC LIMIT 1"
    )->execute([
        $externalRefId, $rawBody,
        $overallCondition, $severity, $recommendation,
        $engineerAssigned, $inspectedAt,
        $applicationId,
    ]);

    // ── 3. Update what shows up in the Technical Assessment tab ─────────────
    $flag = $status === 'approved' ? 'ok' : 'violation';
    $db->prepare(
        "INSERT INTO impact_assessments (application_id, traffic_flag, traffic_notes, checked_at)
         VALUES (?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE
            traffic_flag  = ?,
            traffic_notes = ?,
            checked_at    = NOW()"
    )->execute([$applicationId, $flag, $notes, $flag, $notes]);

    // ── 4. Log it to the application's history / audit trail ────────────────
    $currentStatusStmt = $db->prepare("SELECT status FROM applications WHERE id = ?");
    $currentStatusStmt->execute([$applicationId]);
    $currentStatus = $currentStatusStmt->fetchColumn() ?: 'unknown';

    $db->prepare(
        "INSERT INTO application_status_history (application_id, status, remarks, changed_by)
         VALUES (?, ?, ?, 0)"
    )->execute([
        $applicationId,
        $currentStatus,
        'Road inspection result received from IPMS: ' . strtoupper($status)
            . ($notes ? " — $notes" : ''),
    ]);

    $db->commit();

    http_response_code(200);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
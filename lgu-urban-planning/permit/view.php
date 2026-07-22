<?php
/**
 * View Application Details (Staff View) - Final Sync Fix
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helper.php';
require_once __DIR__ . '/../modules/PermitProcessing/PermitController.php';
require_once __DIR__ . '/../modules/GISMapping/GISController.php';

$auth = new Auth();
$auth->requireRole(['admin', 'super_admin', 'zoning_officer', 'building_official', 'assessor', 'inspector']);

$permitController = new PermitController();
$gisController = new GISController();
$applicationId = (int)($_GET['id'] ?? 0);

$db = Database::getInstance();
$dbConn = $db->getConnection();

// ── Audit log helper ────────────────────────────────────────────────────────
function logAudit(PDO $pdo, int $userId, string $action, string $entityType, int $entityId, string $details): void {
    try {
        $pdo->prepare(
            "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address, user_agent, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
        )->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            $details,
            $_SERVER['REMOTE_ADDR']  ?? '0.0.0.0',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        ]);
    } catch (Exception $e) { /* fail silently — don't break main action */ }
}
// ────────────────────────────────────────────────────────────────────────────

// ── Parse "Label: Value | Label: Value" style assessment notes into a
//    clean associative array for structured display instead of one long
//    paragraph. Returns null if the text doesn't match the expected
//    format (e.g. plain status messages like "Awaiting result"), so the
//    caller can fall back to showing the raw text.
function parse_assessment_notes(?string $notes): ?array {
    if (!$notes || stripos($notes, ':') === false || stripos($notes, 'Overall:') === false) {
        return null;
    }

    $pairs = [];
    foreach (explode("\n", $notes) as $line) {
        $line = trim($line);
        if ($line === '') continue;

        // "Remarks:" often contains its own punctuation/colons in free text,
        // so treat it as a single field rather than splitting on "|".
        if (stripos($line, 'Remarks:') === 0) {
            $pairs['Remarks'] = trim(substr($line, strlen('Remarks:')));
            continue;
        }

        foreach (explode('|', $line) as $segment) {
            if (strpos($segment, ':') === false) continue;
            [$label, $value] = array_map('trim', explode(':', $segment, 2));
            if ($label !== '' && $value !== '') {
                $pairs[$label] = $value;
            }
        }
    }

    return $pairs ?: null;
}

// ── Maps a condition/severity word to a Bootstrap badge color ──────────────
function assessment_badge_class(string $value): ?string {
    $v = strtolower($value);
    return match (true) {
        in_array($v, ['excellent', 'good', 'low', 'ok'], true)        => 'success',
        in_array($v, ['fair', 'medium'], true)                        => 'warning',
        in_array($v, ['poor', 'high', 'critical', 'urgent'], true)    => 'danger',
        default                                                       => null,
    };
}
// ────────────────────────────────────────────────────────────────────────────

$error = '';
$success = '';

// ── Language / locale ─────────────────────────────────────────────────────────
$_lang = $_SESSION['locale_language'] ?? 'en_PH';

$_translations = [
    'en_PH' => [
        // Page
        'page_title'            => 'Application Details',
        'current_phase'         => 'Current Phase:',
        'phase_name'            => 'Urban Development Review',
        // Tabs
        'tab_details'           => 'Project Details',
        'tab_impact'            => 'Technical Assessment',
        'tab_docs'              => 'Documents',
        'tab_actions'           => 'Zoning & Actions',
        'tab_history'           => 'Timeline',
        // Details tab
        'section_project'       => 'Project & Land Information',
        'lbl_project_name'      => 'Project Name',
        'lbl_project_type'      => 'Project Type',
        'lbl_description'       => 'Project Description',
        'no_description'        => 'No description provided.',
        'section_identifiers'   => 'Technical & Legal Identifiers',
        'lbl_lot_block'         => 'Lot & Block Number',
        'lbl_street'            => 'Street',
        'lbl_barangay'          => 'Barangay',
        'lbl_parcel_id'         => 'GIS Parcel ID',
        'lbl_coordinates'       => 'Geospatial Coordinates',
        'section_applicant'     => 'Applicant Information',
        'lbl_applicant_id'      => 'Applicant ID',
        'lbl_full_name'         => 'Full Name',
        'lbl_email'             => 'Email Address',
        'lbl_phone'             => 'Phone/Contact',
        'lbl_record_type'       => 'Record Type:',
        'record_walkin'         => 'Manual / Walk-in Entry',
        'record_online'         => 'Online Portal Submission',
        // Impact/assessment tab
        'impact_inspector_note' => 'You are viewing departmental assessments. To submit your field report, please go to the',
        'impact_inspector_link' => 'Zoning & Actions',
        'impact_inspector_end'  => 'tab.',
        'impact_heading'        => 'Departmental Inspection Results',
        'impact_subtitle'       => 'Assessment data provided by Roads and Energy departments.',
        'btn_simulate'          => 'Request New Inspection',
        'roads_title'           => 'Roads & Traffic',
        'roads_subtitle'        => 'Infrastructure Impact',
        'utilities_title'       => 'Utilities',
        'utilities_subtitle'    => 'Grid Capacity Load',
        'awaiting_inspection'   => 'AWAITING INSPECTION',
        'assessment_data'       => 'Assessment Data:',
        'no_roads_data'         => 'No data submitted yet by IPMS (Infrastructure Project Management System).',
        'no_energy_data'        => 'No data submitted yet by the Energy/Utilities department.',
        // Docs tab
        'docs_heading'          => 'Submitted Requirements',
        'no_docs'               => 'No documents uploaded yet.',
        'col_doc_type'          => 'Document Type',
        'col_file_name'         => 'File Name',
        'col_uploaded_by'       => 'Uploaded By',
        'col_date'              => 'Date',
        'col_action'            => 'Action',
        // Actions tab
        'actions_heading'       => 'Workflow Action',
        'lbl_set_status'        => 'Set Application Status',
        'status_submitted'      => 'Submitted (Initial)',
        'status_review'         => 'Under Review / Processing',
        'status_revision'       => 'For Revision (Return to Applicant)',
        'status_approved'       => 'Final Approval',
        'status_rejected'       => 'Rejected / Denied',
        'status_hint'           => "Final Approval requires all technical assessments to be 'OK'.",
        'lbl_assign_officer'    => 'Assign to Officer',
        'no_assignment'         => '-- No Assignment --',
        'lbl_remarks'           => 'Official Remarks',
        'remarks_ph'            => 'Provide reason for status update or instructions...',
        'prereq_title'          => 'Required Before Workflow Update',
        'prereq_tech_done'      => 'Technical Assessment — Completed',
        'prereq_tech_pending'   => 'Technical Assessment — Not yet done',
        'prereq_zone_done'      => 'Zoning & Land Verification — Completed',
        'prereq_zone_pending'   => 'Zoning & Land Verification — Not yet done',
        'btn_confirm_workflow'  => 'Confirm Workflow Update',
        'prereq_modal_title'    => 'Prerequisite Checks Incomplete',
        'prereq_modal_body'     => 'You cannot move this application to the selected status until the following steps are completed:',
        'btn_close'             => 'Close',
        // Permit PDF panel
        'permit_ready'          => 'Locational Clearance Ready',
        'permit_generated'      => 'Official permit document has been generated.',
        'btn_download_permit'   => 'Download / View Permit PDF',
        // Zoning section
        'zoning_heading'        => 'Zoning & Land Verification',
        'btn_reverify'          => 'RE-VERIFY ON GIS MAP',
        'btn_verify'            => 'LOCATE & VERIFY ON GIS MAP',
        'gis_cross_ref'         => 'Cross-reference with Cadastral & Zoning Map',
        'gis_analysis_label'    => 'GIS Technical Analysis:',
        'no_analysis'           => 'No analysis provided.',
        'awaiting_spatial'      => 'Awaiting Spatial Review',
        'awaiting_spatial_desc' => 'The zoning compliance and parcel boundaries have not been verified.',
        // History tab
        'history_heading'       => 'Movement History',
        'history_submitted_manual' => 'SUBMITTED (MANUAL ENTRY)',
        'history_manual_desc'   => 'Application created via',
        'history_manual_entry'  => 'Manual Entry',
        'history_for'           => 'for',
        'history_by'            => 'By:',
        'history_admin'         => 'ADMINISTRATOR',
        'history_submitted_online' => 'SUBMITTED (ONLINE)',
        'history_online_desc'   => 'Application submitted via',
        'history_online_portal' => 'Online Portal',
        'history_sys_admin'     => 'SYSTEM / ADMINISTRATOR',
        'history_no_notes'      => 'No notes provided',
        'pagination_prev'       => 'Previous',
        'pagination_next'       => 'Next',
        // Prereq modal JS strings
        'js_prereq_tech'        => '<strong>Technical Assessment</strong> — Go to the <em>Technical Assessment</em> tab and click <em>Request New Inspection</em>.',
        'js_prereq_zone'        => '<strong>Zoning &amp; Land Verification</strong> — Go to the <em>Zoning &amp; Actions</em> tab and verify the parcel on the GIS Map.',
        // Back button
        'btn_back'              => 'Back to Development Permits',
        // Doc viewer modal
        'doc_viewer_title'      => 'Document Viewer',
    ],
    'fil' => [
        // Page
        'page_title'            => 'Mga Detalye ng Aplikasyon',
        'current_phase'         => 'Kasalukuyang Yugto:',
        'phase_name'            => 'Pagsusuri ng Urban Development',
        // Tabs
        'tab_details'           => 'Mga Detalye ng Proyekto',
        'tab_impact'            => 'Teknikal na Pagsusuri',
        'tab_docs'              => 'Mga Dokumento',
        'tab_actions'           => 'Zoning at Aksyon',
        'tab_history'           => 'Timeline',
        // Details tab
        'section_project'       => 'Impormasyon ng Proyekto at Lupa',
        'lbl_project_name'      => 'Pangalan ng Proyekto',
        'lbl_project_type'      => 'Uri ng Proyekto',
        'lbl_description'       => 'Paglalarawan ng Proyekto',
        'no_description'        => 'Walang paglalarawang ibinigay.',
        'section_identifiers'   => 'Mga Teknikal at Legal na Identifier',
        'lbl_lot_block'         => 'Numero ng Lote at Bloke',
        'lbl_street'            => 'Kalye',
        'lbl_barangay'          => 'Barangay',
        'lbl_parcel_id'         => 'GIS Parcel ID',
        'lbl_coordinates'       => 'Geospatial na Koordinasyon',
        'section_applicant'     => 'Impormasyon ng Aplikante',
        'lbl_applicant_id'      => 'ID ng Aplikante',
        'lbl_full_name'         => 'Buong Pangalan',
        'lbl_email'             => 'Email Address',
        'lbl_phone'             => 'Telepono/Kontak',
        'lbl_record_type'       => 'Uri ng Rekord:',
        'record_walkin'         => 'Manu-mano / Walk-in Entry',
        'record_online'         => 'Pagsusumite sa Online Portal',
        // Impact tab
        'impact_inspector_note' => 'Tinitingnan mo ang mga departmental na pagsusuri. Para isumite ang iyong field report, pumunta sa',
        'impact_inspector_link' => 'Zoning at Aksyon',
        'impact_inspector_end'  => 'tab.',
        'impact_heading'        => 'Mga Resulta ng Departmental na Inspeksyon',
        'impact_subtitle'       => 'Datos ng pagsusuri mula sa mga departamento ng Kalsada at Enerhiya.',
        'btn_simulate'          => 'Humiling ng Bagong Inspeksyon',
        'roads_title'           => 'Kalsada at Trapiko',
        'roads_subtitle'        => 'Epekto sa Imprastraktura',
        'utilities_title'       => 'Mga Utility',
        'utilities_subtitle'    => 'Kapasidad ng Grid',
        'awaiting_inspection'   => 'NAGHIHINTAY SA INSPEKSYON',
        'assessment_data'       => 'Datos ng Pagsusuri:',
        'no_roads_data'         => 'Wala pang datos na isinumite ng inspektor ng departamento ng Kalsada.',
        'no_energy_data'        => 'Wala pang datos na isinumite ng departamento ng Enerhiya/Utilities.',
        // Docs tab
        'docs_heading'          => 'Mga Isinumiteng Kinakailangan',
        'no_docs'               => 'Wala pang mga dokumentong na-upload.',
        'col_doc_type'          => 'Uri ng Dokumento',
        'col_file_name'         => 'Pangalan ng File',
        'col_uploaded_by'       => 'In-upload Ni',
        'col_date'              => 'Petsa',
        'col_action'            => 'Aksyon',
        // Actions tab
        'actions_heading'       => 'Aksyon sa Workflow',
        'lbl_set_status'        => 'Itakda ang Katayuan ng Aplikasyon',
        'status_submitted'      => 'Isinumite (Paunang)',
        'status_review'         => 'Sinusuri / Pinoproseso',
        'status_revision'       => 'Para sa Rebisyon (Ibalik sa Aplikante)',
        'status_approved'       => 'Panghuling Pag-apruba',
        'status_rejected'       => 'Tinanggihan / Ipinagkait',
        'status_hint'           => "Ang Panghuling Pag-apruba ay nangangailangan na lahat ng teknikal na pagsusuri ay 'OK'.",
        'lbl_assign_officer'    => 'Italaga sa Opisyal',
        'no_assignment'         => '-- Walang Itatalaga --',
        'lbl_remarks'           => 'Opisyal na Mga Puna',
        'remarks_ph'            => 'Magbigay ng dahilan para sa pag-update ng katayuan o mga tagubilin...',
        'prereq_title'          => 'Kinakailangan Bago I-update ang Workflow',
        'prereq_tech_done'      => 'Teknikal na Pagsusuri — Nakumpleto',
        'prereq_tech_pending'   => 'Teknikal na Pagsusuri — Hindi pa tapos',
        'prereq_zone_done'      => 'Zoning at Pag-verify ng Lupa — Nakumpleto',
        'prereq_zone_pending'   => 'Zoning at Pag-verify ng Lupa — Hindi pa tapos',
        'btn_confirm_workflow'  => 'Kumpirmahin ang Update ng Workflow',
        'prereq_modal_title'    => 'Hindi Kumpleto ang Mga Kinakailangan',
        'prereq_modal_body'     => 'Hindi mo mailipat ang aplikasyong ito sa napiling katayuan hanggang makumpleto ang mga sumusunod na hakbang:',
        'btn_close'             => 'Isara',
        // Permit PDF panel
        'permit_ready'          => 'Handa na ang Locational Clearance',
        'permit_generated'      => 'Nabuo na ang opisyal na dokumento ng permit.',
        'btn_download_permit'   => 'I-download / Tingnan ang Permit PDF',
        // Zoning section
        'zoning_heading'        => 'Zoning at Pag-verify ng Lupa',
        'btn_reverify'          => 'MULING I-VERIFY SA GIS MAP',
        'btn_verify'            => 'HANAPIN AT I-VERIFY SA GIS MAP',
        'gis_cross_ref'         => 'I-cross-reference sa Cadastral at Zoning Map',
        'gis_analysis_label'    => 'GIS Teknikal na Pagsusuri:',
        'no_analysis'           => 'Walang pagsusuring ibinigay.',
        'awaiting_spatial'      => 'Naghihintay sa Spatial na Pagsusuri',
        'awaiting_spatial_desc' => 'Hindi pa na-verify ang zoning compliance at mga hangganan ng parcel.',
        // History tab
        'history_heading'       => 'Kasaysayan ng Kilusan',
        'history_submitted_manual' => 'ISINUMITE (MANU-MANONG ENTRY)',
        'history_manual_desc'   => 'Nilikha ang aplikasyon sa pamamagitan ng',
        'history_manual_entry'  => 'Manu-manong Entry',
        'history_for'           => 'para kay',
        'history_by'            => 'Ni:',
        'history_admin'         => 'ADMINISTRADOR',
        'history_submitted_online' => 'ISINUMITE (ONLINE)',
        'history_online_desc'   => 'Isinumite ang aplikasyon sa pamamagitan ng',
        'history_online_portal' => 'Online Portal',
        'history_sys_admin'     => 'SISTEMA / ADMINISTRADOR',
        'history_no_notes'      => 'Walang mga tala',
        'pagination_prev'       => 'Nakaraan',
        'pagination_next'       => 'Susunod',
        // Prereq modal JS strings
        'js_prereq_tech'        => '<strong>Teknikal na Pagsusuri</strong> — Pumunta sa tab na <em>Teknikal na Pagsusuri</em> at i-click ang <em>Humiling ng Bagong Inspeksyon</em>.',
        'js_prereq_zone'        => '<strong>Zoning at Pag-verify ng Lupa</strong> — Pumunta sa tab na <em>Zoning at Aksyon</em> at i-verify ang parcel sa GIS Map.',
        // Back button
        'btn_back'              => 'Bumalik sa Dashboard',
        // Doc viewer modal
        'doc_viewer_title'      => 'Viewer ng Dokumento',
    ],
];

function _vt(string $key, array $translations, string $lang): string {
    return $translations[$lang][$key] ?? $translations['en_PH'][$key] ?? $key;
}
$_t = fn(string $key) => _vt($key, $_translations, $_lang);

// --- STEP 1: FETCH FRESH DATA --- 
$zoningCheck = $db->fetchOne("SELECT * FROM zoning_compliance_checks WHERE application_id = ?", [$applicationId]);
$impactAssessment = $db->fetchOne("SELECT * FROM impact_assessments WHERE application_id = ? ORDER BY checked_at DESC LIMIT 1", [$applicationId]);
$application = $permitController->getApplicationDetails($applicationId);

if (!$application) {
    header('Location: /lgu-urban-planning/permit/applications.php');
    exit;
}

// --- STEP 2: HANDLE POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

// ASSESSOR ACTION: Update Parcel Info
if ($_POST['action'] === 'update_parcel_info' && in_array($_SESSION['role'] ?? '', ['admin', 'super_admin', 'assessor'])) {
    $lot      = trim($_POST['lot_number']  ?? '');
    $block    = trim($_POST['block']       ?? '');
    $street   = trim($_POST['street']      ?? '');
    $barangay = trim($_POST['barangay']    ?? '');
    $parcelId = trim($_POST['parcel_id']   ?? '');
    $lat      = trim($_POST['latitude']    ?? '');
    $lng      = trim($_POST['longitude']   ?? '');
    $officerId = $_SESSION['user_id'] ?? 0;

    $db->query(
        "UPDATE applications SET 
            lot_number = ?, block = ?, street = ?, barangay = ?, 
            parcel_id = ?, latitude = ?, longitude = ?
         WHERE id = ?",
        [$lot, $block, $street, $barangay, $parcelId, $lat, $lng, $applicationId]
    );

    // Log to history
    $db->query(
        "INSERT INTO application_status_history (application_id, status, remarks, changed_by, created_at)
         VALUES (?, ?, ?, ?, NOW())",
        [$applicationId, $application['status'], "Parcel information updated by assessor.", $officerId]
    );

    $success = "Parcel information updated successfully.";
    logAudit($dbConn, (int)($_SESSION['user_id'] ?? 0), 'update_parcel_info', 'application', $applicationId,
        "Parcel info updated — Lot: $lot, Block: $block, Street: $street, Barangay: $barangay, Parcel ID: $parcelId, Lat: $lat, Lng: $lng");
    // Refresh application data using controller to preserve joined fields
    $application = $permitController->getApplicationDetails($applicationId);
}


if ($_POST['action'] === 'assign_inspection' && $auth->hasRole(['admin', 'zoning_officer'])) {
    $ins_id = $_POST['inspector_id'];
    $sched = $_POST['scheduled_at'];
    
    $stmt = $dbConn->prepare("INSERT INTO inspections (application_id, inspector_id, scheduled_at, status) VALUES (?, ?, ?, 'scheduled')");
    if ($stmt->execute([$applicationId, $ins_id, $sched])) {
        $success = "Inspector assigned successfully.";
        logAudit($dbConn, (int)($_SESSION['user_id'] ?? 0), 'assign_inspection', 'application', $applicationId,
            "Inspector (user_id: $ins_id) assigned. Scheduled at: $sched");
    }
}

// 2. ROADS: real IPMS integration. ENERGY/UTILITIES: still the old dummy
//    simulation for now — swap that out next once Roads is confirmed working.
if ($_POST['action'] === 'request_inspection') {
    $officerId = $_SESSION['user_id'] ?? 0;

    require_once __DIR__ . '/../ipms-integration/RoadsIntegrationService.php'; // ⚠️ adjust to wherever you place the ipms-integration folder

    try {
        $roadsService = new RoadsIntegrationService();
        $result = $roadsService->requestInspection(
            $applicationId,
            [
                'applicant_name' => $application['applicant_name'] ?? null,
                'project_type'   => $application['project_type']   ?? null,
                'address'        => $application['street']         ?? null,
                'lot'            => $application['lot']            ?? null,
                'block'          => $application['block']          ?? null,
                'barangay'       => $application['barangay']       ?? null,
                'lat'            => $application['lat']            ?? null,
                'lng'            => $application['lng']            ?? null,
            ],
            (int) $officerId
        );

        if ($result['sent']) {
            $success = "Road inspection request sent to IPMS. Awaiting their response.";
        } else {
            // Still recorded locally as 'pending' even if IPMS couldn't be reached —
            // nothing is lost, it just needs retrying or manual follow-up.
            $error = "Request saved, but could not reach IPMS: " . ($result['error'] ?? 'unknown error');
        }

        logAudit($dbConn, (int) $officerId, 'request_inspection', 'application', $applicationId,
            "Road inspection request sent to IPMS (request_id: {$result['request_id']}).");

        $dbConn->prepare("INSERT INTO application_status_history (application_id, status, remarks, changed_by) VALUES (?, ?, ?, ?)")
               ->execute([$applicationId, $application['status'], "Road inspection requested from IPMS.", $officerId]);

        // --- ENERGY/UTILITIES: still the old dummy simulation, unchanged for now ---
        $energyNotes = "AUTOMATED SIMULATION: Grid capacity verified. Local transformer can handle the projected electrical load of the new development.";
        $dbConn->prepare(
            "INSERT INTO impact_assessments (application_id, energy_flag, energy_notes, checked_at)
             VALUES (?, 'ok', ?, NOW())
             ON DUPLICATE KEY UPDATE energy_flag = 'ok', energy_notes = ?, checked_at = NOW()"
        )->execute([$applicationId, $energyNotes, $energyNotes]);

        // Refresh so the UI reflects the latest state immediately
        $impactAssessment = $db->fetchOne("SELECT * FROM impact_assessments WHERE application_id = ? ORDER BY checked_at DESC LIMIT 1", [$applicationId]);
    } catch (Exception $e) {
        $error = "Request failed: " . $e->getMessage();
    }
}

//  3. INSPECTOR ACTION
if ($_POST['action'] === 'submit_inspection' && $auth->hasRole('inspector')) {
    $notes = $_POST['notes'];
    $status = $_POST['status']; // e.g., 'completed' or 'violation_found'
    
    $stmt = $dbConn->prepare("UPDATE inspections SET notes = ?, status = ?, updated_at = NOW() WHERE application_id = ? AND inspector_id = ?");
    if ($stmt->execute([$notes, $status, $applicationId, $_SESSION['user_id']])) {
        $success = "Inspection report submitted successfully.";
        logAudit($dbConn, (int)($_SESSION['user_id'] ?? 0), 'submit_inspection', 'application', $applicationId,
            "Inspection report submitted. Status: $status. Notes: " . substr($notes, 0, 100));
    }
}
    
// 4. ZONING COMPLIANCE UPDATE
if ($_POST['action'] === 'update_compliance') {
    $zoningType = $_POST['zoning_type'] ?? 'Unknown';
    $complianceResult = strtolower(trim($_POST['compliance_status'] ?? 'non_compliant'));
    $parcelIdFromMap = $_POST['parcel_id'] ?? 'N/A';
    $officerId = $_SESSION['user_id'] ?? 0;
    $finalAnalysis = $_POST['technical_analysis'] ?? '';

    // BINUKSAN ANG TRY BLOCK DITO
    try {
        // Simulan ang transaction para siguradong sabay ma-save ang zoning at history
        $dbConn->beginTransaction();

        // 1. I-save ang spatial analysis sa zoning table
        $sqlMain = "INSERT INTO zoning_compliance_checks 
                (application_id, parcel_id, zoning_type, compliance_status, technical_analysis, checked_by, checked_at) 
            VALUES (:app_id, :parcel_id, :zoning_type, :status, :analysis, :officer_id, NOW())
            ON DUPLICATE KEY UPDATE 
                zoning_type = VALUES(zoning_type), 
                compliance_status = VALUES(compliance_status), 
                technical_analysis = VALUES(technical_analysis), 
                checked_by = VALUES(checked_by), 
                checked_at = NOW()";
        
        $stmtComp = $dbConn->prepare($sqlMain);
        $stmtComp->execute([
            ':app_id' => $applicationId, 
            ':parcel_id' => $parcelIdFromMap, 
            ':zoning_type' => $zoningType, 
            ':status' => $complianceResult, 
            ':analysis' => $finalAnalysis, 
            ':officer_id' => $officerId 
        ]);

        // 2. I-save sa Movement History (application_status_history)
        $historyComment = "GIS Verification: " . strtoupper($complianceResult) . " (Zone: $zoningType)";
        
        $stmtHistory = $dbConn->prepare("INSERT INTO application_status_history (application_id, status, remarks, changed_by, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmtHistory->execute([
            $applicationId, 
            $application['status'], 
            $historyComment, 
            $officerId
        ]);

        // I-COMMIT ANG TRANSACTION
        $dbConn->commit();

        // Refresh variable para lumitaw agad sa UI
        $zoningCheck = $db->fetchOne("SELECT * FROM zoning_compliance_checks WHERE application_id = ?", [$applicationId]);
        $success = 'Spatial verification updated successfully.';
        logAudit($dbConn, (int)$officerId, 'update_compliance', 'application', $applicationId,
            "GIS zoning compliance updated. Result: " . strtoupper($complianceResult) . ", Zone: $zoningType, Parcel ID: $parcelIdFromMap");

    } catch (Exception $e) {
        // Kung may error, i-undo lahat ng pagbabago (Rollback)
        if ($dbConn->inTransaction()) {
            $dbConn->rollBack();
        }
        $error = "Update failed: " . $e->getMessage();
    }
}

// 5. STATUS UPDATES & MESSAGING
        if ($_POST['action'] === 'update_status') {
        $newStatus = $_POST['status'];
        $remarks = $_POST['remarks'] ?? 'Your application is currently being processed.';
        $officerId = $_SESSION['user_id'];
        $applicantId = $application['applicant_id'];

        // ── PREREQUISITE CHECK ───────────────────────────────────────────────
        // Statuses that require both Technical Assessment AND Zoning & Land
        // Verification to have been completed before they can be set.
        $statusesRequiringChecks = ['approved', 'rejected'];
        if (in_array($newStatus, $statusesRequiringChecks)) {
            $statusLabel = strtoupper(str_replace('_', ' ', $newStatus));
            if (!$impactAssessment) {
                $error = "<strong>Cannot move to \"{$statusLabel}\":</strong> Technical Assessment has not been completed yet. "
                       . "Please go to the <em>Technical Assessment</em> tab and run the departmental inspection simulation first.";
            } elseif (!$zoningCheck) {
                $error = "<strong>Cannot move to \"{$statusLabel}\":</strong> Zoning &amp; Land Verification has not been completed yet. "
                       . "Please open the GIS Map from the <em>Zoning &amp; Actions</em> tab and save the spatial verification first.";
            }
        }
        // ── END PREREQUISITE CHECK ───────────────────────────────────────────

        if (!empty($error)) {
            // Skip the DB update entirely; the error will be displayed by the
            // existing $error alert block at the top of the page.
        } else
        try {
            $dbConn->beginTransaction();

            // 1. Update the application status
            $stmt = $dbConn->prepare("UPDATE applications SET status = :status, updated_at = NOW() WHERE id = :id");
            $stmt->execute([':status' => $newStatus, ':id' => $applicationId]);

            // 2. Add to Status History
            $stmtHistory = $dbConn->prepare("INSERT INTO application_status_history (application_id, status, remarks, changed_by) VALUES (?, ?, ?, ?)");
            $stmtHistory->execute([$applicationId, $newStatus, $remarks, $officerId]);

            // 3. Inspections
            if ($newStatus === 'approved') {
                $exists = $db->fetchOne("SELECT id FROM inspections WHERE application_id = ?", [$applicationId]);
                if (!$exists) {
                    // Siguraduhing 'inspection' ang status dito
                    $stmtIns = $dbConn->prepare("INSERT INTO inspections (application_id, status, created_at) VALUES (?, 'inspection', NOW())");
                    $stmtIns->execute([$applicationId]);
                }
            }

// 4. SET DYNAMIC SUBJECT & MESSAGE BODY
        $statusLabel = strtoupper(str_replace('_', ' ', $newStatus));
        
        if ($newStatus === 'approved') {
            $subject = "CONGRATULATIONS: Approved Locational Clearance / Permit #" . $application['application_number'];
            
            $messageBody = "Dear Applicant,\n\n";
            $messageBody .= "We are pleased to inform you that your application for '" . $application['project_name'] . "' has been officially APPROVED.\n\n";
            $messageBody .= "Your Locational Clearance / Permit has been generated. You may download and print the official document from the 'Documents' section of your portal. A copy has also been sent to your registered email address.\n\n";
            $messageBody .= "Permit Details:\n";
            $messageBody .= "- Permit No: " . $application['application_number'] . "\n";
            $messageBody .= "- Location: Barangay " . $application['barangay'] . "\n\n";
            $messageBody .= "Office Remarks:\n\"" . $remarks . "\"\n\n";
            $messageBody .= "Thank you for your cooperation.\n\n";
        } else {
            $subject = "Official Update: Application #" . $application['application_number'];
            
            $messageBody = "Dear Applicant,\n\n";
            $messageBody .= "This is an official notification regarding your application: " . $application['project_name'] . ".\n\n";
            $messageBody .= "The status has been updated to: " . $statusLabel . ".\n";
            $messageBody .= "Location: Barangay " . $application['barangay'] . ", Block " . ($application['block'] ?? 'N/A') . ", Street " . ($application['street'] ?? 'N/A') . "\n\n";
            $messageBody .= "Remarks from Office:\n\"" . $remarks . "\"\n\n";
            $messageBody .= "You may monitor further progress through your portal.\n\n";
        }
        $messageBody .= "Quezon City Urban Planning Department";

        // 5. INSERT IN-SYSTEM MESSAGE (still inside the transaction)
        $stmtMsg = $dbConn->prepare("INSERT INTO messages (application_id, sender_id, receiver_id, subject, message, message_type, created_at) VALUES (?, ?, ?, ?, ?, 'system', NOW())");
        $stmtMsg->execute([$applicationId, $officerId, $applicantId, $subject, $messageBody]);

        // 6. COMMIT everything (status + history + inspection row + message)
        $dbConn->commit();
        $success = 'Application status updated and notification sent.';

        // ── AUTO-GENERATE PERMIT PDF ON FINAL APPROVAL ──────────────────────
        if ($newStatus === 'approved') {
            $permitGenerated = false;
            $pdfFilename     = '';

            try {
                // Reuse the same PDF-generation logic inline via require,
                // but capture output so we can store the file path.
                $safeNo      = preg_replace('/[^A-Za-z0-9\-_]/', '_', $application['application_number']);
                $pdfFilename = "Locational_Clearance_{$safeNo}.pdf";
                $uploadDir   = __DIR__ . '/../uploads/permits/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Only regenerate if file does not already exist
                $savePath = $uploadDir . $pdfFilename;
                if (!file_exists($savePath)) {
                    // Delegate to generator script (outputs to file, not browser)
                    // Pass a flag so the generator saves to disk without streaming.
                    $_POST['_save_only']    = true;
                    $_POST['application_id'] = $applicationId;
                    ob_start();
                include __DIR__ . '/../modules/PermitProcessing/generate_permit_pdf.php';
                    ob_end_clean();
                }

                // Record in application_documents table if not already present
                $existing = $db->fetchOne(
                    "SELECT id FROM application_documents WHERE application_id = ? AND document_type = 'permit_certificate'",
                    [$applicationId]
                );
                if (!$existing) {
                    $dbConn->prepare(
                        "INSERT INTO application_documents (application_id, uploaded_by, document_type, file_name, file_path, created_at)
                         VALUES (?, ?, 'permit_certificate', ?, ?, NOW())"
                    )->execute([
                        $applicationId,
                        $_SESSION['user_id'],
                        $pdfFilename,
                        'uploads/permits/' . $pdfFilename
                    ]);
                }

                $permitGenerated = true;
                $success .= ' <strong>Locational Clearance PDF has been generated and attached to the application.</strong>';

                // ── SEND EMAIL WITH PDF ATTACHMENT VIA PHPMAILER (GMAIL SMTP) ──
                $applicantEmail = $application['applicant_email'] ?? '';
                if (!empty($applicantEmail) && file_exists($savePath)) {
                    try {
                        // PHPMailer — same autoload used by the rest of the system
                        require_once __DIR__ . '/../vendor/autoload.php';
                        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                        // ── SMTP credentials (Gmail) ─────────────────────────
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'aelousssnexus@gmail.com';
                        $mail->Password   = 'zuey mjni sbzz gvsm';
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;
                        $mail->CharSet    = 'UTF-8';

                        // ── Sender & recipient ───────────────────────────────
                        $mail->setFrom('aelousssnexus@gmail.com', 'LGU Urban Planning');
                        $mail->addAddress(
                            $applicantEmail,
                            trim($application['applicant_first_name'] . ' ' . $application['applicant_last_name'])
                        );

                        // ── Attach the generated PDF ─────────────────────────
                        $mail->addAttachment($savePath, $pdfFilename);

                        // ── Subject & HTML body ──────────────────────────────
                        $mail->isHTML(true);
                        $mail->Subject = 'Your Locational Clearance is Ready – Permit #' . $application['application_number'];

                        $applicantFullName = htmlspecialchars(
                            trim($application['applicant_first_name'] . ' ' . $application['applicant_last_name'])
                        );
                        $mail->Body = '
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;color:#222;margin:0;padding:0;background:#f4f6f9;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:30px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);">

        <!-- Header -->
        <tr>
          <td style="background:#003366;padding:24px 32px;text-align:center;">
            <h2 style="color:#fff;margin:0;font-size:18px;letter-spacing:1px;">
              QUEZON CITY URBAN PLANNING DEPARTMENT
            </h2>
            <p style="color:#aac4e8;margin:6px 0 0;font-size:12px;">
              Official Notification – Locational Clearance
            </p>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:32px;">
            <p style="margin:0 0 16px;">Dear <strong>' . $applicantFullName . '</strong>,</p>
            <p style="margin:0 0 16px;">
              🎉 Congratulations! Your application has been officially <strong style="color:#1a7a3c;">APPROVED</strong>.
              Your <strong>Locational Clearance / Permit</strong> is attached to this email as a PDF file.
            </p>

            <!-- Permit details box -->
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="background:#f0f5ff;border:1px solid #c8d8f5;border-radius:6px;margin:20px 0;">
              <tr>
                <td style="padding:16px 20px;">
                  <p style="margin:0 0 8px;font-size:13px;color:#555;text-transform:uppercase;letter-spacing:.5px;font-weight:bold;">
                    Permit Details
                  </p>
                  <table cellpadding="4" cellspacing="0" style="font-size:14px;width:100%;">
                    <tr>
                      <td style="color:#555;width:140px;">Permit No.</td>
                      <td><strong>' . htmlspecialchars($application['application_number']) . '</strong></td>
                    </tr>
                    <tr>
                      <td style="color:#555;">Project</td>
                      <td><strong>' . htmlspecialchars($application['project_name']) . '</strong></td>
                    </tr>
                    <tr>
                      <td style="color:#555;">Location</td>
                      <td>Barangay ' . htmlspecialchars($application['barangay']) . '</td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- Remarks -->
            <p style="margin:0 0 8px;font-size:13px;color:#555;">Office Remarks:</p>
            <blockquote style="margin:0 0 20px;padding:10px 16px;background:#f9f9f9;border-left:4px solid #003366;
                               font-style:italic;color:#444;border-radius:0 4px 4px 0;">
              ' . nl2br(htmlspecialchars($remarks)) . '
            </blockquote>

            <p style="margin:0 0 20px;font-size:14px;">
              You may also download the document anytime from the
              <strong>Documents</strong> section of your applicant portal.
            </p>

            <p style="margin:0;font-size:14px;">Thank you for your cooperation.</p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#f0f0f0;padding:16px 32px;text-align:center;font-size:11px;color:#888;">
            This is a system-generated email. Please do not reply directly to this message.<br>
            © ' . date('Y') . ' Quezon City Urban Planning Department
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>';

                        // Plain-text fallback
                        $mail->AltBody =
                            "Dear " . trim($application['applicant_first_name'] . ' ' . $application['applicant_last_name']) . ",\r\n\r\n" .
                            "Congratulations! Your application for \"" . $application['project_name'] . "\" has been officially APPROVED.\r\n\r\n" .
                            "Permit No : " . $application['application_number'] . "\r\n" .
                            "Project   : " . $application['project_name'] . "\r\n" .
                            "Location  : Barangay " . $application['barangay'] . "\r\n\r\n" .
                            "Office Remarks:\r\n\"" . $remarks . "\"\r\n\r\n" .
                            "The Locational Clearance PDF is attached to this email.\r\n\r\n" .
                            "Quezon City Urban Planning Department";

                        $mail->send();
                        $success .= ' <strong>Permit PDF has been emailed to ' . htmlspecialchars($applicantEmail) . '.</strong>';

                    } catch (Exception $mailEx) {
                        $success .= ' <span class="text-warning">(Email notice: ' . htmlspecialchars($mailEx->getMessage()) . ')</span>';
                    }
                }
                // ── END EMAIL SEND ───────────────────────────────────────────

            } catch (Exception $pdfEx) {
                // PDF failure should not roll back the status update.
                $success .= ' (Note: PDF generation encountered an issue: ' . htmlspecialchars($pdfEx->getMessage()) . ')';
            }
        }
        // ── END PDF GENERATION ───────────────────────────────────────────────

        // Sync in-memory status so the badge and PDF panel render correctly on this page load
        $application['status'] = $newStatus;

    } catch (Exception $e) {
        $dbConn->rollBack();
        $error = "Update failed: " . $e->getMessage();
    }
}
}

// --- Pagination ---
$limit = 10; 
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $limit;

// Count total records for the pagination calculation
$countResult = $db->fetchOne("SELECT COUNT(*) as total FROM application_status_history WHERE application_id = ?", [$applicationId]);
$totalRecords = $countResult['total'] ?? 0;
$totalPages = ceil($totalRecords / $limit); 
// --------------------------------------

// Update your history fetch to use the LIMIT and OFFSET
$historyRecords = $db->fetchAll(
    "SELECT h.*, u.first_name, u.last_name 
     FROM application_status_history h 
     LEFT JOIN users u ON h.changed_by = u.id 
     WHERE h.application_id = ? 
     ORDER BY h.created_at DESC 
     LIMIT ? OFFSET ?", 
    [$applicationId, $limit, $offset]
);

// Officers list — filtered by the role of the logged-in staff
// admin/super_admin see all staff; other roles only see peers in their own department
$sessionRole = $_SESSION['role'] ?? '';
if (in_array($sessionRole, ['admin', 'super_admin'])) {
    $officerRoleFilter = "('super_admin', 'admin', 'zoning_officer', 'building_official')";
} elseif ($sessionRole === 'zoning_officer') {
    $officerRoleFilter = "('zoning_officer')";
} elseif ($sessionRole === 'building_official') {
    $officerRoleFilter = "('building_official', 'zoning_officer')";
} else {
    $officerRoleFilter = "('')"; // assessor, inspector and others — no assign access
}
$officers = $db->fetchAll(
    "SELECT id, first_name, last_name, role 
     FROM users 
     WHERE is_active = 1 
     AND role IN {$officerRoleFilter}
     ORDER BY last_name ASC"
);

$pageTitle = 'Application Details';
$isAuthPage = true;
include __DIR__ . '/../admin/header.php';
?>

<style>
    /* ── BASE ── */
    .border-dashed {
        border-style: dashed !important;
        border-width: 2px !important;
    }
    .pulse-warning {
        width: 10px; height: 10px;
        background-color: #ffc107;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
        box-shadow: 0 0 0 rgba(255, 193, 7, 0.4);
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0%   { box-shadow: 0 0 0 0   rgba(255, 193, 7, 0.7); }
        70%  { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
        100% { box-shadow: 0 0 0 0   rgba(255, 193, 7, 0); }
    }

    /* ================================================
       MOBILE RESPONSIVE
       768px (Tablet) | 480px (Large Mobile) | 320px (Small Mobile)
       ================================================ */

    /* --- 768px: Tablet --- */
    @media (max-width: 768px) {

        /* Page padding */
        .p-4 { padding: 1rem !important; }

        /* Page title */
        .mb-4 h2 { font-size: 1.25rem; }

        /* Card header: stack app number + status badge */
        .card-header.d-flex.justify-content-between {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 8px;
        }
        .card-header .d-flex.align-items-center {
            flex-wrap: wrap;
            gap: 6px;
        }
        .card-header .me-3 { margin-right: 0 !important; font-size: 0.78rem; }

        /* Nav pills: allow wrapping */
        #appTabs {
            flex-wrap: wrap;
            gap: 4px;
        }
        #appTabs .nav-link {
            font-size: 0.8rem;
            padding: 6px 10px;
        }

        /* Details tab: stack 2 columns */
        #details .row > .col-md-7,
        #details .row > .col-md-5 {
            width: 100%;
            flex: 0 0 100%;
            border-right: none !important;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }

        /* Coordinates block: stack lat/lng */
        .p-3.bg-primary-subtle .row.align-items-center { flex-wrap: wrap; }
        .p-3.bg-primary-subtle code { display: block; margin: 2px 0; }
        .p-3.bg-primary-subtle .mx-2 { display: none; }

        /* Technical Assessment tab: stack cards */
        #impact .col-md-6 { width: 100%; flex: 0 0 100%; }

        /* Assessment header: wrap badge */
        #impact .d-flex.justify-content-between.align-items-center.mb-4 {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 8px;
        }
        #impact .d-flex.justify-content-between.align-items-center.mb-4 form { width: 100%; }
        #impact .d-flex.justify-content-between.align-items-center.mb-4 .btn { width: 100%; }

        /* Documents table */
        .table { font-size: 0.8rem; }
        .table th, .table td { padding: 0.5rem 0.4rem; }
        /* Hide Uploaded By on tablet */
        #docs .table thead th:nth-child(3),
        #docs .table tbody td:nth-child(3) { display: none; }

        /* Actions tab: stack 2 columns */
        #actions .row > .col-md-6 {
            width: 100%;
            flex: 0 0 100%;
            border-right: none !important;
            margin-bottom: 1.25rem;
        }

        /* History timeline: tighten spacing */
        #history .d-flex.justify-content-between { flex-wrap: wrap; gap: 2px; }

        /* Back button */
        .mt-4.mb-4 .btn { width: 100%; text-align: center; }

        /* Doc viewer modal */
        .modal-dialog.modal-xl { margin: 0.5rem; }
        #docViewerModal .modal-body { height: 70vh !important; }
    }

    /* --- 480px: Large Mobile --- */
    @media (max-width: 480px) {

        .p-4 { padding: 0.75rem !important; }

        /* Page title */
        .mb-4 h2 { font-size: 1.05rem; }

        /* Card header */
        .card-header { padding: 0.65rem 0.75rem !important; }
        .card-header h5 { font-size: 0.88rem; }
        .card-header .badge { font-size: 0.7rem; padding: 4px 8px; }
        .card-header .me-3 { font-size: 0.72rem; }

        /* Nav pills */
        #appTabs .nav-link {
            font-size: 0.72rem;
            padding: 5px 8px;
        }

        /* Details tab inner cards */
        .card-body { padding: 0.75rem !important; }
        .card-body .row.g-3 { --bs-gutter-y: 0.5rem; }
        .card-body h6 { font-size: 0.82rem; }
        .card-body small, .card-body .small { font-size: 0.75rem; }

        /* Coordinates */
        .p-3.bg-primary-subtle { padding: 0.6rem !important; }
        .p-3.bg-primary-subtle code { font-size: 0.75rem; }
        .p-3.bg-primary-subtle .rounded-circle {
            width: 32px !important; height: 32px !important;
            padding: 0.3rem !important;
        }

        /* Technical Assessment */
        #impact .d-flex.justify-content-between.align-items-start { flex-wrap: wrap; gap: 6px; }
        #impact .card-body { padding: 0.65rem !important; }

        /* Documents table: also hide Date col */
        .table { font-size: 0.72rem; }
        .table th, .table td { padding: 0.35rem 0.3rem; }
        #docs .table thead th:nth-child(3),
        #docs .table tbody td:nth-child(3),
        #docs .table thead th:nth-child(4),
        #docs .table tbody td:nth-child(4) { display: none; }
        .table .badge { font-size: 0.62rem; padding: 2px 5px; }
        .btn-group .btn-sm { padding: 3px 7px; font-size: 0.72rem; }

        /* Actions tab forms */
        #actions form.p-3 { padding: 0.65rem !important; }
        #actions .form-select,
        #actions .form-control { font-size: 0.82rem; padding: 6px 9px; }
        #actions label.small { font-size: 0.75rem; }
        #actions textarea { rows: 3; font-size: 0.82rem; }

        /* Zoning section */
        #actions .p-3.border { padding: 0.65rem !important; }
        #actions .btn.w-100.py-2 { padding: 8px !important; font-size: 0.82rem; }

        /* History */
        #history .border-start { padding-left: 0.75rem !important; }
        #history strong.text-primary { font-size: 0.75rem; }
        #history p.small { font-size: 0.75rem; }
        #history small { font-size: 0.68rem; }

        /* Pagination */
        .pagination { flex-wrap: wrap; gap: 2px; }
        .page-link { padding: 4px 8px; font-size: 0.72rem; }

        /* Toasts */
        .toast { font-size: 0.78rem; padding: 0.6rem 0.75rem; }

        /* Doc viewer modal */
        #docViewerModal .modal-body { height: 60vh !important; }
        #docViewerModal .modal-title { font-size: 0.88rem; }
    }

    /* --- 320px: Small Mobile --- */
    @media (max-width: 320px) {

        .p-4 { padding: 0.5rem !important; }

        /* Title */
        .mb-4 h2 { font-size: 0.95rem; }

        /* Card header: app number on top, badge inline below */
        .card-header { padding: 0.6rem 0.75rem !important; }
        .card-header.d-flex.justify-content-between {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 6px;
        }
        .card-header h5 { font-size: 0.85rem; margin-bottom: 0; }
        .card-header .badge { font-size: 0.68rem; padding: 4px 10px; }
        .card-header .me-3 { display: none; } /* hide "Current Phase" — too cramped */
        .card-header .d-flex.align-items-center { gap: 0; }

        /* Nav pills: single scrollable row — no orphaned items */
        #appTabs {
            flex-wrap: nowrap !important;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            gap: 4px;
            padding-bottom: 4px;
        }
        #appTabs::-webkit-scrollbar { display: none; }
        #appTabs .nav-item { flex-shrink: 0; }
        #appTabs .nav-link {
            font-size: 0.7rem;
            padding: 5px 10px;
            white-space: nowrap;
            border-radius: 20px;
        }

        /* Details tab */
        .card-body { padding: 0.5rem !important; }
        .card-body h6 { font-size: 0.75rem; }
        .card-body .fw-bold { font-size: 0.82rem; }
        .card-body small, .card-body .small { font-size: 0.68rem; }
        .card-body .row.g-3 { --bs-gutter-y: 0.35rem; }

        /* Coordinates */
        .p-3.bg-primary-subtle { padding: 0.5rem !important; }
        .p-3.bg-primary-subtle code { font-size: 0.68rem; }
        .p-3.bg-primary-subtle .col-auto { display: none; } /* hide icon circle */

        /* Technical Assessment */
        #impact h6 { font-size: 0.78rem; }
        #impact .card-body small { font-size: 0.68rem; }

        /* Documents table: keep only Type + Action */
        .table { font-size: 0.65rem; }
        .table th, .table td { padding: 0.3rem 0.2rem; }
        #docs .table thead th:nth-child(2),
        #docs .table tbody td:nth-child(2),
        #docs .table thead th:nth-child(3),
        #docs .table tbody td:nth-child(3),
        #docs .table thead th:nth-child(4),
        #docs .table tbody td:nth-child(4) { display: none; }

        /* Actions tab */
        #actions h6 { font-size: 0.78rem; }
        #actions form.p-3 { padding: 0.5rem !important; }
        #actions .form-select,
        #actions .form-control { font-size: 0.78rem; padding: 5px 8px; }
        #actions label.small { font-size: 0.68rem; }
        #actions .btn { font-size: 0.78rem; padding: 6px 10px; }

        /* Zoning */
        #actions .p-3.border { padding: 0.5rem !important; }
        #actions .btn.w-100.py-2 { font-size: 0.75rem; padding: 6px !important; }

        /* History */
        #history h6 { font-size: 0.78rem; }
        #history .border-start { padding-left: 0.6rem !important; margin-left: 0.25rem; }
        #history strong.text-primary { font-size: 0.68rem; }
        #history p.small { font-size: 0.68rem; }
        #history small { font-size: 0.62rem; }
        #history .d-flex.justify-content-between { flex-direction: column; gap: 0; }

        /* Pagination */
        .page-link { padding: 3px 6px; font-size: 0.65rem; }

        /* Back button */
        .mt-4.mb-4 .btn { font-size: 0.78rem; padding: 7px 12px; }

        /* Toasts */
        .toast { font-size: 0.72rem; padding: 0.5rem 0.6rem; }

        /* Doc viewer modal */
        .modal-dialog.modal-xl { margin: 0.15rem; }
        #docViewerModal .modal-body { height: 55vh !important; }
        #docViewerModal .modal-title { font-size: 0.82rem; }
    }
</style>

<div class="p-4">
    <div class="mb-4">
        <h2><?php echo htmlspecialchars($_t('page_title')); ?></h2>
    </div>
    
    <!-- ── TOAST CONTAINER ──────────────────────────────────────────────── -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
        <?php if ($error): ?>
        <div id="toastError" class="toast align-items-center text-bg-danger border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="false">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div id="toastSuccess" class="toast align-items-center text-bg-success border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="6000">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-check-circle-fill me-2"></i><?php echo $success; ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <!-- ── END TOAST CONTAINER ──────────────────────────────────────────── -->

    <?php if ($error || $success): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        <?php if ($error): ?>
        var toastError = document.getElementById('toastError');
        if (toastError) new bootstrap.Toast(toastError).show();
        <?php endif; ?>
        <?php if ($success): ?>
        var toastSuccess = document.getElementById('toastSuccess');
        if (toastSuccess) new bootstrap.Toast(toastSuccess).show();
        <?php endif; ?>
    });
    </script>
    <?php endif; ?>
    
    <div class="card mb-3 shadow-sm border-0">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h5 class="mb-0">Application #<?php echo htmlspecialchars($application['application_number']); ?></h5>
                <?php 
                    $recordType = strtolower($application['record_type'] ?? '');
                    $isWalkIn = ($recordType === 'walk-in' || $recordType === 'manual');
                ?>
            </div>
            <div class="d-flex align-items-center">
                <span class="me-3 text-muted small"><?php echo htmlspecialchars($_t('current_phase')); ?> <strong><?php echo htmlspecialchars($_t('phase_name')); ?></strong></span>
                <span class="badge bg-<?php echo Helper::getStatusBadge($application['status']); ?> p-2 px-3">
                    <?php echo strtoupper(str_replace('_', ' ', $application['status'])); ?>
                </span>
            </div>
        </div>
        <div class="card-body">
            <ul class="nav nav-pills mb-4 border-bottom pb-3" id="appTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab"><?php echo htmlspecialchars($_t('tab_details')); ?></button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="impact-tab" data-bs-toggle="tab" data-bs-target="#impact" type="button" role="tab"><?php echo htmlspecialchars($_t('tab_impact')); ?></button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="docs-tab" data-bs-toggle="tab" data-bs-target="#docs" type="button" role="tab"><?php echo htmlspecialchars($_t('tab_docs')); ?></button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="actions-tab" data-bs-toggle="tab" data-bs-target="#actions" type="button" role="tab"><?php echo htmlspecialchars($_t('tab_actions')); ?></button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab"><?php echo htmlspecialchars($_t('tab_history')); ?></button>
                </li>
            </ul>
            
            <div class="tab-content pt-2">
                <div class="tab-pane fade show active" id="details" role="tabpanel">
                    <div class="row">
                        <div class="col-md-7 border-end">
                            <h6 class="fw-bold text-primary mb-3 text-uppercase small">
                                <i class="bi bi-info-square-fill me-2"></i><?php echo htmlspecialchars($_t('section_project')); ?>
                            </h6>
                            <div class="row mb-4">
                                <div class="col-sm-6">
                                    <label class="text-muted d-block small"><?php echo htmlspecialchars($_t('lbl_project_name')); ?></label>
                                    <span class="fw-bold"><?php echo htmlspecialchars($application['project_name']); ?></span>
                                </div>
                                <div class="col-sm-6">
                                    <label class="text-muted d-block small"><?php echo htmlspecialchars($_t('lbl_project_type')); ?></label>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2">
                                        <?php echo htmlspecialchars($application['project_type'] ?? 'N/A'); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="text-muted d-block small"><?php echo htmlspecialchars($_t('lbl_description')); ?></label>
                                <p class="small text-dark bg-light p-3 rounded border shadow-sm">
                                    <?php echo nl2br(htmlspecialchars($application['project_description'] ?? $_t('no_description'))); ?>
                                </p>
                            </div>
                            <div class="card border-0 shadow-sm bg-light mb-4">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                        <h6 class="fw-bold x-small text-uppercase text-muted mb-0"><?php echo htmlspecialchars($_t('section_identifiers')); ?></h6>
                                        <?php if (in_array($_SESSION['role'], ['admin', 'super_admin', 'assessor'])): ?>
                                        <button type="button" class="btn btn-outline-success btn-sm px-3" data-bs-toggle="modal" data-bs-target="#editParcelModal">
                                            <i class="bi bi-pencil-square me-1"></i> Edit
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <small class="text-muted d-block x-small"><?php echo htmlspecialchars($_t('lbl_lot_block')); ?></small>
                                            <span class="fw-bold text-dark">Lot <?php echo htmlspecialchars($application['lot_number'] ?? '---'); ?>, Block <?php echo htmlspecialchars($application['block'] ?? '---'); ?></span>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted d-block x-small"><?php echo htmlspecialchars($_t('lbl_street')); ?></small>
                                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($application['street'] ?? '---'); ?></span>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted d-block x-small"><?php echo htmlspecialchars($_t('lbl_barangay')); ?></small>
                                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($application['barangay'] ?? '---'); ?></span>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted d-block small"><?php echo htmlspecialchars($_t('lbl_parcel_id')); ?></label>
                                            <span class="badge bg-dark">
                                                <?php 
                                                    echo htmlspecialchars($application['parcel_id'] ?? 'UNLINKED'); 
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3 bg-primary-subtle border-start border-primary border-4 rounded-end shadow-sm">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="bi bi-geo-alt-fill"></i></div>
                                    </div>
                                    <div class="col">
                                        <label class="d-block fw-bold text-primary x-small text-uppercase"><?php echo htmlspecialchars($_t('lbl_coordinates')); ?></label>
                                        <code class="text-dark small">Latitude: <?php echo htmlspecialchars($application['latitude']); ?></code>
                                        <span class="mx-2 text-muted">|</span>
                                        <code class="text-dark small">Longitude: <?php echo htmlspecialchars($application['longitude']); ?></code>
                                    </div>
                                </div>
                            </div>
                        </div>
                                                <div class="col-md-5">
                            <h6 class="fw-bold text-primary mb-3 text-uppercase small"><i class="bi bi-person-badge-fill me-2"></i><?php echo htmlspecialchars($_t('section_applicant')); ?></h6>
                            <div class="card shadow-sm mb-3">
                                <div class="card-body">
                                    <div class="mb-3 border-bottom pb-2">
                                        <label class="text-muted d-block x-small"><?php echo htmlspecialchars($_t('lbl_applicant_id')); ?></label>
                                        <span class="fw-bold">#<?php echo htmlspecialchars($application['id']); ?></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="text-muted d-block x-small"><?php echo htmlspecialchars($_t('lbl_full_name')); ?></label>
                                        <span class="fw-bold d-block"><?php echo htmlspecialchars($application['applicant_first_name'] . ' ' . $application['applicant_last_name']); ?></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="text-muted d-block x-small"><?php echo htmlspecialchars($_t('lbl_email')); ?></label>
                                        <a href="mailto:<?php echo htmlspecialchars($application['applicant_email']); ?>" class="text-decoration-none"><i class="bi bi-envelope-at me-1"></i><?php echo htmlspecialchars($application['applicant_email']); ?></a>
                                    </div>
                                    <div class="mb-3">
                                        <label class="text-muted d-block x-small"><?php echo htmlspecialchars($_t('lbl_phone')); ?></label>
                                        <span class="fw-bold"><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($application['applicant_phone'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div class="mt-3 p-2 rounded <?php echo $isWalkIn ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' : 'bg-info-subtle text-info-emphasis border border-info-subtle'; ?> small">
                                        <i class="bi <?php echo $isWalkIn ? 'bi-person-workspace' : 'bi-globe'; ?> me-1"></i>
                                        <strong><?php echo htmlspecialchars($_t('lbl_record_type')); ?></strong> 
                                        <span class="fw-bold">
                                            <?php echo $isWalkIn ? htmlspecialchars($_t('record_walkin')) : htmlspecialchars($_t('record_online')); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

<div class="tab-pane fade" id="impact" role="tabpanel">
    <?php if ($_SESSION['role'] === 'inspector'): ?>
        <div class="d-flex align-items-start gap-2 px-3 py-2 mb-3 rounded border border-secondary-subtle bg-secondary-subtle small">
            <i class="bi bi-info-circle text-secondary mt-1 flex-shrink-0"></i>
            <span class="text-secondary">
                <?php echo htmlspecialchars($_t('impact_inspector_note')); ?> <b><?php echo htmlspecialchars($_t('impact_inspector_link')); ?></b> <?php echo htmlspecialchars($_t('impact_inspector_end')); ?>
            </span>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($_t('impact_heading')); ?></h6>
            <small class="text-muted"><?php echo htmlspecialchars($_t('impact_subtitle')); ?></small>
        </div>
        <?php if ($_SESSION['role'] !== 'inspector' && $_SESSION['role'] !== 'zoning_officer' && $_SESSION['role'] !== 'assessor'): ?>
            <form method="POST">
                <input type="hidden" name="action" value="request_inspection">
                <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm">
                    <i class="bi bi-megaphone-fill me-1"></i> <?php echo htmlspecialchars($_t('btn_simulate')); ?>
                </button>
            </form>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card h-100 border-0 bg-light shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="text-primary fw-bold mb-0"><i class="bi bi-road-front-fill me-2"></i><?php echo htmlspecialchars($_t('roads_title')); ?></h6>
                            <small class="text-muted"><?php echo htmlspecialchars($_t('roads_subtitle')); ?></small>
                        </div>
                        <?php if ($impactAssessment && !empty($impactAssessment['traffic_flag'])): ?>
                            <?php
                                $trafficFlag = $impactAssessment['traffic_flag'];
                                $badgeClass = $trafficFlag === 'pending'
                                    ? 'warning'
                                    : (($trafficFlag === 'ok' || $trafficFlag === 'approved') ? 'success' : 'danger');
                            ?>
                            <span class="badge bg-<?php echo $badgeClass; ?>"><?php echo strtoupper($trafficFlag); ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($_t('awaiting_inspection')); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="bg-white p-3 rounded border">
                        <p class="mb-2 small fw-bold text-muted text-uppercase" style="letter-spacing:.03em;"><?php echo htmlspecialchars($_t('assessment_data')); ?></p>
                        <?php
                            $trafficPairs = parse_assessment_notes($impactAssessment['traffic_notes'] ?? null);
                            $trafficRemarks = $trafficPairs['Remarks'] ?? null;
                            if ($trafficPairs) unset($trafficPairs['Remarks']);
                        ?>
                        <?php if ($trafficPairs): ?>
                            <div class="row row-cols-2 g-3 mb-2">
                                <?php foreach ($trafficPairs as $label => $value): $badge = assessment_badge_class($value); ?>
                                    <div class="col">
                                        <div class="text-muted" style="font-size:.72rem;"><?php echo htmlspecialchars($label); ?></div>
                                        <?php if ($badge): ?>
                                            <span class="badge bg-<?php echo $badge; ?> fw-semibold"><?php echo htmlspecialchars($value); ?></span>
                                        <?php else: ?>
                                            <div class="fw-semibold text-dark small"><?php echo htmlspecialchars($value); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($trafficRemarks): ?>
                                <div class="pt-2 border-top">
                                    <div class="text-muted" style="font-size:.72rem;">Remarks</div>
                                    <div class="small text-dark"><?php echo htmlspecialchars($trafficRemarks); ?></div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="small mb-0 text-dark">
                                <?php echo htmlspecialchars($impactAssessment['traffic_notes'] ?? $_t('no_roads_data')); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 border-0 bg-light shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="text-warning fw-bold mb-0"><i class="bi bi-lightning-charge-fill me-2"></i><?php echo htmlspecialchars($_t('utilities_title')); ?></h6>
                            <small class="text-muted"><?php echo htmlspecialchars($_t('utilities_subtitle')); ?></small>
                        </div>
                        <?php if ($impactAssessment && !empty($impactAssessment['energy_flag'])): ?>
                            <span class="badge bg-<?php echo ($impactAssessment['energy_flag'] === 'ok' || $impactAssessment['energy_flag'] === 'approved') ? 'success' : 'danger'; ?>"><?php echo strtoupper($impactAssessment['energy_flag']); ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($_t('awaiting_inspection')); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="bg-white p-3 rounded border">
                        <p class="mb-2 small fw-bold text-muted text-uppercase" style="letter-spacing:.03em;"><?php echo htmlspecialchars($_t('assessment_data')); ?></p>
                        <?php
                            $energyPairs = parse_assessment_notes($impactAssessment['energy_notes'] ?? null);
                            $energyRemarks = $energyPairs['Remarks'] ?? null;
                            if ($energyPairs) unset($energyPairs['Remarks']);
                        ?>
                        <?php if ($energyPairs): ?>
                            <div class="row row-cols-2 g-3 mb-2">
                                <?php foreach ($energyPairs as $label => $value): $badge = assessment_badge_class($value); ?>
                                    <div class="col">
                                        <div class="text-muted" style="font-size:.72rem;"><?php echo htmlspecialchars($label); ?></div>
                                        <?php if ($badge): ?>
                                            <span class="badge bg-<?php echo $badge; ?> fw-semibold"><?php echo htmlspecialchars($value); ?></span>
                                        <?php else: ?>
                                            <div class="fw-semibold text-dark small"><?php echo htmlspecialchars($value); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($energyRemarks): ?>
                                <div class="pt-2 border-top">
                                    <div class="text-muted" style="font-size:.72rem;">Remarks</div>
                                    <div class="small text-dark"><?php echo htmlspecialchars($energyRemarks); ?></div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="small mb-0 text-dark">
                                <?php echo htmlspecialchars($impactAssessment['energy_notes'] ?? $_t('no_energy_data')); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
                <div class="tab-pane fade" id="docs" role="tabpanel">
                    <h6 class="fw-bold mb-3"><?php echo htmlspecialchars($_t('docs_heading')); ?></h6>
                    <?php if (empty($application['documents'])): ?>
                        <div class="text-center p-5 border rounded bg-light">
                            <i class="bi bi-file-earmark-x fs-1 text-muted"></i>
                            <p class="text-muted mt-2"><?php echo htmlspecialchars($_t('no_docs')); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th><?php echo htmlspecialchars($_t('col_doc_type')); ?></th>
                                        <th><?php echo htmlspecialchars($_t('col_file_name')); ?></th>
                                        <th><?php echo htmlspecialchars($_t('col_uploaded_by')); ?></th>
                                        <th><?php echo htmlspecialchars($_t('col_date')); ?></th>
                                        <th class="text-end"><?php echo htmlspecialchars($_t('col_action')); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($application['documents'] as $doc):
                                        // Skip permit certificates — shown in Zoning & Actions tab.
                                        if (($doc['document_type'] ?? '') === 'permit_certificate') continue;

                                        // Clean display name — strip any Windows absolute path prefix.
                                        $rawName   = $doc['file_name'] ?? '';
                                        $cleanName = basename(str_replace('\\', '/', $rawName));

                                        // file_path holds the actual stored filename (hashed on upload,
                                        // e.g. "69fcc1f86a1ee_1778172408.jpg").
                                        // file_name holds the original display name (e.g. "commercial-plan.jpg").
                                        // We must use file_path for the URL so the browser can find the real file.
                                        $rawPath     = $doc['file_path'] ?? '';
                                        $storedFile  = basename(str_replace('\\', '/', $rawPath));
                                        // Fall back to cleanName for older records that have no file_path.
                                        $urlFilename = ($storedFile !== '') ? $storedFile : $cleanName;
                                        $directUrl   = htmlspecialchars(
                                            '/lgu-urban-planning/uploads/documents/' . $urlFilename,
                                            ENT_QUOTES
                                        );
                                    ?>
                                    <tr>
                                        <td><span class="badge bg-secondary"><?php echo strtoupper(str_replace('_', ' ', $doc['document_type'])); ?></span></td>
                                        <td><?php echo htmlspecialchars($cleanName); ?></td>
                                        <td><small><?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?></small></td>
                                        <td><small><?php echo Helper::formatDate($doc['created_at']); ?></small></td>
                                        <td class="text-end">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-info"
                                                    onclick="viewDocument(<?php echo $doc['id']; ?>, '<?php echo htmlspecialchars($cleanName, ENT_QUOTES); ?>', '<?php echo $directUrl; ?>')">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <a href="/lgu-urban-planning/documents/download.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="actions" role="tabpanel">
                    <?php if (in_array($_SESSION['role'], ['admin', 'super_admin', 'zoning_officer', 'building_official'])): ?>
                    <div class="row">
                        <div class="col-md-6 border-end">
                            <h6 class="fw-bold mb-3"><?php echo htmlspecialchars($_t('actions_heading')); ?></h6>
                            <form method="POST" class="mb-3 p-3 bg-light rounded border shadow-sm">
                                <input type="hidden" name="action" value="update_status">
                                <div class="mb-3">
                                    <label class="small fw-bold mb-1"><?php echo htmlspecialchars($_t('lbl_set_status')); ?></label>
                                    <select class="form-select border-primary" name="status" required>
                                        <?php
                                        $currentRole = $_SESSION['role'] ?? '';
                                        $currentStatus = $application['status'];

                                        // All available statuses
                                        $allStatuses = [
                                            'submitted'    => $_t('status_submitted'),
                                            'under_review' => $_t('status_review'),
                                            'for_revision' => $_t('status_revision'),
                                            'approved'     => $_t('status_approved'),
                                            'rejected'     => $_t('status_rejected'),
                                        ];

                                        // Role-based allowed statuses
                                        if (in_array($currentRole, ['admin', 'super_admin'])) {
                                            $allowedStatuses = array_keys($allStatuses);
                                        } elseif ($currentRole === 'zoning_officer') {
                                            $allowedStatuses = ['submitted', 'under_review', 'for_revision', 'rejected'];
                                        } elseif ($currentRole === 'building_official') {
                                            $allowedStatuses = ['submitted', 'under_review', 'for_revision', 'approved', 'rejected'];
                                        } else {
                                            $allowedStatuses = [$currentStatus]; // fallback
                                        }

                                        foreach ($allStatuses as $value => $label):
                                            $isSelected  = $currentStatus === $value ? 'selected' : '';
                                            $isDisabled  = !in_array($value, $allowedStatuses) ? 'disabled' : '';
                                        ?>
                                            <option value="<?php echo $value; ?>" <?php echo $isSelected; ?> <?php echo $isDisabled; ?>>
                                                <?php echo htmlspecialchars($label); ?><?php echo $isDisabled ? ' —' : ''; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text x-small text-danger"><i class="bi bi-info-circle me-1"></i> <?php echo htmlspecialchars($_t('status_hint')); ?></div>
                                </div>
                                
                                <div class="mb-3" id="assignOfficerField" style="<?php echo $application['status'] === 'under_review' ? '' : 'display:none;'; ?>">
                                    <label class="small fw-bold mb-1"><?php echo htmlspecialchars($_t('lbl_assign_officer')); ?> <span class="text-danger">*</span></label>
                                    <select class="form-select" name="assign_officer_id" id="assignOfficerSelect">
                                        <option value=""><?php echo htmlspecialchars($_t('no_assignment')); ?></option>
                                        <?php foreach ($officers as $officer): 
                                            $roleDisplay = ucwords(str_replace('_', ' ', $officer['role'])); ?>
                                            <option value="<?php echo $officer['id']; ?>" <?php echo ($application['assigned_officer_id'] == $officer['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($officer['first_name'] . ' ' . $officer['last_name'] . ' (' . $roleDisplay . ')'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text x-small text-muted"><i class="bi bi-info-circle me-1"></i> Required when setting status to "Under Review".</div>
                                </div>

                                <div class="mb-3">
                                    <label class="small fw-bold mb-1"><?php echo htmlspecialchars($_t('lbl_remarks')); ?></label>
                                    <textarea class="form-control" name="remarks" placeholder="<?php echo htmlspecialchars($_t('remarks_ph')); ?>" rows="4" required></textarea>
                                </div>

                                <?php
                                    // Pass prerequisite flags to JS
                                    $hasTechnicalAssessment = !empty($impactAssessment);
                                    $hasZoningVerification  = !empty($zoningCheck);
                                ?>
                                <!-- ── PREREQUISITE CHECKLIST ── -->
                                <div class="mb-3 p-2 rounded border bg-white" id="prereqChecklist">
                                    <p class="small fw-bold text-muted mb-2 text-uppercase" style="font-size:0.7rem; letter-spacing:.04em;">
                                        <i class="bi bi-shield-check me-1"></i> <?php echo htmlspecialchars($_t('prereq_title')); ?>
                                    </p>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <?php if ($hasTechnicalAssessment): ?>
                                            <i class="bi bi-check-circle-fill text-success"></i>
                                            <span class="small text-success fw-semibold"><?php echo htmlspecialchars($_t('prereq_tech_done')); ?></span>
                                        <?php else: ?>
                                            <i class="bi bi-x-circle-fill text-danger"></i>
                                            <span class="small text-danger fw-semibold"><?php echo htmlspecialchars($_t('prereq_tech_pending')); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if ($hasZoningVerification): ?>
                                            <i class="bi bi-check-circle-fill text-success"></i>
                                            <span class="small text-success fw-semibold"><?php echo htmlspecialchars($_t('prereq_zone_done')); ?></span>
                                        <?php else: ?>
                                            <i class="bi bi-x-circle-fill text-danger"></i>
                                            <span class="small text-danger fw-semibold"><?php echo htmlspecialchars($_t('prereq_zone_pending')); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <!-- ── END PREREQUISITE CHECKLIST ── -->

                                <div class="d-grid">
                                    <button type="submit" id="confirmWorkflowBtn" class="btn btn-primary shadow">
                                        <i class="bi bi-save me-2"></i> <?php echo htmlspecialchars($_t('btn_confirm_workflow')); ?>
                                    </button>
                                </div>
                            </form>

                            <!-- ── PREREQUISITE MODAL ── -->
                            <div class="modal fade" id="prereqBlockModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg">
                                        <div class="modal-header bg-danger text-white">
                                            <h6 class="modal-title fw-bold mb-0">
                                                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($_t('prereq_modal_title')); ?>
                                            </h6>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="small mb-3">
                                                <?php echo htmlspecialchars($_t('prereq_modal_body')); ?>
                                            </p>
                                            <ul class="list-unstyled mb-0" id="prereqModalList"></ul>
                                        </div>
                                        <div class="modal-footer border-0 pt-0">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?php echo htmlspecialchars($_t('btn_close')); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- ── END PREREQUISITE MODAL ── -->

                            <script>
                            (function () {
                                var hasTechnical = <?php echo $hasTechnicalAssessment ? 'true' : 'false'; ?>;
                                var hasZoning    = <?php echo $hasZoningVerification  ? 'true' : 'false'; ?>;
                                var JS_PREREQ_TECH  = <?php echo json_encode($_t('js_prereq_tech')); ?>;
                                var JS_PREREQ_ZONE  = <?php echo json_encode($_t('js_prereq_zone')); ?>;

                                // Statuses that require both checks
                                var restrictedStatuses = ['approved', 'rejected'];

                                var btn = document.getElementById('confirmWorkflowBtn');
                                if (!btn) return;
                                var form = btn.closest('form');
                                if (!form) return;

                                form.addEventListener('submit', function (e) {
                                    var statusSelect = form.querySelector('select[name="status"]');
                                    if (!statusSelect) return;

                                    var chosen = statusSelect.value;
                                    if (restrictedStatuses.indexOf(chosen) === -1) return; // 'submitted' always OK

                                    var missing = [];
                                    if (!hasTechnical) {
                                        missing.push({
                                            icon: 'bi-clipboard2-pulse-fill',
                                            text: JS_PREREQ_TECH
                                        });
                                    }
                                    if (!hasZoning) {
                                        missing.push({
                                            icon: 'bi-geo-alt-fill',
                                            text: JS_PREREQ_ZONE
                                        });
                                    }

                                    if (missing.length === 0) return; // all good, let the form submit

                                    e.preventDefault();

                                    var list = document.getElementById('prereqModalList');
                                    list.innerHTML = missing.map(function (m) {
                                        return '<li class="d-flex align-items-start gap-2 mb-2">'
                                             + '<i class="bi ' + m.icon + ' text-danger mt-1 flex-shrink-0"></i>'
                                             + '<span class="small">' + m.text + '</span>'
                                             + '</li>';
                                    }).join('');

                                    new bootstrap.Modal(document.getElementById('prereqBlockModal')).show();
                                });
                            })();
                            </script>

                            <?php if ($application['status'] === 'approved' && !in_array($_SESSION['role'], ['zoning_officer', 'assessor', 'inspector'])): ?>
                            <!-- ── PERMIT PDF DOWNLOAD PANEL ─────────────────────────── -->
                            <div class="p-3 bg-success-subtle border border-success rounded shadow-sm">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-file-earmark-check-fill text-success fs-4 me-2"></i>
                                    <div>
                                        <h6 class="fw-bold mb-0 text-success"><?php echo htmlspecialchars($_t('permit_ready')); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($_t('permit_generated')); ?></small>
                                    </div>
                                </div>
                                <a href="/lgu-urban-planning/modules/PermitProcessing/generate_permit_pdf.php?id=<?php echo $applicationId; ?>"
                                   target="_blank"
                                   class="btn btn-success w-100 shadow-sm">
                                    <i class="bi bi-download me-2"></i> <?php echo htmlspecialchars($_t('btn_download_permit')); ?>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3 text-uppercase small text-muted tracking-wider">
                                <i class="bi bi-geo-fill me-1 text-primary"></i> <?php echo htmlspecialchars($_t('zoning_heading')); ?>
                            </h6>
                            
                            <?php 
                                $containerClass = 'bg-white border-light'; 
                                if ($zoningCheck) {
                                    $status = strtolower($zoningCheck['compliance_status']);
                                    if ($status === 'compliant') {
                                        $containerClass = 'bg-success-subtle border-success';
                                    } elseif ($status === 'non-compliant') {
                                        $containerClass = 'bg-danger-subtle border-danger';
                                    }
                                }
                            ?>
                            
                            <div class="p-3 border rounded-4 shadow-sm mb-1 <?php echo $containerClass; ?>">                                
                                <div class="text-center mb-3">
                                    <a href="/lgu-urban-planning/gis/map.php?app_id=<?php echo $applicationId; ?>&lat=<?php echo $application['latitude']; ?>&lng=<?php echo $application['longitude']; ?>&brgy=<?php echo urlencode($application['barangay']); ?>&street=<?php echo urlencode($application['street']); ?>&block=<?php echo urlencode($application['block']); ?>&lot=<?php echo urlencode($application['lot_number']); ?>" 
                                    class="btn btn-primary shadow-sm w-100 py-2">
                                        <i class="bi bi-map-fill me-2"></i> 
                                        <?php echo ($zoningCheck) ? htmlspecialchars($_t('btn_reverify')) : htmlspecialchars($_t('btn_verify')); ?>
                                    </a>
                                    <small class="text-muted mt-2 d-block" style="font-size: 0.75rem;">
                                        <i class="bi bi-info-circle me-1"></i> <?php echo htmlspecialchars($_t('gis_cross_ref')); ?>
                                    </small>
                                </div>

                                <?php if ($zoningCheck): ?>
                                    <div class="bg-white p-3 rounded-3 border shadow-sm">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge rounded-pill <?php echo (strtolower($zoningCheck['compliance_status']) === 'compliant') ? 'bg-success' : 'bg-danger'; ?> px-3 py-2">
                                                <i class="bi <?php echo (strtolower($zoningCheck['compliance_status']) === 'compliant') ? 'bi-check-circle-fill' : 'bi-x-circle-fill'; ?> me-1"></i> 
                                                <?php echo strtoupper(str_replace('_', ' ', $zoningCheck['compliance_status'])); ?>
                                            </span>
                                            
                                            <span class="badge bg-light text-dark border px-2 py-1">
                                                <span class="fw-normal text-muted" style="font-size: 0.7rem;">Zone:</span> 
                                                <span class="fw-bold ms-1" style="font-size: 0.8rem;"><?php echo htmlspecialchars($zoningCheck['zoning_type'] ?? 'Unknown'); ?></span>
                                            </span>
                                        </div>
                                        
                                        <div class="bg-light p-2 rounded-2 border-start border-4 <?php echo (strtolower($zoningCheck['compliance_status']) === 'compliant') ? 'border-success' : 'border-danger'; ?>">
                                            <label class="fw-bold text-uppercase text-muted d-block mb-1" style="font-size: 0.65rem;"><?php echo htmlspecialchars($_t('gis_analysis_label')); ?></label>
                                            <p class="mb-0 text-dark lh-sm" style="font-size: 0.85rem;">
                                                <?php echo nl2br(htmlspecialchars($zoningCheck['technical_analysis'] ?? $_t('no_analysis'))); ?>
                                            </p>
                                        </div>

                                        <div class="mt-2 text-end">
                                            <small class="text-muted" style="font-size: 0.65rem;">Verified: <?php echo date('M d, Y', strtotime($zoningCheck['checked_at'])); ?></small>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4 border border-dashed rounded-4 bg-white shadow-sm" style="border-width: 2px !important;">
                                        <div class="mb-2">
                                            <i class="bi bi-geo-alt-fill text-secondary opacity-50" style="font-size: 2.5rem;"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($_t('awaiting_spatial')); ?></h6>
                                        <p class="text-muted mb-0 px-4 small lh-sm">
                                            <?php echo htmlspecialchars($_t('awaiting_spatial_desc')); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="row">
                        <div class="col-md-6 border-end">
                            <h6 class="fw-bold mb-3"><?php echo htmlspecialchars($_t('actions_heading')); ?></h6>
                            <div class="mb-3 p-3 bg-light rounded border shadow-sm position-relative" style="pointer-events:none; opacity:0.65;">
                                <div class="mb-3">
                                    <label class="small fw-bold mb-1"><?php echo htmlspecialchars($_t('lbl_set_status')); ?></label>
                                    <select class="form-select border-primary" disabled>
                                        <?php foreach (['submitted' => $_t('status_submitted'), 'under_review' => $_t('status_review'), 'for_revision' => $_t('status_revision'), 'approved' => $_t('status_approved'), 'rejected' => $_t('status_rejected')] as $value => $label): ?>
                                            <option value="<?php echo $value; ?>" <?php echo ($application['status'] === $value) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="small fw-bold mb-1"><?php echo htmlspecialchars($_t('lbl_remarks')); ?></label>
                                    <textarea class="form-control" rows="4" disabled placeholder="<?php echo htmlspecialchars($_t('remarks_ph')); ?>"></textarea>
                                </div>
                                <?php
                                    $hasTechnicalAssessment = !empty($impactAssessment);
                                    $hasZoningVerification  = !empty($zoningCheck);
                                ?>
                                <div class="mb-3 p-2 rounded border bg-white">
                                    <p class="small fw-bold text-muted mb-2 text-uppercase" style="font-size:0.7rem; letter-spacing:.04em;">
                                        <i class="bi bi-shield-check me-1"></i> <?php echo htmlspecialchars($_t('prereq_title')); ?>
                                    </p>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <?php if ($hasTechnicalAssessment): ?>
                                            <i class="bi bi-check-circle-fill text-success"></i>
                                            <span class="small text-success fw-semibold"><?php echo htmlspecialchars($_t('prereq_tech_done')); ?></span>
                                        <?php else: ?>
                                            <i class="bi bi-x-circle-fill text-danger"></i>
                                            <span class="small text-danger fw-semibold"><?php echo htmlspecialchars($_t('prereq_tech_pending')); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if ($hasZoningVerification): ?>
                                            <i class="bi bi-check-circle-fill text-success"></i>
                                            <span class="small text-success fw-semibold"><?php echo htmlspecialchars($_t('prereq_zone_done')); ?></span>
                                        <?php else: ?>
                                            <i class="bi bi-x-circle-fill text-danger"></i>
                                            <span class="small text-danger fw-semibold"><?php echo htmlspecialchars($_t('prereq_zone_pending')); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button type="button" class="btn btn-primary shadow" disabled>
                                        <i class="bi bi-save me-2"></i> <?php echo htmlspecialchars($_t('btn_confirm_workflow')); ?>
                                    </button>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 px-3 py-2 rounded border border-warning-subtle bg-warning-subtle small mt-2">
                                <i class="bi bi-lock-fill text-warning flex-shrink-0"></i>
                                <span class="text-warning-emphasis">You don't have permission to change the application status. Contact an Admin, Zoning Officer, or Building Official.</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3 text-uppercase small text-muted tracking-wider">
                                <i class="bi bi-geo-fill me-1 text-primary"></i> <?php echo htmlspecialchars($_t('zoning_heading')); ?>
                            </h6>
                            <?php 
                                $containerClass = 'bg-white border-light'; 
                                if ($zoningCheck) {
                                    $status = strtolower($zoningCheck['compliance_status']);
                                    if ($status === 'compliant') {
                                        $containerClass = 'bg-success-subtle border-success';
                                    } elseif ($status === 'non-compliant') {
                                        $containerClass = 'bg-danger-subtle border-danger';
                                    }
                                }
                            ?>
                            <div class="p-3 border rounded-4 shadow-sm mb-1 <?php echo $containerClass; ?>">
                                <div class="text-center mb-3" style="pointer-events:none; opacity:0.65;">
                                    <button type="button" class="btn btn-primary shadow-sm w-100 py-2" disabled>
                                        <i class="bi bi-map-fill me-2"></i>
                                        <?php echo ($zoningCheck) ? htmlspecialchars($_t('btn_reverify')) : htmlspecialchars($_t('btn_verify')); ?>
                                    </button>
                                    <small class="text-muted mt-2 d-block" style="font-size: 0.75rem;">
                                        <i class="bi bi-info-circle me-1"></i> <?php echo htmlspecialchars($_t('gis_cross_ref')); ?>
                                    </small>
                                </div>
                                <?php if ($zoningCheck): ?>
                                    <div class="bg-white p-3 rounded-3 border shadow-sm">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge rounded-pill <?php echo (strtolower($zoningCheck['compliance_status']) === 'compliant') ? 'bg-success' : 'bg-danger'; ?> px-3 py-2">
                                                <i class="bi <?php echo (strtolower($zoningCheck['compliance_status']) === 'compliant') ? 'bi-check-circle-fill' : 'bi-x-circle-fill'; ?> me-1"></i>
                                                <?php echo strtoupper(str_replace('_', ' ', $zoningCheck['compliance_status'])); ?>
                                            </span>
                                            <span class="badge bg-light text-dark border px-2 py-1">
                                                <span class="fw-normal text-muted" style="font-size: 0.7rem;">Zone:</span>
                                                <span class="fw-bold ms-1" style="font-size: 0.8rem;"><?php echo htmlspecialchars($zoningCheck['zoning_type'] ?? 'Unknown'); ?></span>
                                            </span>
                                        </div>
                                        <div class="bg-light p-2 rounded-2 border-start border-4 <?php echo (strtolower($zoningCheck['compliance_status']) === 'compliant') ? 'border-success' : 'border-danger'; ?>">
                                            <label class="fw-bold text-uppercase text-muted d-block mb-1" style="font-size: 0.65rem;"><?php echo htmlspecialchars($_t('gis_analysis_label')); ?></label>
                                            <p class="mb-0 text-dark lh-sm" style="font-size: 0.85rem;">
                                                <?php echo nl2br(htmlspecialchars($zoningCheck['technical_analysis'] ?? $_t('no_analysis'))); ?>
                                            </p>
                                        </div>
                                        <div class="mt-2 text-end">
                                            <small class="text-muted" style="font-size: 0.65rem;">Verified: <?php echo date('M d, Y', strtotime($zoningCheck['checked_at'])); ?></small>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4 border border-dashed rounded-4 bg-white shadow-sm" style="border-width: 2px !important;">
                                        <div class="mb-2">
                                            <i class="bi bi-geo-alt-fill text-secondary opacity-50" style="font-size: 2.5rem;"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($_t('awaiting_spatial')); ?></h6>
                                        <p class="text-muted mb-0 px-4 small lh-sm">
                                            <?php echo htmlspecialchars($_t('awaiting_spatial_desc')); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="history" role="tabpanel">
                    <h6 class="fw-bold mb-3"><?php echo htmlspecialchars($_t('history_heading')); ?></h6>
                    <div class="ms-3">
                        <?php if (empty($historyRecords) && $currentPage == 1): ?>
                            <div class="border-start border-primary border-3 ps-3 mb-4 position-relative">
                                <i class="bi bi-circle-fill position-absolute text-primary" style="left: -10px; top: 0; font-size: 12px;"></i>
                                <div class="d-flex justify-content-between">
                                    <strong class="text-primary small"><?php echo htmlspecialchars($_t('history_submitted_manual')); ?></strong>
                                    <span class="text-muted italic" style="font-size: 11px;"><?php echo Helper::formatDateTime($application['created_at']); ?></span>
                                </div>
                                <p class="mb-1 small">
                                    <?php echo htmlspecialchars($_t('history_manual_desc')); ?> <strong><?php echo htmlspecialchars($_t('history_manual_entry')); ?></strong> <?php echo htmlspecialchars($_t('history_for')); ?>
                                    <span class="text-primary fw-bold"><?php echo htmlspecialchars($application['applicant_first_name'] . ' ' . $application['applicant_last_name']); ?></span>
                                </p>
                                <small class="text-muted x-small"><?php echo htmlspecialchars($_t('history_by')); ?> <strong><?php echo htmlspecialchars($_t('history_admin')); ?></strong></small>
                            </div>
                        <?php else: ?>
                            <?php foreach ($historyRecords as $history): ?>
                                <div class="border-start border-primary border-3 ps-3 mb-4 position-relative">
                                    <i class="bi bi-circle-fill position-absolute text-primary" style="left: -10px; top: 0; font-size: 12px;"></i>
                                    <div class="d-flex justify-content-between">
                                        <strong class="text-primary small">
                                            <?php 
                                                if ($history['status'] === 'submitted') {
                                                    echo htmlspecialchars($_t('history_submitted_online'));
                                                } else {
                                                    echo strtoupper(str_replace('_', ' ', $history['status']));
                                                }
                                            ?>
                                        </strong>
                                        <span class="text-muted italic" style="font-size: 11px;"><?php echo Helper::formatDateTime($history['created_at']); ?></span>
                                    </div>
                                    <p class="mb-1 small">
                                        <?php echo ($history['status'] === 'submitted') ? htmlspecialchars($_t('history_online_desc')) . ' <strong>' . htmlspecialchars($_t('history_online_portal')) . '</strong>' : htmlspecialchars($history['remarks'] ?? $_t('history_no_notes')); ?>
                                    </p>
                                    <small class="text-muted x-small">
                                        <?php echo htmlspecialchars($_t('history_by')); ?> <strong>
                                            <?php 
                                            if (!empty($history['first_name'])) {
                                                echo htmlspecialchars($history['first_name'] . ' ' . $history['last_name']); 
                                            } else {
                                                echo htmlspecialchars($_t('history_sys_admin')); 
                                            }
                                            ?>
                                        </strong>
                                    </small>
                                </div>
                            <?php endforeach; ?>

                            <?php if ($totalPages > 1): ?>
                                <nav class="mt-4">
                                    <ul class="pagination pagination-sm">
                                        <li class="page-item <?php echo ($currentPage <= 1) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?id=<?php echo $applicationId; ?>&page=<?php echo $currentPage - 1; ?>#history">
                                                <i class="bi bi-chevron-left"></i> <?php echo htmlspecialchars($_t('pagination_prev')); ?>
                                            </a>
                                        </li>

                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php echo ($currentPage == $i) ? 'active' : ''; ?>">
                                                <a class="page-link" href="?id=<?php echo $applicationId; ?>&page=<?php echo $i; ?>#history"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <li class="page-item <?php echo ($currentPage >= $totalPages) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?id=<?php echo $applicationId; ?>&page=<?php echo $currentPage + 1; ?>#history">
                                                <?php echo htmlspecialchars($_t('pagination_next')); ?> <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 mb-4">
        <a href="/lgu-urban-planning/permit/applications.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> <?php echo htmlspecialchars($_t('btn_back')); ?>
        </a>
    </div>
</div>

<div class="modal fade" id="docViewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="docTitle"><?php echo htmlspecialchars($_t('doc_viewer_title')); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 d-flex align-items-center justify-content-center bg-secondary-subtle" style="height: 80vh; overflow: hidden;">
                <img id="docImage" src="" alt="Document" style="display:none; max-width:100%; max-height:100%; object-fit:contain;">
                <iframe id="docFrame" src="" width="100%" height="100%" frameborder="0" style="display:none;"></iframe>
            </div>
        </div> 
    </div>
</div>

<script>
function viewDocument(id, title, fileUrl) {
    document.getElementById('docTitle').innerText = title;
    const img   = document.getElementById('docImage');
    const frame = document.getElementById('docFrame');
    img.style.display   = 'none';
    frame.style.display = 'none';

    // Use the direct URL when provided (bypasses documents/view.php
    // which can fail on Windows-style file_path values stored in the DB).
    // Fall back to the PHP script only if no direct URL was supplied.
    const url = (fileUrl && fileUrl.trim() !== '')
        ? fileUrl
        : '/lgu-urban-planning/documents/view.php?id=' + id;

    if (title.toLowerCase().endsWith('.pdf')) {
        frame.src = url;
        frame.style.display = 'block';
    } else {
        img.src = url;
        img.style.display = 'block';
    }
    new bootstrap.Modal(document.getElementById('docViewerModal')).show();
}

document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (window.location.hash === '#history' || urlParams.has('page')) {
        const historyTab = document.querySelector('#history-tab');
        if (historyTab) {
            const tab = new bootstrap.Tab(historyTab);
            tab.show();
            document.getElementById('history').scrollIntoView({ behavior: 'smooth' });
        }
    }

    // Show/hide "Assign to Officer" field based on status selection
    const statusSelect = document.querySelector('select[name="status"]');
    const assignField  = document.getElementById('assignOfficerField');
    const assignSelect = document.getElementById('assignOfficerSelect');

    if (statusSelect && assignField) {
        statusSelect.addEventListener('change', function () {
            const isUnderReview = this.value === 'under_review';
            assignField.style.display = isUnderReview ? '' : 'none';
            if (assignSelect) assignSelect.required = isUnderReview;
        });
        // Set required on page load if already under_review
        if (statusSelect.value === 'under_review' && assignSelect) {
            assignSelect.required = true;
        }
    }
});
</script>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<?php if (in_array($_SESSION['role'], ['admin', 'super_admin', 'assessor'])): ?>
<!-- ── EDIT PARCEL INFO MODAL ─────────────────────────────────────────── -->
<div class="modal fade" id="editParcelModal" tabindex="-1" aria-labelledby="editParcelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                    <i class="bi bi-pencil-square me-2"></i> Edit Parcel Information
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_parcel_info">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Lot Number</label>
                            <input type="text" class="form-control" name="lot_number"
                                value="<?php echo htmlspecialchars($application['lot_number'] ?? ''); ?>"
                                placeholder="e.g. 1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Block</label>
                            <input type="text" class="form-control" name="block"
                                value="<?php echo htmlspecialchars($application['block'] ?? ''); ?>"
                                placeholder="e.g. 5">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Street</label>
                            <input type="text" class="form-control" name="street"
                                value="<?php echo htmlspecialchars($application['street'] ?? ''); ?>"
                                placeholder="e.g. Aurora Boulevard">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Barangay</label>
                            <input type="text" class="form-control" name="barangay"
                                value="<?php echo htmlspecialchars($application['barangay'] ?? ''); ?>"
                                placeholder="e.g. Socorro">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">GIS Parcel ID</label>
                            <input type="text" class="form-control" name="parcel_id"
                                value="<?php echo htmlspecialchars($application['parcel_id'] ?? ''); ?>"
                                placeholder="e.g. 114-13-002-01-001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Latitude</label>
                            <input type="number" step="any" class="form-control" name="latitude" id="parcel-lat"
                                value="<?php echo htmlspecialchars($application['latitude'] ?? ''); ?>"
                                placeholder="e.g. 14.62250000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Longitude</label>
                            <input type="number" step="any" class="form-control" name="longitude" id="parcel-lng"
                                value="<?php echo htmlspecialchars($application['longitude'] ?? ''); ?>"
                                placeholder="e.g. 121.05330000">
                        </div>
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold mb-0">LOCATOR MAP</label>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-parcel-map">
                                    <i class="bi bi-geo-alt me-1"></i> Pick on Map
                                </button>
                            </div>
                            <div id="parcel-map-container" style="display:none; height:300px; width:100%; border-radius:8px; border:1px solid #ddd;"></div>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-2 mt-3 mb-0 px-3 py-2 rounded border border-warning-subtle bg-warning-subtle small">
                        <i class="bi bi-exclamation-triangle-fill text-warning mt-1 flex-shrink-0"></i>
                        <span class="text-warning-emphasis">
                            Changes to parcel information will be logged in the application timeline.
                        </span>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm">
                        <i class="bi bi-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    const defaultLat = 14.7566;
    const defaultLng = 121.0450;
    let parcelMap = null;
    let parcelMarker = null;

    function updateParcelMarker(lat, lng, moveMap) {
        if (!lat || !lng || isNaN(lat) || isNaN(lng)) return;
        const pos = [parseFloat(lat), parseFloat(lng)];
        if (parcelMarker) {
            parcelMarker.setLatLng(pos);
        } else if (parcelMap) {
            parcelMarker = L.marker(pos, { draggable: true }).addTo(parcelMap);
            parcelMarker.on('dragend', function () {
                const p = parcelMarker.getLatLng();
                document.getElementById('parcel-lat').value = p.lat.toFixed(6);
                document.getElementById('parcel-lng').value = p.lng.toFixed(6);
            });
        }
        if (moveMap && parcelMap) parcelMap.setView(pos, 16);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const latInput = document.getElementById('parcel-lat');
        const lngInput = document.getElementById('parcel-lng');
        const mapBtn   = document.getElementById('btn-parcel-map');
        const mapDiv   = document.getElementById('parcel-map-container');
        if (!mapBtn || !mapDiv) return;

        // Sync typed coordinates to marker
        [latInput, lngInput].forEach(function (el) {
            if (el) el.addEventListener('input', function () {
                const lat = latInput.value, lng = lngInput.value;
                if (lat && lng) updateParcelMarker(lat, lng, true);
            });
        });

        mapBtn.addEventListener('click', function () {
            if (mapDiv.style.display === 'none' || mapDiv.style.display === '') {
                mapDiv.style.display = 'block';
                mapBtn.innerHTML = '<i class="bi bi-map-fill"></i> Hide Map';

                if (!parcelMap) {
                    const initLat = parseFloat(latInput.value) || defaultLat;
                    const initLng = parseFloat(lngInput.value) || defaultLng;
                    parcelMap = L.map('parcel-map-container').setView([initLat, initLng], 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(parcelMap);

                    parcelMap.on('click', function (e) {
                        const lat = e.latlng.lat.toFixed(6);
                        const lng = e.latlng.lng.toFixed(6);
                        latInput.value = lat;
                        lngInput.value = lng;
                        updateParcelMarker(lat, lng);
                    });

                    // Show existing pin if coords already set
                    if (latInput.value && lngInput.value) {
                        updateParcelMarker(latInput.value, lngInput.value, true);
                    }
                }

                setTimeout(function () { parcelMap.invalidateSize(); }, 200);
            } else {
                mapDiv.style.display = 'none';
                mapBtn.innerHTML = '<i class="bi bi-geo-alt me-1"></i> Pick on Map';
            }
        });
    });
})();
</script>

<?php include __DIR__ . '/../admin/footer.php'; ?>
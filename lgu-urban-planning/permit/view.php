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
$auth->requireRole(['admin', 'zoning_officer', 'building_official', 'assessor', 'inspector']);

$permitController = new PermitController();
$gisController = new GISController();
$applicationId = $_GET['id'] ?? 0;

$db = Database::getInstance();
$dbConn = $db->getConnection();

$error = '';
$success = '';

// --- STEP 1: FETCH FRESH DATA --- 
$zoningCheck = $db->fetchOne("SELECT * FROM zoning_compliance_checks WHERE application_id = ?", [$applicationId]);
$impactAssessment = $db->fetchOne("SELECT * FROM impact_assessments WHERE application_id = ?", [$applicationId]);
$application = $permitController->getApplicationDetails($applicationId);

if (!$application) {
    header('Location: /lgu-urban-planning/permit/applications.php');
    exit;
}

// --- STEP 2: HANDLE POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

//  ADMIN ACTION: Assign Inspector
if ($_POST['action'] === 'assign_inspection' && $auth->hasRole(['admin', 'zoning_officer'])) {
    $ins_id = $_POST['inspector_id'];
    $sched = $_POST['scheduled_at'];
    
    $stmt = $dbConn->prepare("INSERT INTO inspections (application_id, inspector_id, scheduled_at, status) VALUES (?, ?, ?, 'scheduled')");
    if ($stmt->execute([$applicationId, $ins_id, $sched])) {
        $success = "Inspector assigned successfully.";
    }
}

//  INSPECTOR ACTION: Submit Field Report
if ($_POST['action'] === 'submit_inspection' && $auth->hasRole('inspector')) {
    $notes = $_POST['notes'];
    $status = $_POST['status']; // e.g., 'completed' or 'violation_found'
    
    $stmt = $dbConn->prepare("UPDATE inspections SET notes = ?, status = ?, updated_at = NOW() WHERE application_id = ? AND inspector_id = ?");
    if ($stmt->execute([$notes, $status, $applicationId, $_SESSION['user_id']])) {
        $success = "Inspection report submitted successfully.";
    }
}
    
// ZONING COMPLIANCE UPDATE (From Map)
    if ($_POST['action'] === 'update_compliance') {
        $zoningType = $_POST['zoning_type'] ?? 'Unknown';
        $complianceResult = strtolower(trim($_POST['compliance_status'] ?? 'non_compliant'));
        $proposedProject = $application['project_type'] ?? ''; 
        $parcelIdFromMap = $_POST['parcel_id'] ?? null;
        $officerId = $_SESSION['user_id'] ?? 0;

        // Automation Check for the analysis text only
        $stmtCheck = $dbConn->prepare("SELECT * FROM permitted_uses WHERE ? LIKE CONCAT('%', zone_code, '%') AND project_type = ? LIMIT 1");
        $stmtCheck->execute([$zoningType, $proposedProject]);
        $isAllowed = $stmtCheck->fetch();

        $analysis = ($isAllowed) 
            ? "AUTOMATED VERIFICATION: Project type '$proposedProject' is permitted in zone $zoningType. " 
            : "AUTOMATED WARNING: Project type '$proposedProject' is NOT listed as a permitted use in $zoningType. ";
        $analysis .= ($_POST['technical_analysis'] ?? '');

        // UPSERT - Logic remains the same
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
                ':app_id' => $applicationId, ':parcel_id' => $parcelIdFromMap, ':zoning_type' => $zoningType, ':status' => $complianceResult, ':analysis' => $analysis, ':officer_id' => $officerId ]);

            $stmtLock = $dbConn->prepare("UPDATE zoning_compliance_checks 
                SET parcel_id = ? 
                WHERE application_id = ? 
                AND (parcel_id IS NULL OR parcel_id = '' OR parcel_id = '0')
            ");
            $stmtLock->execute([$parcelIdFromMap, $applicationId]);

        $historyRemarks = "GIS Verification: " . strtoupper($complianceResult) . " (Zone: $zoningType)";
        $dbConn->prepare("INSERT INTO application_status_history (application_id, status, remarks, changed_by) VALUES (?, 'zoning_verified', ?, ?)")
               ->execute([$applicationId, $historyRemarks, $officerId]);

        $success = 'Spatial verification updated successfully.';
    }

    // --- NEW: HANDLE STATUS UPDATES & AUTOMATED MESSAGE ---
    if ($_POST['action'] === 'update_status') {
        $newStatus = $_POST['status'];
        $remarks = $_POST['remarks'] ?? 'Your application is currently being processed.';
        $officerId = $_SESSION['user_id'];
        $applicantId = $application['applicant_id'];

        try {
            $dbConn->beginTransaction();

            // 1. Update the application status
            $stmt = $dbConn->prepare("UPDATE applications SET status = :status, updated_at = NOW() WHERE id = :id");
            $stmt->execute([':status' => $newStatus, ':id' => $applicationId]);

            // 2. Add to Status History
            $stmtHistory = $dbConn->prepare("INSERT INTO application_status_history (application_id, status, remarks, changed_by) VALUES (?, ?, ?, ?)");
            $stmtHistory->execute([$applicationId, $newStatus, $remarks, $officerId]);

            // 3. SEND THE PROFESSIONAL AUTOMATED MESSAGE
            $subject = "Official Update: Application #" . $application['application_number'];
            $statusLabel = strtoupper(str_replace('_', ' ', $newStatus));
            
            $messageBody = "Dear Applicant,\n\n";
            $messageBody .= "This is an official notification regarding your application: " . $application['project_name'] . ".\n\n";
            $messageBody .= "The status has been updated to: " . $statusLabel . ".\n";
            $messageBody .= "Location: Barangay " . $application['barangay'] . ", Block " . ($application['block'] ?? 'N/A') . ", Street " . ($application['street'] ?? 'N/A') . "\n\n";
            $messageBody .= "Remarks from Office:\n\"" . $remarks . "\"\n\n";
            $messageBody .= "You may monitor further progress through your portal.\n\n";
            $messageBody .= "Quezon City Urban Planning Department";

            $stmtMsg = $dbConn->prepare("INSERT INTO messages (application_id, sender_id, receiver_id, subject, message, message_type, created_at) VALUES (?, ?, ?, ?, ?, 'system_notification', NOW())");
            $stmtMsg->execute([$applicationId, $officerId, $applicantId, $subject, $messageBody]);

            $dbConn->commit();
            $success = 'Application status updated and notification sent.';
            
            // Sync the local variable so the badge updates on the page immediately
            $application['status'] = $newStatus; 

        } catch (Exception $e) {
            $dbConn->rollBack();
            $error = "Update failed: " . $e->getMessage();
        }
    }
}

// --- ADD THIS PAGINATION LOGIC HERE ---
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
     LIMIT $limit OFFSET $offset", 
    [$applicationId]
);

if (!$application) {
    header('Location: /lgu-urban-planning/permit/applications.php');
    exit;
}

// Pagination & Officers list (unchanged)
$officers = $db->fetchAll("SELECT id, first_name, last_name, role FROM users WHERE is_active = 1");
$historyRecords = $db->fetchAll("SELECT h.*, u.first_name, u.last_name FROM application_status_history h LEFT JOIN users u ON h.changed_by = u.id WHERE h.application_id = ? ORDER BY h.created_at DESC LIMIT 10", [$applicationId]);

$pageTitle = 'Application Details';
include __DIR__ . '/../admin/header.php';
?>

<style>
    /* Professional Dashed Placeholder */
    .border-dashed {
        border-style: dashed !important;
        border-width: 2px !important;
    }

    /* Pulse animation for the "Awaiting" status */
    .pulse-warning {
        width: 10px;
        height: 10px;
        background-color: #ffc107;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
        box-shadow: 0 0 0 rgba(255, 193, 7, 0.4);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
        100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
    }
</style>

<div class="p-4">
    <div class="mb-4">
        <h2>Application Details</h2>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger shadow-sm"><i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success shadow-sm"><i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
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
                <span class="me-3 text-muted small">Current Phase: <strong>Urban Development Review</strong></span>
                <span class="badge bg-<?php echo Helper::getStatusBadge($application['status']); ?> p-2 px-3">
                    <?php echo strtoupper(str_replace('_', ' ', $application['status'])); ?>
                </span>
            </div>
        </div>
        <div class="card-body">
            <ul class="nav nav-pills mb-4 border-bottom pb-3" id="appTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">Project Details</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="impact-tab" data-bs-toggle="tab" data-bs-target="#impact" type="button" role="tab">Technical Assessment</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="docs-tab" data-bs-toggle="tab" data-bs-target="#docs" type="button" role="tab">Documents</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="actions-tab" data-bs-toggle="tab" data-bs-target="#actions" type="button" role="tab">Zoning & Actions</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">Timeline</button>
                </li>
            </ul>
            
            <div class="tab-content pt-2">
                <div class="tab-pane fade show active" id="details" role="tabpanel">
                    <div class="row">
                        <div class="col-md-7 border-end">
                            <h6 class="fw-bold text-primary mb-3 text-uppercase small">
                                <i class="bi bi-info-square-fill me-2"></i>Project & Land Information
                            </h6>
                            <div class="row mb-4">
                                <div class="col-sm-6">
                                    <label class="text-muted d-block small">Project Name</label>
                                    <span class="fw-bold"><?php echo htmlspecialchars($application['project_name']); ?></span>
                                </div>
                                <div class="col-sm-6">
                                    <label class="text-muted d-block small">Project Type</label>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2">
                                        <?php echo htmlspecialchars($application['project_type'] ?? 'N/A'); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="text-muted d-block small">Project Description</label>
                                <p class="small text-dark bg-light p-3 rounded border shadow-sm">
                                    <?php echo nl2br(htmlspecialchars($application['project_description'] ?? 'No description provided.')); ?>
                                </p>
                            </div>
                            <div class="card border-0 shadow-sm bg-light mb-4">
                                <div class="card-body p-3">
                                    <h6 class="fw-bold x-small text-uppercase text-muted mb-3 border-bottom pb-2">Technical & Legal Identifiers</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <small class="text-muted d-block x-small">Lot & Block Number</small>
                                            <span class="fw-bold text-dark">Lot <?php echo htmlspecialchars($application['lot_number'] ?? '---'); ?>, Block <?php echo htmlspecialchars($application['block'] ?? '---'); ?></span>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted d-block x-small">Street</small>
                                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($application['street'] ?? '---'); ?></span>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted d-block x-small">Barangay</small>
                                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($application['barangay'] ?? '---'); ?></span>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted d-block small">GIS Parcel ID</label>
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
                                        <label class="d-block fw-bold text-primary x-small text-uppercase">Geospatial Coordinates</label>
                                        <code class="text-dark small">Latitude: <?php echo htmlspecialchars($application['latitude']); ?></code>
                                        <span class="mx-2 text-muted">|</span>
                                        <code class="text-dark small">Longitude: <?php echo htmlspecialchars($application['longitude']); ?></code>
                                    </div>
                                </div>
                            </div>
                        </div>
                                                <div class="col-md-5">
                            <h6 class="fw-bold text-primary mb-3 text-uppercase small"><i class="bi bi-person-badge-fill me-2"></i>Applicant Information</h6>
                            <div class="card shadow-sm mb-3">
                                <div class="card-body">
                                    <div class="mb-3 border-bottom pb-2">
                                        <label class="text-muted d-block x-small">Applicant ID</label>
                                        <span class="fw-bold">#<?php echo htmlspecialchars($application['id']); ?></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="text-muted d-block x-small">Full Name</label>
                                        <span class="fw-bold d-block"><?php echo htmlspecialchars($application['applicant_first_name'] . ' ' . $application['applicant_last_name']); ?></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="text-muted d-block x-small">Email Address</label>
                                        <a href="mailto:<?php echo $application['applicant_email']; ?>" class="text-decoration-none"><i class="bi bi-envelope-at me-1"></i><?php echo htmlspecialchars($application['applicant_email']); ?></a>
                                    </div>
                                    <div class="mb-3">
                                        <label class="text-muted d-block x-small">Phone/Contact</label>
                                        <span class="fw-bold"><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($application['applicant_phone'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div class="mt-3 p-2 rounded <?php echo $isWalkIn ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' : 'bg-info-subtle text-info-emphasis border border-info-subtle'; ?> small">
                                        <i class="bi <?php echo $isWalkIn ? 'bi-person-workspace' : 'bi-globe'; ?> me-1"></i>
                                        <strong>Record Type:</strong> 
                                        <span class="fw-bold">
                                            <?php echo $isWalkIn ? 'Manual / Walk-in Entry' : 'Online Portal Submission'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                    <div class="tab-pane fade" id="impact" role="tabpanel">
                        <?php if ($_SESSION['role'] === 'inspector'): ?>
                            <div class="alert alert-secondary py-2 small mb-3">
                                <i class="bi bi-info-circle me-2"></i> You are viewing departmental assessments. To submit your field report, please go to the <b>Zoning & Actions</b> tab.
                            </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="fw-bold mb-0">Departmental Inspection Results</h6>
                            <small class="text-muted">Assessment data provided by Roads and Energy departments.</small>
                        </div>
                        <?php if ($_SESSION['role'] !== 'inspector'): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="request_inspection">
                                <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm">
                                    <i class="bi bi-megaphone-fill me-1"></i> Request New Inspection
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
                                            <h6 class="text-primary fw-bold mb-0"><i class="bi bi-road-front-fill me-2"></i>Roads & Traffic</h6>
                                            <small class="text-muted">Infrastructure Impact</small>
                                        </div>
                                        <?php if ($impactAssessment && !empty($impactAssessment['traffic_flag'])): ?>
                                            <span class="badge bg-<?php echo ($impactAssessment['traffic_flag'] === 'ok' || $impactAssessment['traffic_flag'] === 'approved') ? 'success' : 'danger'; ?>"><?php echo strtoupper($impactAssessment['traffic_flag']); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">AWAITING INSPECTION</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="bg-white p-3 rounded border">
                                        <p class="mb-1 small fw-bold text-muted">Assessment Data:</p>
                                        <p class="small mb-0 text-dark italic"><?php echo htmlspecialchars($impactAssessment['traffic_notes'] ?? $impactAssessment['notes'] ?? 'No data submitted yet by the Roads department inspector.'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 border-0 bg-light shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="text-warning fw-bold mb-0"><i class="bi bi-lightning-charge-fill me-2"></i>Energy & Utilities</h6>
                                            <small class="text-muted">Grid Capacity Load</small>
                                        </div>
                                        <?php if ($impactAssessment && !empty($impactAssessment['energy_flag'])): ?>
                                            <span class="badge bg-<?php echo ($impactAssessment['energy_flag'] === 'ok' || $impactAssessment['energy_flag'] === 'approved') ? 'success' : 'danger'; ?>"><?php echo strtoupper($impactAssessment['energy_flag']); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">AWAITING INSPECTION</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="bg-white p-3 rounded border">
                                        <p class="mb-1 small fw-bold text-muted">Assessment Data:</p>
                                        <p class="small mb-0 text-dark italic"><?php echo htmlspecialchars($impactAssessment['energy_notes'] ?? $impactAssessment['notes'] ?? 'No data submitted yet by the Energy/Utilities department.'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="docs" role="tabpanel">
                    <h6 class="fw-bold mb-3">Submitted Requirements</h6>
                    <?php if (empty($application['documents'])): ?>
                        <div class="text-center p-5 border rounded bg-light">
                            <i class="bi bi-file-earmark-x fs-1 text-muted"></i>
                            <p class="text-muted mt-2">No documents uploaded yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Document Type</th>
                                        <th>File Name</th>
                                        <th>Uploaded By</th>
                                        <th>Date</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($application['documents'] as $doc): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary"><?php echo strtoupper(str_replace('_', ' ', $doc['document_type'])); ?></span></td>
                                        <td><?php echo htmlspecialchars($doc['file_name']); ?></td>
                                        <td><small><?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?></small></td>
                                        <td><small><?php echo Helper::formatDate($doc['created_at']); ?></small></td>
                                        <td class="text-end">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-info" onclick="viewDocument(<?php echo $doc['id']; ?>, '<?php echo htmlspecialchars($doc['file_name'], ENT_QUOTES); ?>')"><i class="bi bi-eye"></i></button>
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
                    <div class="row">
                        <div class="col-md-6 border-end">
                            <h6 class="fw-bold mb-3">Workflow Action</h6>
                            <form method="POST" class="mb-3 p-3 bg-light rounded border shadow-sm">
                                <input type="hidden" name="action" value="update_status">
                                <div class="mb-3">
                                    <label class="small fw-bold mb-1">Set Application Status</label>
                                    <select class="form-select border-primary" name="status" required>
                                        <option value="submitted" <?php echo $application['status'] === 'submitted' ? 'selected' : ''; ?>>Submitted (Initial)</option>
                                        <option value="under_review" <?php echo $application['status'] === 'under_review' ? 'selected' : ''; ?>>Under Review / Processing</option>
                                        <option value="for_revision" <?php echo $application['status'] === 'for_revision' ? 'selected' : ''; ?>>For Revision (Return to Applicant)</option>
                                        <option value="approved" <?php echo $application['status'] === 'approved' ? 'selected' : ''; ?>>Final Approval</option>
                                        <option value="rejected" <?php echo $application['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected / Denied</option>
                                    </select>
                                    <div class="form-text x-small text-danger"><i class="bi bi-info-circle me-1"></i> Final Approval requires all technical assessments to be 'OK'.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="small fw-bold mb-1">Assign to Officer</label>
                                    <select class="form-select" name="assign_officer_id">
                                        <option value="">-- No Assignment --</option>
                                        <?php foreach ($officers as $officer): 
                                            $roleDisplay = ucwords(str_replace('_', ' ', $officer['role'])); ?>
                                            <option value="<?php echo $officer['id']; ?>" <?php echo ($application['assigned_officer_id'] == $officer['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($officer['first_name'] . ' ' . $officer['last_name'] . ' (' . $roleDisplay . ')'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="small fw-bold mb-1">Official Remarks</label>
                                    <textarea class="form-control" name="remarks" placeholder="Provide reason for status update or instructions..." rows="4" required></textarea>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary shadow"><i class="bi bi-save me-2"></i> Confirm Workflow Update</button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3 text-uppercase small text-muted tracking-wider">
                                <i class="bi bi-geo-fill me-1 text-primary"></i> Zoning & Land Verification
                            </h6>
                            
                            <?php 
                                // Logic para sa dynamic colors
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
                                        <?php echo ($zoningCheck) ? 'RE-VERIFY ON GIS MAP' : 'LOCATE & VERIFY ON GIS MAP'; ?>
                                    </a>
                                    <small class="text-muted mt-2 d-block" style="font-size: 0.75rem;">
                                        <i class="bi bi-info-circle me-1"></i> Cross-reference with Cadastral & Zoning Map
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
                                            <label class="fw-bold text-uppercase text-muted d-block mb-1" style="font-size: 0.65rem;">GIS Technical Analysis:</label>
                                            <p class="mb-0 text-dark lh-sm" style="font-size: 0.85rem;">
                                                <?php echo nl2br(htmlspecialchars($zoningCheck['technical_analysis'] ?? 'No analysis provided.')); ?>
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
                                        <h6 class="fw-bold text-dark mb-2">Awaiting Spatial Review</h6>
                                        <p class="text-muted mb-0 px-4 small lh-sm">
                                            The zoning compliance and parcel boundaries have not been verified.
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="history" role="tabpanel">
                    <h6 class="fw-bold mb-3">Movement History</h6>
                    <div class="ms-3">
                        <?php if (empty($historyRecords) && $currentPage == 1): ?>
                            <div class="border-start border-primary border-3 ps-3 mb-4 position-relative">
                                <i class="bi bi-circle-fill position-absolute text-primary" style="left: -10px; top: 0; font-size: 12px;"></i>
                                <div class="d-flex justify-content-between">
                                    <strong class="text-primary small">SUBMITTED (MANUAL ENTRY)</strong>
                                    <span class="text-muted italic" style="font-size: 11px;"><?php echo Helper::formatDateTime($application['created_at']); ?></span>
                                </div>
                                <p class="mb-1 small">
                                    Application created via <strong>Manual Entry</strong> for 
                                    <span class="text-primary fw-bold"><?php echo htmlspecialchars($application['applicant_first_name'] . ' ' . $application['applicant_last_name']); ?></span>
                                </p>
                                <small class="text-muted x-small">By: <strong>ADMINISTRATOR</strong></small>
                            </div>
                        <?php else: ?>
                            <?php foreach ($historyRecords as $history): ?>
                                <div class="border-start border-primary border-3 ps-3 mb-4 position-relative">
                                    <i class="bi bi-circle-fill position-absolute text-primary" style="left: -10px; top: 0; font-size: 12px;"></i>
                                    <div class="d-flex justify-content-between">
                                        <strong class="text-primary small">
                                            <?php 
                                                if ($history['status'] === 'submitted') {
                                                    echo "SUBMITTED (ONLINE)";
                                                } else {
                                                    echo strtoupper(str_replace('_', ' ', $history['status']));
                                                }
                                            ?>
                                        </strong>
                                        <span class="text-muted italic" style="font-size: 11px;"><?php echo Helper::formatDateTime($history['created_at']); ?></span>
                                    </div>
                                    <p class="mb-1 small">
                                        <?php echo ($history['status'] === 'submitted') ? "Application submitted via <strong>Online Portal</strong>" : htmlspecialchars($history['remarks'] ?? 'No notes provided'); ?>
                                    </p>
                                    <small class="text-muted x-small">
                                        By: <strong>
                                            <?php 
                                            if (!empty($history['first_name'])) {
                                                echo htmlspecialchars($history['first_name'] . ' ' . $history['last_name']); 
                                            } else {
                                                echo "SYSTEM / ADMINISTRATOR"; 
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
                                                <i class="bi bi-chevron-left"></i> Previous
                                            </a>
                                        </li>

                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php echo ($currentPage == $i) ? 'active' : ''; ?>">
                                                <a class="page-link" href="?id=<?php echo $applicationId; ?>&page=<?php echo $i; ?>#history"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <li class="page-item <?php echo ($currentPage >= $totalPages) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?id=<?php echo $applicationId; ?>&page=<?php echo $currentPage + 1; ?>#history">
                                                Next <i class="bi bi-chevron-right"></i>
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
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<div class="modal fade" id="docViewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="docTitle">Document Viewer</h5>
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
function viewDocument(id, title) {
    document.getElementById('docTitle').innerText = title;
    const img = document.getElementById('docImage');
    const frame = document.getElementById('docFrame');
    img.style.display = 'none';
    frame.style.display = 'none';
    const url = '/lgu-urban-planning/documents/view.php?id=' + id;
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
});
</script>

<?php include __DIR__ . '/../admin/footer.php'; ?>
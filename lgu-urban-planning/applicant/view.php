<?php
/**
 * View Application Details (Applicant)
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helper.php';
require_once __DIR__ . '/../modules/ApplicantSelfService/ApplicantController.php';

$auth = new Auth();
$auth->requireRole('applicant');

$applicantController = new ApplicantController();
$applicationId = $_GET['id'] ?? 0;
$application = $applicantController->getApplicationDetails($applicationId);

if (!$application) {
    header('Location: /lgu-urban-planning/applicant/applications.php');
    exit;
}

$pageTitle = 'Application Details';
include __DIR__ . '/../user/header.php';
?>

<div class="p-4">
    <h2 class="mb-4">Application Details</h2>
    
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Application #<?php echo htmlspecialchars($application['application_number']); ?></h5>
            <span class="badge bg-<?php echo Helper::getStatusBadge($application['status']); ?>">
                <?php echo ucfirst(str_replace('_', ' ', $application['status'])); ?>
            </span>
        </div>
        <div class="card-body">
            <h6>Project Information</h6>
            <p><strong>Project Name:</strong> <?php echo htmlspecialchars($application['project_name']); ?></p>
            <p><strong>Project Type:</strong> <?php echo htmlspecialchars($application['project_type'] ?? 'N/A'); ?></p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($application['project_description'] ?? 'N/A'); ?></p>
            
            <hr>
            
            <h6>Location Information</h6>
            <p><strong>Lot Number:</strong> <?php echo htmlspecialchars($application['lot_number'] ?? 'N/A'); ?></p>
            <p><strong>Block Number:</strong> <?php echo htmlspecialchars($application['block'] ?? 'N/A'); ?></p>
            <p><strong>Street:</strong> <?php echo htmlspecialchars($application['street'] ?? 'N/A'); ?></p>
            <p><strong>Barangay:</strong> <?php echo htmlspecialchars($application['barangay'] ?? 'N/A'); ?></p>
            <p><strong>Parcel ID:</strong> <?php echo htmlspecialchars($application['parcel_id'] ?? 'N/A'); ?></p>
            <?php if (!empty($application['latitude']) && !empty($application['longitude'])): ?>
                <p><strong>Coordinates:</strong> <?php echo htmlspecialchars($application['latitude']); ?>, <?php echo htmlspecialchars($application['longitude']); ?></p>
            <?php endif; ?>
            
            <?php 
            // Fix: Added null coalescing to prevent "Undefined array key" warning
            $zoningStatus = $application['zoning_compliance_status'] ?? 'pending';
            if ($zoningStatus !== 'pending'): ?>
                <hr>
                <h6>Zoning Compliance</h6>
                <p><strong>Status:</strong> 
                    <span class="badge bg-<?php echo $zoningStatus === 'compliant' ? 'success' : 'danger'; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $zoningStatus)); ?>
                    </span>
                </p>
                <?php 
                // Fix: Added safety check for the report content
                $report = $application['zoning_compliance_report'] ?? null;
                if ($report): ?>
                    <pre class="bg-light p-3"><?php echo htmlspecialchars($report); ?></pre>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card mb-3">
        <div class="card-header">
            <h5>Documents</h5>
        </div>
        <div class="card-body">
            <?php if (empty($application['documents'])): ?>
                <p class="text-muted">No documents uploaded yet.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Document Type</th>
                            <th>File Name</th>
                            <th>Uploaded</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($application['documents'] as $doc): ?>
                        <tr>
                            <td><?php echo ucfirst(str_replace('_', ' ', $doc['document_type'])); ?></td>
                            <td><?php echo htmlspecialchars($doc['file_name']); ?></td>
                            <td><?php echo Helper::formatDateTime($doc['created_at']); ?></td>
                            <td>
                                <a href="/lgu-urban-planning/documents/download.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-primary">Download</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card mb-3">
        <div class="card-header">
            <h5>Status History</h5>
        </div>
        <div class="card-body">
            <div class="timeline">
                <?php foreach ($application['status_history'] as $history): ?>
                <div class="mb-3 border-start border-3 ps-3">
                    <strong><?php echo ucfirst(str_replace('_', ' ', $history['status'])); ?></strong>
                    <p class="mb-1"><?php echo htmlspecialchars($history['remarks'] ?? 'No remarks'); ?></p>
                    <small class="text-muted">
                        <?php echo Helper::formatDateTime($history['created_at']); ?>
                        <?php if (!empty($history['first_name'])): ?>
                            by <?php echo htmlspecialchars($history['first_name'] . ' ' . $history['last_name']); ?>
                        <?php endif; ?>
                    </small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <a href="/lgu-urban-planning/applicant/applications.php" class="btn btn-secondary">Back to Applications</a>
</div>

<?php include __DIR__ . '/../user/footer.php'; ?>
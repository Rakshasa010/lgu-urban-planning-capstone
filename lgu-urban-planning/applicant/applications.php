<?php
/**
 * My Applications List
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helper.php';
require_once __DIR__ . '/../modules/ApplicantSelfService/ApplicantController.php';

$auth = new Auth();
$auth->requireRole('applicant');

$applicantController = new ApplicantController();
$applications = $applicantController->getMyApplications();

$pageTitle = 'My Applications';
include __DIR__ . '/../user/header.php';
?>

<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Applications</h2>
        <a href="/lgu-urban-planning/applicant/apply.php" class="btn btn-primary">
            <i class="bi bi-plus"></i> Submit New Application
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Application #</th>
                        <th>Project Name</th>
                        <th>Status</th>
                        <th>Documents</th>
                        <th>Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($applications)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No applications yet. <a href="/lgu-urban-planning/applicant/apply.php">Submit your first application</a></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($app['application_number']); ?></td>
                            <td><?php echo htmlspecialchars($app['project_name']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo Helper::getStatusBadge($app['status']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo $app['document_count']; ?> document(s)</td>
                            <td><?php echo Helper::formatDate($app['submitted_at'] ?? $app['created_at']); ?></td>
                            <td>
                                <a href="/lgu-urban-planning/applicant/view.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-primary">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../user/footer.php'; ?>


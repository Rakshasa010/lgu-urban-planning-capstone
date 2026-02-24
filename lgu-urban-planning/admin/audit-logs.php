<?php

// Audit Logs

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helper.php';
require_once __DIR__ . '/../modules/UserAccessManagement/UserController.php';

$auth = new Auth();
$auth->requirePermission('view_audit_logs');
$auth->requireRole(['admin']);

$userController = new UserController();
$db = Database::getInstance();

// --- AUTO-PURGE LOGIC ---
$purgeMessage = "";
if (isset($_POST['purge_logs'])) {
    $years = (int)$_POST['purge_years'];
    if ($years >= 1) {
        $pdo = $db->getConnection(); 
        $stmt = $pdo->prepare("DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? YEAR)");
        
        if ($stmt->execute([$years])) {
            $deletedCount = $stmt->rowCount();
            $purgeMessage = "<div class='alert alert-success alert-dismissible fade show small mx-4 mt-3' role='alert'>
                                <i class='bi bi-check-circle-fill me-2'></i>Successfully purged <strong>$deletedCount</strong> logs older than $years year(s).
                                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                             </div>";
        }
    }
}

// Helper Function

function getSeverityTag($action) {
    $action = strtolower($action);
    
    // CRITICAL: Deletion or System Changes
    if (strpos($action, 'delete') !== false || strpos($action, 'remove') !== false || strpos($action, 'config') !== false || strpos($action, 'setting') !== false) {
        return '<span class="badge bg-danger text-white border-0 shadow-sm px-2 py-1"><i class="bi bi-exclamation-octagon me-1"></i>CRITICAL</span>';
    }
    
    // WARNING: Profile updates or Password changes
    if (strpos($action, 'update') !== false || strpos($action, 'edit') !== false || strpos($action, 'password') !== false || strpos($action, 'profile') !== false || strpos($action, 'change') !== false) {
        return '<span class="badge bg-warning text-dark border-0 shadow-sm px-2 py-1"><i class="bi bi-exclamation-triangle me-1"></i>WARNING</span>';
    }
    
    // INFO: Login, Logout, View (Default)
    return '<span class="badge bg-info text-white border-0 shadow-sm px-2 py-1"><i class="bi bi-info-circle me-1"></i>INFO</span>';
}

// --- FILTERS ---
$filters = [
    'action'    => $_GET['action'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to'   => $_GET['date_to'] ?? ''
];

// --- 1. EXPORT HANDLER (CSV/EXCEL) ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $allLogs = $userController->getAuditLogs($filters, 999999, 0);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Audit_Logs_Report_' . date('Y-m-d_His') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    fputcsv($output, ['Timestamp', 'Officer/User', 'Action', 'Entity Type', 'Entity ID', 'Details', 'IP Address', 'User Agent']);

    foreach ($allLogs as $log) {
        fputcsv($output, [
            $log['created_at'],
            $log['username'] ?? 'SYSTEM',
            $log['action'],
            $log['entity_type'] ?? 'N/A',
            $log['entity_id'] ?? 'N/A',
            $log['details'],
            $log['ip_address'],
            $log['user_agent'] ?? 'N/A'
        ]);
    }
    fclose($output);
    exit;
}

// --- 2. PAGINATION CONFIGURATION ---
$limit = 15; 
$page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// --- 3. DATA FETCHING ---
$totalLogs  = $userController->getTotalAuditLogsCount($filters);
$totalPages = ceil($totalLogs / $limit);
$logs       = $userController->getAuditLogs($filters, $limit, $offset);

$query_string = http_build_query(array_filter($filters));

$pageTitle = 'Audit Logs | LGU Urban Planning';
include __DIR__ . '/header.php';
?>

<style>
    .btn-export-lgu {
        background-color: #1a5c2b;
        color: #ffffff !important;
        font-weight: 600;
        font-size: 0.85rem;
        border: none;
        padding: 8px 20px;
        border-radius: 6px;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .btn-export-lgu:hover {
        background-color: #144621;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.15);
    }
    .btn-purge {
        background-color: #f8f9fa;
        color: #dc3545;
        border: 1px solid #dee2e6;
        font-weight: 600;
        font-size: 0.85rem;
    }
    .btn-purge:hover {
        background-color: #dc3545;
        color: white;
    }
    .pagination .page-link { color: #2c3e50; border: 1px solid #dee2e6; margin: 0 2px; border-radius: 4px; }
    .pagination .page-item.active .page-link { background-color: #1a5c2b; border-color: #1a5c2b; color: white; }
    .info-text { font-size: 0.875rem; color: #6c757d; }
    .table-lgu thead { background-color: #f8f9fa; border-top: 2px solid #1a5c2b; }
    .breadcrumb-item a { color: #1a5c2b; text-decoration: none; }
    
    .table-hover tbody tr { cursor: pointer; transition: background 0.2s; }
    .table-hover tbody tr:hover { background-color: rgba(26, 92, 43, 0.05) !important; }
    .text-device { font-size: 0.75rem; color: #95a5a6; }
    .badge { font-size: 0.65rem; letter-spacing: 0.5px; font-weight: 700; }
</style>

<div class="p-4 page-container">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active fw-bold text-dark" aria-current="page">System Audit Logs</li>
        </ol>
    </nav>

    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-shield-check text-success me-2"></i>Audit Records
            </h2>
            <p class="text-muted small mb-0">Official activity logs for transparency and administrative monitoring.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button type="button" class="btn btn-purge shadow-sm me-2" data-bs-toggle="modal" data-bs-target="#purgeModal">
                <i class="bi bi-trash3-fill me-1"></i> PURGE SETTINGS
            </button>
            <a href="?export=csv&<?= $query_string ?>" class="btn-export-lgu shadow-sm">
                <i class="bi bi-file-earmark-excel-fill"></i>
                <span>GENERATE EXCEL REPORT</span>
            </a>
        </div>
    </div>

    <?= $purgeMessage ?>

    <div class="card border-0 shadow-sm mb-4" style="border-left: 5px solid #1a5c2b !important;">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-uppercase text-muted" style="font-size: 0.7rem;">Action Type</label>
                    <input type="text" class="form-control form-control-sm" name="action" placeholder="Search action..." value="<?= htmlspecialchars($filters['action']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-uppercase text-muted" style="font-size: 0.7rem;">Date From</label>
                    <input type="date" class="form-control form-control-sm" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-uppercase text-muted" style="font-size: 0.7rem;">Date To</label>
                    <input type="date" class="form-control form-control-sm" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>">
                </div>
                <div class="col-md-5">
                    <div class="btn-group w-100 shadow-sm">
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">APPLY FILTERS</button>
                        <a href="audit-logs.php" class="btn btn-sm border fw-bold text-muted">RESET</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-lgu table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4 py-3 text-muted small text-uppercase">Severity</th>
                            <th class="text-muted small text-uppercase">Timestamp</th>
                            <th class="text-muted small text-uppercase">User</th>
                            <th class="text-muted small text-uppercase">Action</th>
                            <th class="text-muted small text-uppercase">IP Address</th>
                            <th class="text-muted small text-uppercase">Reference ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted small italic">No audit records found matching your filters.</td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                            <tr onclick="showLogDetails(this)" 
                                data-user="<?= htmlspecialchars($log['username'] ?? 'SYSTEM') ?>"
                                data-action="<?= htmlspecialchars($log['action']) ?>"
                                data-time="<?= Helper::formatDateTime($log['created_at']) ?>"
                                data-details="<?= htmlspecialchars($log['details']) ?>"
                                data-ip="<?= htmlspecialchars($log['ip_address']) ?>"
                                data-agent="<?= htmlspecialchars($log['user_agent'] ?? 'Unknown Device') ?>">
                                <td class="ps-4"><?= getSeverityTag($log['action']) ?></td>
                                <td class="small text-secondary"><?= Helper::formatDateTime($log['created_at']) ?></td>
                                <td>
                                    <div class="fw-bold text-primary small"><?= htmlspecialchars($log['username'] ?? 'SYSTEM') ?></div>
                                </td>
                                <td><span class="badge bg-light text-dark border fw-normal px-2 py-1"><?= htmlspecialchars($log['action']) ?></span></td>
                                <td class="small font-monospace text-muted"><?= htmlspecialchars($log['ip_address']) ?></td>
                                <td class="small text-muted">
                                    <?= $log['entity_type'] ? (htmlspecialchars($log['entity_type']) . " <span class='text-secondary fw-bold'>#" . $log['entity_id'] . "</span>") : '<span class="text-muted opacity-50">-</span>' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer py-3 border-0">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <span class="info-text text-muted">
                        Showing <strong><?= ($offset + 1) ?></strong> to 
                        <strong><?= min($offset + $limit, $totalLogs) ?></strong> of 
                        <strong><?= $totalLogs ?></strong> entries
                    </span>
                </div>
                <div class="col-md-6 text-md-end">
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm justify-content-center justify-content-md-end mb-0">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?p=1&<?= $query_string ?>"><i class="bi bi-chevron-double-left"></i></a>
                            </li>
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?p=<?= ($page - 1) ?>&<?= $query_string ?>">Prev</a>
                            </li>
                            <?php
                            $start = max(1, $page - 2);
                            $end = min($totalPages, $page + 2);
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?p=<?= $i ?>&<?= $query_string ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?p=<?= ($page + 1) ?>&<?= $query_string ?>">Next</a>
                            </li>
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?p=<?= $totalPages ?>&<?= $query_string ?>"><i class="bi bi-chevron-double-right"></i></a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="purgeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <div class="modal-header bg-light">
                    <h6 class="modal-title fw-bold text-danger"><i class="bi bi-database-fill-dash me-2"></i>Storage Management</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <p class="small text-muted mb-3">Optimize database by removing old activity logs.</p>
                    <label class="small fw-bold text-uppercase d-block mb-2">Delete logs older than:</label>
                    <select name="purge_years" class="form-select form-select-sm mb-3 text-center">
                        <option value="1">1 Year</option>
                        <option value="2" selected>2 Years</option>
                        <option value="3">3 Years</option>
                        <option value="5">5 Years</option>
                    </select>
                    <div class="alert alert-warning p-2 small mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Warning: This action cannot be undone.
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light p-2">
                    <button type="button" class="btn btn-light btn-sm fw-bold border" data-bs-dismiss="modal">CANCEL</button>
                    <button type="submit" name="purge_logs" class="btn btn-danger btn-sm fw-bold px-3 shadow-sm" onclick="return confirm('Are you sure you want to permanently delete these logs?')">PURGE NOW</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="logModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalTitle">Activity Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="text-uppercase small fw-bold text-muted d-block">Performed By</label>
                        <span id="modalUser" class="fw-bold text-primary"></span>
                    </div>
                    <div class="col-6 text-end">
                        <label class="text-uppercase small fw-bold text-muted d-block">IP Address</label>
                        <span id="modalIP" class="text-dark font-monospace small"></span>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="text-uppercase small fw-bold text-muted d-block">Timestamp</label>
                    <span id="modalTime" class="text-dark"></span>
                </div>
                <div class="mb-3 p-2 bg-light border rounded">
                    <label class="text-uppercase small fw-bold text-muted d-block" style="font-size: 0.65rem;">Device / Browser Info</label>
                    <span id="modalAgentDisplay" class="fw-bold d-block"></span>
                    <span id="modalAgentRaw" class="text-muted small italic" style="font-size: 0.7rem;"></span>
                </div>
                <hr>
                <div class="mb-0">
                    <label class="text-uppercase small fw-bold text-muted d-block mb-2">Changes Made</label>
                    <div id="modalDetails" class="p-3 bg-dark text-light border rounded small italic" style="white-space: pre-wrap; font-family: monospace;"></div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function showLogDetails(row) {
    const user = row.getAttribute('data-user');
    const action = row.getAttribute('data-action');
    const time = row.getAttribute('data-time');
    const details = row.getAttribute('data-details');
    const ip = row.getAttribute('data-ip');
    const agent = row.getAttribute('data-agent');

    // Browser & Device Parsing
    let displayDevice = "Unknown Device";
    let displayBrowser = "Unknown Browser";

    if (agent.includes("Windows NT 10.0")) displayDevice = "Windows 10/11 Desktop";
    else if (agent.includes("Android")) displayDevice = "Android Mobile";
    else if (agent.includes("iPhone")) displayDevice = "iPhone/iOS";
    else if (agent.includes("Macintosh")) displayDevice = "Mac Desktop";

    if (agent.includes("Chrome") && !agent.includes("Edg")) displayBrowser = "Google Chrome";
    else if (agent.includes("Edg")) displayBrowser = "Microsoft Edge";
    else if (agent.includes("Firefox")) displayBrowser = "Mozilla Firefox";
    else if (agent.includes("Safari") && !agent.includes("Chrome")) displayBrowser = "Apple Safari";

    document.getElementById('modalTitle').innerText = action;
    document.getElementById('modalUser').innerText = user;
    document.getElementById('modalTime').innerText = time;
    document.getElementById('modalIP').innerText = ip;
    document.getElementById('modalAgentDisplay').innerText = displayDevice + " (" + displayBrowser + ")";
    document.getElementById('modalAgentRaw').innerText = agent;
    document.getElementById('modalDetails').innerText = details ? details : "No specific data changes recorded.";

    const myModal = new bootstrap.Modal(document.getElementById('logModal'));
    myModal.show();
}
</script>

<?php include __DIR__ . '/footer.php'; ?>
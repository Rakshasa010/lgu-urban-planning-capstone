<?php

// User Management

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helper.php';
require_once __DIR__ . '/../modules/UserAccessManagement/UserController.php';

$auth = new Auth();
$auth->requirePermission('manage_users');
$auth->requireRole(['admin']);
$userController = new UserController();

// --- PAGINATION SETTINGS ---
$limit = 10; 
$page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// --- EXPORT HANDLER ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filters = ['role' => $_GET['role'] ?? '', 'is_active' => $_GET['is_active'] ?? '', 'search' => $_GET['search'] ?? ''];
    $usersToExport = $userController->getAllUsers($filters);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=users_export_' . date('Ymd_His') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'First Name', 'Last Name', 'Username', 'Email', 'Role', 'Status', 'Verified', 'Created At']);

    foreach ($usersToExport as $u) {
        fputcsv($output, [
            $u['id'],
            $u['first_name'],
            $u['last_name'],
            $u['username'],
            $u['email'],
            strtoupper($u['role']),
            $u['is_active'] ? 'Active' : 'Inactive',
            $u['is_verified'] ? 'Yes' : 'No',
            $u['created_at'] ?? 'N/A'
        ]);
    }
    fclose($output);
    exit;
}

// --- AJAX HANDLER ---
if (isset($_GET['action'])) {
    while (ob_get_level()) { ob_end_clean(); } 
    header('Content-Type: application/json');
    $uId = $_GET['user_id'] ?? 0;
    
    try {
        if ($_GET['action'] === 'get_history') {
            $history = $userController->getUserHistory($uId);
            echo json_encode([
                'success' => true, 
                'last_login' => $history['last_login'] ?? 'No record', 
                'app_count' => $history['app_count'] ?? 0, 
                'applications' => $history['applications'] ?? []
            ]);
            exit;
        }

        if ($_GET['action'] === 'get_verification') {
    $user = $userController->getUserById($uId);
    if (!$user) throw new Exception("User not found");
    
    // CHANGE THIS: Ensure this matches your XAMPP folder name exactly
    $projectName = "lgu-urban-planning"; 
    
    // We add a leading slash so it starts from 'localhost'
    $front = !empty($user['id_front_path']) ? "/" . $projectName . "/" . $user['id_front_path'] : null;
    $back = !empty($user['id_back_path']) ? "/" . $projectName . "/" . $user['id_back_path'] : null;
    
    // Fallback for older data
    if (!$front && !empty($user['id_proof_path'])) {
        $front = "/" . $projectName . "/" . $user['id_proof_path'];
    }
    
    echo json_encode([
        'success' => true,
        'id_front' => $front,
        'id_back' => $back,
        'is_verified' => (int)$user['is_verified'],
        'rejection_reason' => $user['rejection_reason'] ?? ''
    ]);
    exit;
}
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

$error = '';
$success = '';

// --- POST ACTIONS HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $userId = $_POST['user_id'] ?? 0;
        try {
            if ($_POST['action'] === 'verify_user') {
                $status = $_POST['status']; 
                $reason = ($status === 'reject') ? ($_POST['rejection_reason'] ?? '') : '';
                
                if ($reason === 'Other') {
                    $reason = $_POST['custom_reason'] ?? 'Rejected';
                }

                // 1. I-update ang verification status sa database
                $userController->verifyIdentity($userId, $status, $reason);

                // 2. MAGDAGDAG NG CODE DITO PARA SA MESSAGE:
                $db = Database::getInstance();
                $subject = ($status === 'approve') ? "Identity Verified Successfully" : "Identity Verification Rejected";
                $messageBody = ($status === 'approve') 
                    ? "Congratulations! Your identity has been verified. You can now proceed with your applications."
                    : "Unfortunately, your identity verification was rejected due to: " . $reason . ". Please re-upload a clear copy of your ID.";

                $sqlMessage = "INSERT INTO messages (sender_id, receiver_id, subject, message, is_read, message_type, created_at) 
                   VALUES (?, ?, ?, ?, 0, 'system', NOW())";
    
                try {
                    $adminId = $_SESSION['user_id'] ?? 0;
                    $db->query($sqlMessage, [$adminId, $userId, $subject, $messageBody]);
                    
                    $success = ($status === 'approve') ? 'User identity verified and applicant notified.' : 'Verification rejected and message sent.';
                } catch (PDOException $e) {
                    $error = "User status updated but message failed: " . $e->getMessage();
                }
            }
            elseif ($_POST['action'] === 'create' || $_POST['action'] === 'update') {
                $password = $_POST['password'] ?? '';
                if (!empty($password)) {
                    if (strlen($password) < 8 || !preg_match('@[A-Z]@', $password) || !preg_match('@[0-9]@', $password)) {
                        throw new Exception('Password must be 8+ chars with uppercase and numbers.');
                    }
                }
                
                $data = [
                    'first_name' => $_POST['first_name'], 
                    'last_name' => $_POST['last_name'], 
                    'email' => $_POST['email'], 
                    'role' => $_POST['role'],
                    'phone' => $_POST['phone'] ?? '',
                    'username' => $_POST['username'] ?? ''
                ];
                if(!empty($password)) $data['password'] = $password;

                if ($_POST['action'] === 'create') {
                    $userController->createUser($data);
                    $success = 'User created successfully.';
                } else {
                    $userController->updateUser($userId, $data);
                    $success = 'User updated successfully.';
                }
            }
            elseif ($_POST['action'] === 'deactivate') { $userController->deactivateUser($userId); $success = 'User deactivated.'; }
            elseif ($_POST['action'] === 'activate') { $userController->activateUser($userId); $success = 'User activated.'; }
            
            // --- BULK ACTIONS ---
            elseif ($_POST['action'] === 'bulk_deactivate' || $_POST['action'] === 'bulk_activate') {
                $selectedIds = $_POST['selected_users'] ?? [];
                if (empty($selectedIds)) throw new Exception("No users selected.");
                
                foreach ($selectedIds as $id) {
                    if ($_POST['action'] === 'bulk_deactivate') {
                        $userController->deactivateUser($id);
                    } else {
                        $userController->activateUser($id);
                    }
                }
                $success = "Bulk action completed for " . count($selectedIds) . " users.";
            }

        } catch (Exception $e) { $error = $e->getMessage(); }
    }
}

// --- FETCH DATA WITH PAGINATION ---
$filters = ['role' => $_GET['role'] ?? '', 'is_active' => $_GET['is_active'] ?? '', 'search' => $_GET['search'] ?? ''];

$totalUsers = $userController->getTotalUsersCount($filters);
$totalPages = ceil($totalUsers / $limit);
$users = $userController->getAllUsersPaginated($filters, $limit, $offset);

$pageTitle = 'User Management';
include __DIR__ . '/header.php';
?>

<style>
    /* Base UI Enhancements */
    .strength-meter { height: 5px; background-color: #e2e8f0; border-radius: 3px; margin-top: 6px; overflow: hidden; }
    .strength-bar { height: 100%; width: 0%; transition: all 0.3s ease; }
    .cursor-pointer { cursor: pointer; }
    
    /* Light/Dark Adaptive Status Colors */
    .status-active { background-color: #d1e7dd; color: #0f5132; }
    .status-inactive { background-color: #f8d7da; color: #842029; }

    /* Verification Preview */
    .img-verify-preview { 
        width: 100%; height: 220px; object-fit: contain; border-radius: 8px; 
        border: 1px solid var(--bs-border-color); cursor: pointer; 
        background-color: var(--bs-tertiary-bg); transition: transform 0.2s; 
    }
    .img-verify-preview:hover { transform: scale(1.02); border-color: #0d6efd; }
    #fullImagePreview { max-width: 100%; height: auto; border-radius: 4px; }
    
    /* Toolbars & Online Indicators */
    .bulk-toolbar { 
        display: none; 
        background: var(--bs-secondary-bg); 
        border: 1px solid var(--bs-border-color); 
        border-radius: 8px; 
    }
    .online-dot { height: 10px; width: 10px; background-color: #198754; border-radius: 50%; display: inline-block; margin-right: 5px; border: 2px solid var(--bs-body-bg); box-shadow: 0 0 0 1px #198754; }
    .offline-dot { height: 10px; width: 10px; background-color: #adb5bd; border-radius: 50%; display: inline-block; margin-right: 5px; }

    /* Pagination Styling Customization */
    .pagination .page-link { color: #333; border: 1px solid #dee2e6; padding: 0.5rem 0.75rem; }
    .pagination .page-item.active .page-link { background-color: #212529; border-color: #212529; color: #fff; }
    .pagination .page-item.disabled .page-link { color: #bcbcbc; }

</style>

<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0 fw-bold">User Management</h2>
            <p class="text-muted small">Manage accounts and verify applicant identities.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="?export=csv&<?= http_build_query($filters) ?>" class="btn btn-outline-dark shadow-sm">
                <i class="bi bi-download"></i> Export CSV
            </a>
            <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="bi bi-person-plus"></i> Create User
            </button>
        </div>
    </div>
    
    <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5"><input type="text" class="form-control" name="search" placeholder="Search name, email..." value="<?= htmlspecialchars($filters['search']) ?>"></div>
                <div class="col-md-2">
                    <select class="form-select" name="role">
                        <option value="">All Roles</option>
                        <option value="admin" <?= $filters['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                        <option value="applicant" <?= $filters['role'] === 'applicant' ? 'selected' : '' ?>>Applicant</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="is_active">
                        <option value="">All Status</option>
                        <option value="1" <?= $filters['is_active'] === '1' ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $filters['is_active'] === '0' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3"><button type="submit" class="btn btn-dark w-100">Apply Filter</button></div>
            </form>
        </div>
    </div>

    <div id="bulkToolbar" class="bulk-toolbar p-3 mb-3">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <span class="badge bg-primary rounded-pill me-2" id="selectedCount">0</span> users selected for bulk action
            </div>
            <div>
                <button type="button" class="btn btn-outline-danger btn-sm me-2" onclick="submitBulkAction('bulk_deactivate')">
                    <i class="bi bi-person-x"></i> Deactivate Selected
                </button>
                <button type="button" class="btn btn-outline-success btn-sm" onclick="submitBulkAction('bulk_activate')">
                    <i class="bi bi-person-check"></i> Activate Selected
                </button>
            </div>
        </div>
    </div>
    
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <form id="bulkForm" method="POST">
                    <input type="hidden" name="action" id="bulkActionInput" value="">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width: 40px;">
                                    <input type="checkbox" class="form-check-input" id="selectAll">
                                </th>
                                <th>User Details</th>
                                <th>Role</th>
                                <th>System Status</th>
                                <th>Identity Verification</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            foreach ($users as $user): 
                                $isOnline = false;
                                if (!empty($user['last_activity'])) {
                                    $lastActivity = strtotime($user['last_activity']);
                                    $currentTime = time();
                                    if (($currentTime - $lastActivity) <= 300 && $lastActivity > 0) {
                                        $isOnline = true;
                                    }
                                }
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <input type="checkbox" name="selected_users[]" value="<?= $user['id'] ?>" class="form-check-input user-checkbox">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="<?= $isOnline ? 'online-dot' : 'offline-dot' ?>" title="<?= $isOnline ? 'Online' : 'Offline' ?>"></div>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($user['email']) ?> | @<?= htmlspecialchars($user['username']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-secondary text-uppercase" style="font-size: 0.65rem;"><?= htmlspecialchars($user['role']) ?></span></td>
                                <td><span class="badge px-3 <?= $user['is_active'] ? 'status-active' : 'status-inactive' ?>"><?= $user['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                                <td>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <span class="text-muted small">Staff Member</span>
                                    <?php else: ?>
                                        <span class="small fw-bold cursor-pointer <?= $user['is_verified'] ? 'text-success' : 'text-warning' ?>" 
                                              onclick="openVerificationModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?>')">
                                            <i class="bi <?= $user['is_verified'] ? 'bi-check-circle-fill' : 'bi-clock-history' ?>"></i> 
                                            <?= $user['is_verified'] ? 'VERIFIED' : 'PENDING / UNVERIFIED' ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-dark" onclick="viewLogs(<?= $user['id'] ?>, '<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>')"><i class="bi bi-clock-history"></i></button>
                                    <button type="button" class="btn btn-sm btn-light border" onclick='editUser(<?= json_encode($user) ?>)'><i class="bi bi-pencil-square"></i> Edit</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="quickAction(<?= $user['id'] ?>, '<?= $user['is_active'] ? 'deactivate' : 'activate' ?>')">
                                        <?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
        <div class="card-footer border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="small text-muted">
                    Showing <b><?= $offset + 1 ?></b> to <b><?= min($offset + $limit, $totalUsers) ?></b> of <b><?= $totalUsers ?></b> users
                </div>
                <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?p=<?= $page - 1 ?>&<?= http_build_query($filters) ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>

                        <?php 
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        for ($i = $start; $i <= $end; $i++): 
                        ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?p=<?= $i ?>&<?= http_build_query($filters) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?p=<?= $page + 1 ?>&<?= http_build_query($filters) ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
        </div>
</div>

<form id="quickActionForm" method="POST" style="display:none;">
    <input type="hidden" name="user_id" id="qa_user_id">
    <input type="hidden" name="action" id="qa_action">
</form>

<div class="modal fade" id="verificationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Identity Validation: <span id="v_name"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="verify_user">
                <input type="hidden" name="user_id" id="v_user_id">
                <div id="v_loading" class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2 text-muted">Fetching documents...</p>
                </div>
                <div id="v_content" style="display:none;">
                    <div class="row g-3">
                        <div class="col-md-6 text-center">
                            <label class="small fw-bold d-block mb-1">ID Front</label>
                            <img src="" id="img_front" class="img-verify-preview" onclick="zoomImage(this.src)">
                        </div>
                        <div class="col-md-6 text-center">
                            <label class="small fw-bold d-block mb-1">ID Back</label>
                            <img src="" id="img_back" class="img-verify-preview" onclick="zoomImage(this.src)">
                        </div>
                    </div>
                    <div class="mt-4 p-3 bg-light rounded shadow-sm">
                        <label class="small fw-bold mb-2">Verification Decision</label>
                        <select name="status" id="v_decision" class="form-select shadow-sm" onchange="toggleRejectionBox(this.value)">
                            <option value="approve">Approve / Verified</option>
                            <option value="reject">Reject / Needs Re-upload</option>
                        </select>
                        <div id="rejection_box" class="mt-3" style="display:none;">
                            <label class="small fw-bold text-danger">Reason for Rejection</label>
                            <select name="rejection_reason" id="v_rejection_reason" class="form-select mb-2" onchange="checkOtherReason(this.value)">
                                <option value="Blurry or Unreadable ID">Blurry or Unreadable ID</option>
                                <option value="Expired Identification Card">Expired Identification Card</option>
                                <option value="ID Type not supported">ID Type not supported</option>
                                <option value="Name on ID does not match profile">Name on ID does not match profile</option>
                                <option value="Missing back part of the ID">Missing back part of the ID</option>
                                <option value="Other">Other (Please specify...)</option>
                            </select>
                            <textarea name="custom_reason" id="v_custom_reason" class="form-control" placeholder="Type specific reason here..." style="display:none;"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0" id="v_footer" style="display:none;">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-dark px-4">Save Decision</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="imageZoomModal" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 bg-transparent">
            <div class="modal-body p-0 text-center position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
                <img src="" id="fullImagePreview" class="shadow-lg">
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white"><h5>Create New User</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body row g-3">
                <input type="hidden" name="action" value="create">
                <div class="col-md-6"><label class="small fw-bold">First Name</label><input type="text" name="first_name" class="form-control" required></div>
                <div class="col-md-6"><label class="small fw-bold">Last Name</label><input type="text" name="last_name" class="form-control" required></div>
                <div class="col-12"><label class="small fw-bold">Username</label><input type="text" name="username" class="form-control" required></div>
                <div class="col-12"><label class="small fw-bold">Email</label><input type="email" name="email" class="form-control" required></div>
                <div class="col-md-6">
                    <label class="small fw-bold">Password</label>
                    <div class="input-group">
                        <input type="password" name="password" id="create_p" class="form-control" onkeyup="checkStrength(this.value, 's_create')" required>
                        <span class="input-group-text bg-white" onclick="togglePasswordVisibility('create_p', 'create_eye')"><i class="bi bi-eye-slash" id="create_eye"></i></span>
                    </div>
                    <div class="strength-meter"><div id="s_create" class="strength-bar"></div></div>
                </div>
                <div class="col-md-6">
                    <label class="small fw-bold">Role</label>
                    <select name="role" class="form-select"><option value="applicant">Applicant</option><option value="admin">Admin</option></select>
                </div>
            </div>
            <div class="modal-footer border-0"><button type="submit" class="btn btn-primary w-100 py-2 shadow-sm">Create Account</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white"><h5>Edit User Account</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body row g-3">
                <input type="hidden" name="action" value="update"><input type="hidden" name="user_id" id="e_id">
                <div class="col-md-6"><label class="small fw-bold">First Name</label><input type="text" name="first_name" id="e_fname" class="form-control" required></div>
                <div class="col-md-6"><label class="small fw-bold">Last Name</label><input type="text" name="last_name" id="e_lname" class="form-control" required></div>
                <div class="col-12"><label class="small fw-bold">Username</label><input type="text" name="username" id="e_username" class="form-control" required></div>
                <div class="col-12"><label class="small fw-bold">Email</label><input type="email" name="email" id="e_email" class="form-control" required></div>
                <div class="col-12"><label class="small fw-bold">Phone</label><input type="text" name="phone" id="e_phone" class="form-control"></div>
                <div class="col-md-6">
                    <label class="small fw-bold">Role</label>
                    <select name="role" id="e_role" class="form-select"><option value="applicant">Applicant</option><option value="admin">Admin</option></select>
                </div>
                <div class="col-md-6">
                    <label class="small fw-bold">New Password (Optional)</label>
                    <div class="input-group">
                        <input type="password" name="password" id="e_p" class="form-control" onkeyup="checkStrength(this.value, 's_edit')">
                        <span class="input-group-text bg-white" onclick="togglePasswordVisibility('e_p', 'edit_eye')"><i class="bi bi-eye-slash" id="edit_eye"></i></span>
                    </div>
                    <div class="strength-meter"><div id="s_edit" class="strength-bar"></div></div>
                </div>
            </div>
            <div class="modal-footer border-0"><button type="submit" class="btn btn-dark w-100 shadow-sm">Update User</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="logsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white"><h5 class="modal-title">Activity Logs: <span id="log_user_name"></span></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="logs_content"></div>
        </div>
    </div>
</div>

<script>
const CURRENT_FILE = window.location.pathname.split('/').pop() || 'users.php';

const selectAll = document.getElementById('selectAll');
const userCheckboxes = document.querySelectorAll('.user-checkbox');
const bulkToolbar = document.getElementById('bulkToolbar');
const selectedCountText = document.getElementById('selectedCount');

function updateBulkToolbar() {
    const checkedCount = document.querySelectorAll('.user-checkbox:checked').length;
    if (checkedCount > 0) {
        bulkToolbar.style.display = 'block';
        selectedCountText.innerText = checkedCount;
    } else {
        bulkToolbar.style.display = 'none';
    }
}

selectAll.addEventListener('change', function() {
    userCheckboxes.forEach(cb => cb.checked = this.checked);
    updateBulkToolbar();
});

userCheckboxes.forEach(cb => {
    cb.addEventListener('change', updateBulkToolbar);
});

function submitBulkAction(action) {
    if (confirm(`Are you sure you want to perform this bulk action?`)) {
        document.getElementById('bulkActionInput').value = action;
        document.getElementById('bulkForm').submit();
    }
}

function quickAction(id, action) {
    if (confirm('Change status?')) {
        document.getElementById('qa_user_id').value = id;
        document.getElementById('qa_action').value = action;
        document.getElementById('quickActionForm').submit();
    }
}

function togglePasswordVisibility(inputId, eyeId) {
    const input = document.getElementById(inputId);
    const eye = document.getElementById(eyeId);
    if (input.type === "password") {
        input.type = "text";
        eye.classList.replace("bi-eye-slash", "bi-eye");
    } else {
        input.type = "password";
        eye.classList.replace("bi-eye", "bi-eye-slash");
    }
}

function zoomImage(src) {
    if (src.includes('placehold.co')) return; 
    document.getElementById('fullImagePreview').src = src;
    new bootstrap.Modal(document.getElementById('imageZoomModal')).show();
}

function openVerificationModal(userId, name) {
    document.getElementById('v_user_id').value = userId;
    document.getElementById('v_name').innerText = name;
    document.getElementById('v_loading').style.display = 'block';
    document.getElementById('v_content').style.display = 'none';
    document.getElementById('v_footer').style.display = 'none';

    new bootstrap.Modal(document.getElementById('verificationModal')).show();

    fetch(`${CURRENT_FILE}?action=get_verification&user_id=${userId}`)
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                const placeholder = 'https://placehold.co/400x300?text=No+Image+Found'; 
                
                document.getElementById('img_front').src = data.id_front ? data.id_front : placeholder;
                document.getElementById('img_back').src = data.id_back ? data.id_back : placeholder;
                
                document.getElementById('v_decision').value = data.is_verified ? 'approve' : 'reject';
                
                const selectReason = document.getElementById('v_rejection_reason');
                const customText = document.getElementById('v_custom_reason');
                
                if (data.rejection_reason) {
                    let exists = Array.from(selectReason.options).some(opt => opt.value === data.rejection_reason);
                    if (exists) {
                        selectReason.value = data.rejection_reason;
                        customText.style.display = 'none';
                    } else {
                        selectReason.value = 'Other';
                        customText.value = data.rejection_reason;
                        customText.style.display = 'block';
                    }
                }

                toggleRejectionBox(document.getElementById('v_decision').value);
                
                document.getElementById('v_loading').style.display = 'none';
                document.getElementById('v_content').style.display = 'block';
                document.getElementById('v_footer').style.display = 'flex';
            }
        });
}

function toggleRejectionBox(val) {
    document.getElementById('rejection_box').style.display = (val === 'reject') ? 'block' : 'none';
}

function checkOtherReason(val) {
    document.getElementById('v_custom_reason').style.display = (val === 'Other') ? 'block' : 'none';
    if(val !== 'Other') document.getElementById('v_custom_reason').value = '';
}

function editUser(u) {
    document.getElementById('e_id').value = u.id;
    document.getElementById('e_fname').value = u.first_name;
    document.getElementById('e_lname').value = u.last_name;
    document.getElementById('e_username').value = u.username;
    document.getElementById('e_email').value = u.email;
    document.getElementById('e_phone').value = u.phone || '';
    document.getElementById('e_role').value = u.role;
    document.getElementById('e_p').value = '';
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

function viewLogs(userId, userName) {
    document.getElementById('log_user_name').innerText = userName;
    const content = document.getElementById('logs_content');
    content.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>`;
    new bootstrap.Modal(document.getElementById('logsModal')).show();
    
    fetch(`${CURRENT_FILE}?action=get_history&user_id=${userId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                let appRows = (data.applications || []).map(app => 
                    `<tr><td><b>${app.application_number}</b></td><td>${app.project_name}</td><td><span class="badge bg-light text-dark border">${app.status}</span></td><td>${app.created_at}</td></tr>`
                ).join('') || '<tr><td colspan="4" class="text-center py-3">No applications found.</td></tr>';
                
                content.innerHTML = `
                    <div class="row g-2 mb-3">
                        <div class="col-6"><div class="p-3 border bg-light rounded small">LAST LOGIN:<br><b>${data.last_login}</b></div></div>
                        <div class="col-6"><div class="p-3 border bg-light rounded small">TOTAL SUBMISSIONS:<br><b>${data.app_count}</b></div></div>
                    </div>
                    <div class="table-responsive"><table class="table table-sm small border">
                        <thead class="table-light"><tr><th>ID</th><th>Project</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>${appRows}</tbody>
                    </table></div>`;
            }
        });
}

function checkStrength(password, barId) {
    let s = 0;
    if (password.length >= 8) s += 25;
    if (password.match(/[a-z]/)) s += 25;
    if (password.match(/[A-Z]/)) s += 25;
    if (password.match(/[0-9]/)) s += 25;
    let bar = document.getElementById(barId);
    bar.style.width = s + "%";
    bar.style.backgroundColor = s <= 50 ? "#dc3545" : (s <= 75 ? "#ffc107" : "#198754");
}
</script>

<?php include __DIR__ . '/footer.php'; ?>
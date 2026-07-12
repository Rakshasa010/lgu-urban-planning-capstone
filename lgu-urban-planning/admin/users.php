<?php

// User Management

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helper.php';
require_once __DIR__ . '/../modules/UserAccessManagement/UserController.php';

$auth = new Auth();
$auth->requirePermission('manage_users');
$auth->requireRole(['admin', 'super_admin']);
$userController = new UserController();

// ── Load language from session (written by settings.php on every save) ────────
$lang = $_SESSION['locale_language'] ?? 'en_PH';

// ── Translation strings for users.php ────────────────────────────────────────
$translations = [
    'en_PH' => [
        // Page header
        'page_title'            => 'User Management',
        'page_subtitle'         => 'Manage accounts and verify applicant identities.',
        // Action buttons
        'btn_export_csv'        => 'Export CSV',
        'btn_create_user'       => 'Create User',
        'btn_apply_filter'      => 'Apply Filter',
        'btn_edit'              => 'Edit',
        'btn_activate'          => 'Activate',
        'btn_close'             => 'Close',
        'btn_save_decision'     => 'Save Decision',
        'btn_create_account'    => 'Create Account',
        'btn_update_user'       => 'Update User',
        'btn_cancel'            => 'Cancel',
        'btn_verify_download'   => 'Verify & Download',
        // Filters
        'filter_search_ph'      => 'Search name, email...',
        'filter_all_roles'      => 'All Roles',
        // Table headers
        'th_user_details'       => 'User Details',
        'th_role'               => 'Role',
        'th_system_status'      => 'System Status',
        'th_id_verification'    => 'Identity Verification',
        'th_actions'            => 'Actions',
        // Inline status
        'status_active'         => 'Active',
        'status_inactive'       => 'Inactive',
        'status_online'         => 'Online',
        'status_offline'        => 'Offline',
        'label_staff_member'    => 'Staff Member',
        'label_verified'        => 'VERIFIED',
        'label_pending'         => 'PENDING / UNVERIFIED',
        // Pagination
        'pagination_showing'    => 'Showing',
        'pagination_to'         => 'to',
        'pagination_of'         => 'of',
        'pagination_users'      => 'users',
        // Verification modal
        'modal_verify_title'    => 'Identity Validation',
        'modal_verify_loading'  => 'Fetching documents...',
        'modal_id_front'        => 'ID Front',
        'modal_id_back'         => 'ID Back',
        'modal_verify_decision' => 'Verification Decision',
        'modal_approve'         => 'Approve / Verified',
        'modal_reject'          => 'Reject / Needs Re-upload',
        'modal_reject_reason'   => 'Reason for Rejection',
        'reject_blurry'         => 'Blurry or Unreadable ID',
        'reject_expired'        => 'Expired Identification Card',
        'reject_unsupported'    => 'ID Type not supported',
        'reject_name_mismatch'  => 'Name on ID does not match profile',
        'reject_missing_back'   => 'Missing back part of the ID',
        'reject_other'          => 'Other (Please specify...)',
        // Create / Edit user modal
        'modal_create_title'    => 'Create New User',
        'modal_edit_title'      => 'Edit User Account',
        'label_first_name'      => 'First Name',
        'label_last_name'       => 'Last Name',
        'label_username'        => 'Username',
        'label_email'           => 'Email',
        'label_password'        => 'Password',
        'label_new_password'    => 'New Password (Optional)',
        'label_role'            => 'Role',
        'label_phone'           => 'Phone',
        // Role labels
        'role_applicant'        => 'Applicant',
        'role_inspector'        => 'Inspector',
        'role_zoning_officer'   => 'Zoning Officer',
        'role_building_official'=> 'Building Official',
        'role_assessor'         => 'Assessor',
        'role_admin'            => 'Admin',
        'role_super_admin'      => 'Super Admin',
        // Logs modal
        'modal_logs_title'      => 'Activity Logs',
        // Export modal
        'export_modal_title'    => 'Secure Export Verification',
        'export_warning'        => 'You are about to export sensitive user records. Please confirm your identity to proceed.',
        'export_purpose_label'  => 'Purpose of Export',
        'export_purpose_ph'     => '— Select a reason —',
        'export_password_label' => 'Admin Password',
        'export_password_ph'    => 'Re-enter your account password',
        // Success / error messages
        'success_verified'      => 'User identity verified and applicant notified.',
        'success_rejected'      => 'Verification rejected and message sent.',
        'success_created'       => 'User created successfully.',
        'success_updated'       => 'User updated successfully.',
        'success_activated'     => 'User activated.',
        'err_msg_failed'        => 'User status updated but message failed',
        'err_password'          => 'Password must be 8+ chars with uppercase and numbers.',
    ],
    'fil' => [
        // Page header
        'page_title'            => 'Pamamahala ng mga Gumagamit',
        'page_subtitle'         => 'Pamahalaan ang mga account at i-verify ang pagkakakilanlan ng mga aplikante.',
        // Action buttons
        'btn_export_csv'        => 'I-export ang CSV',
        'btn_create_user'       => 'Lumikha ng Gumagamit',
        'btn_apply_filter'      => 'Ilapat ang Filter',
        'btn_edit'              => 'I-edit',
        'btn_activate'          => 'I-aktibo',
        'btn_close'             => 'Isara',
        'btn_save_decision'     => 'I-save ang Desisyon',
        'btn_create_account'    => 'Lumikha ng Account',
        'btn_update_user'       => 'I-update ang Gumagamit',
        'btn_cancel'            => 'Kanselahin',
        'btn_verify_download'   => 'I-verify at I-download',
        // Filters
        'filter_search_ph'      => 'Maghanap ng pangalan, email...',
        'filter_all_roles'      => 'Lahat ng Papel',
        // Table headers
        'th_user_details'       => 'Detalye ng Gumagamit',
        'th_role'               => 'Papel',
        'th_system_status'      => 'Katayuan ng Sistema',
        'th_id_verification'    => 'Pagpapatunay ng Pagkakakilanlan',
        'th_actions'            => 'Mga Aksyon',
        // Inline status
        'status_active'         => 'Aktibo',
        'status_inactive'       => 'Hindi Aktibo',
        'status_online'         => 'Online',
        'status_offline'        => 'Offline',
        'label_staff_member'    => 'Miyembro ng Kawani',
        'label_verified'        => 'NAPATUNAYAN',
        'label_pending'         => 'NAKABINBIN / HINDI NAPATUNAYAN',
        // Pagination
        'pagination_showing'    => 'Ipinapakita',
        'pagination_to'         => 'hanggang',
        'pagination_of'         => 'sa',
        'pagination_users'      => 'mga gumagamit',
        // Verification modal
        'modal_verify_title'    => 'Pagpapatunay ng Pagkakakilanlan',
        'modal_verify_loading'  => 'Kinukuha ang mga dokumento...',
        'modal_id_front'        => 'Harap ng ID',
        'modal_id_back'         => 'Likod ng ID',
        'modal_verify_decision' => 'Desisyon sa Pagpapatunay',
        'modal_approve'         => 'Aprubahan / Napatunayan',
        'modal_reject'          => 'Tanggihan / Kailangang Muling I-upload',
        'modal_reject_reason'   => 'Dahilan ng Pagtanggi',
        'reject_blurry'         => 'Malabo o Hindi Mabasang ID',
        'reject_expired'        => 'Nag-expire na ang Identification Card',
        'reject_unsupported'    => 'Hindi sinusuportahan ang uri ng ID',
        'reject_name_mismatch'  => 'Hindi tugma ang pangalan sa ID at profile',
        'reject_missing_back'   => 'Nawawala ang likod ng ID',
        'reject_other'          => 'Iba pa (Mangyaring tukuyin...)',
        // Create / Edit user modal
        'modal_create_title'    => 'Lumikha ng Bagong Gumagamit',
        'modal_edit_title'      => 'I-edit ang Account ng Gumagamit',
        'label_first_name'      => 'Unang Pangalan',
        'label_last_name'       => 'Apelyido',
        'label_username'        => 'Username',
        'label_email'           => 'Email',
        'label_password'        => 'Password',
        'label_new_password'    => 'Bagong Password (Opsyonal)',
        'label_role'            => 'Papel',
        'label_phone'           => 'Telepono',
        // Role labels
        'role_applicant'        => 'Aplikante',
        'role_inspector'        => 'Inspektor',
        'role_zoning_officer'   => 'Opisyal ng Zoning',
        'role_building_official'=> 'Opisyal ng Gusali',
        'role_assessor'         => 'Assessor',
        'role_admin'            => 'Admin',
        'role_super_admin'      => 'Super Admin',
        // Logs modal
        'modal_logs_title'      => 'Mga Log ng Aktibidad',
        // Export modal
        'export_modal_title'    => 'Secure na Pag-verify ng Export',
        'export_warning'        => 'Mag-e-export ka ng sensitibong mga rekord ng gumagamit. Mangyaring kumpirmahin ang iyong pagkakakilanlan upang magpatuloy.',
        'export_purpose_label'  => 'Layunin ng Export',
        'export_purpose_ph'     => '— Pumili ng dahilan —',
        'export_password_label' => 'Password ng Admin',
        'export_password_ph'    => 'Muling ilagay ang iyong password',
        // Success / error messages
        'success_verified'      => 'Napatunayan ang pagkakakilanlan ng gumagamit at naabisuhan ang aplikante.',
        'success_rejected'      => 'Tinanggihan ang pagpapatunay at naipadala ang mensahe.',
        'success_created'       => 'Matagumpay na nalikha ang gumagamit.',
        'success_updated'       => 'Matagumpay na na-update ang gumagamit.',
        'success_activated'     => 'Na-aktibo ang gumagamit.',
        'err_msg_failed'        => 'Na-update ang katayuan ng gumagamit ngunit nabigo ang mensahe',
        'err_password'          => 'Ang password ay dapat 8+ character na may malaking titik at numero.',
    ],
];

// Helper: get translated string, fallback to English
function t_users(string $key, array $translations, string $lang): string {
    return $translations[$lang][$key] ?? $translations['en_PH'][$key] ?? $key;
}

// --- PAGINATION SETTINGS ---
$limit = 10; 
$page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// --- EXPORT HANDLER (token-gated) ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Validate the one-time export token issued by verify_action.php
    $submittedToken = $_GET['export_token'] ?? '';
    $sessionToken   = $_SESSION['export_token'] ?? '';
    $tokenExpiry    = $_SESSION['export_token_expires'] ?? '';
    $tokenTable     = $_SESSION['export_token_table'] ?? '';

    $tokenValid = (
        !empty($submittedToken) &&
        !empty($sessionToken) &&
        hash_equals($sessionToken, $submittedToken) &&
        $tokenTable === 'users' &&
        strtotime($tokenExpiry) >= time()
    );

    // Invalidate token immediately (one-time use)
    unset($_SESSION['export_token'], $_SESSION['export_token_expires'],
          $_SESSION['export_token_table'], $_SESSION['export_token_type']);

    if (!$tokenValid) {
        http_response_code(403);
        die('<div style="font-family:sans-serif;padding:2rem;text-align:center;">
             <h3>&#128274; Export Denied</h3>
             <p>Invalid or expired export token. Please use the Export button and complete verification.</p>
             <a href="users.php">Go back</a></div>');
    }

    $filters = ['role' => $_GET['role'] ?? '', 'is_active' => $_GET['is_active'] ?? '', 'search' => $_GET['search'] ?? ''];
    $usersToExport = $userController->getAllUsers($filters);

    while (ob_get_level()) { ob_end_clean(); }
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
        if ($_GET['action'] === 'search_users') {
            $searchPage   = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
            if ($searchPage < 1) $searchPage = 1;
            $searchLimit  = 10;
            $searchOffset = ($searchPage - 1) * $searchLimit;

            $searchFilters = [
                'role'      => $_GET['role'] ?? '',
                'is_active' => $_GET['is_active'] ?? '',
                'search'    => $_GET['search'] ?? ''
            ];

            $searchTotal      = $userController->getTotalUsersCount($searchFilters);
            $searchTotalPages = max(1, ceil($searchTotal / $searchLimit));
            $searchUsers      = $userController->getAllUsersPaginated($searchFilters, $searchLimit, $searchOffset);

            $staffRoles = ['super_admin', 'admin', 'zoning_officer', 'building_official', 'assessor', 'inspector'];
            $rows = [];
            foreach ($searchUsers as $user) {
                $isOnline = false;
                if (!empty($user['last_activity'])) {
                    $lastActivity = strtotime($user['last_activity']);
                    $currentTime = time();
                    if (($currentTime - $lastActivity) <= 300 && $lastActivity > 0) {
                        $isOnline = true;
                    }
                }
                $rows[] = [
                    'id'          => $user['id'],
                    'first_name'  => $user['first_name'],
                    'last_name'   => $user['last_name'],
                    'username'    => $user['username'],
                    'email'       => $user['email'],
                    'role'        => $user['role'],
                    'is_active'   => (int)$user['is_active'],
                    'is_verified' => (int)$user['is_verified'],
                    'is_online'   => $isOnline,
                    'is_staff'    => in_array(strtolower($user['role']), $staffRoles),
                    'phone'       => $user['phone'] ?? ''
                ];
            }

            echo json_encode([
                'success'     => true,
                'users'       => $rows,
                'totalUsers'  => (int)$searchTotal,
                'totalPages'  => (int)$searchTotalPages,
                'page'        => $searchPage,
                'limit'       => $searchLimit,
                'offset'      => $searchOffset,
                'labels'      => [
                    'staff'    => t_users('label_staff_member', $translations, $lang),
                    'verified' => t_users('label_verified', $translations, $lang),
                    'pending'  => t_users('label_pending', $translations, $lang),
                    'active'   => t_users('status_active', $translations, $lang),
                    'inactive' => t_users('status_inactive', $translations, $lang),
                    'online'   => t_users('status_online', $translations, $lang),
                    'offline'  => t_users('status_offline', $translations, $lang),
                    'edit'     => t_users('btn_edit', $translations, $lang),
                    'activate' => t_users('btn_activate', $translations, $lang),
                    'showing'  => t_users('pagination_showing', $translations, $lang),
                    'to'       => t_users('pagination_to', $translations, $lang),
                    'of'       => t_users('pagination_of', $translations, $lang),
                    'users'    => t_users('pagination_users', $translations, $lang),
                ]
            ]);
            exit;
        }

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
                    
                    $success = ($status === 'approve') ? t_users('success_verified', $translations, $lang) : t_users('success_rejected', $translations, $lang);
                } catch (PDOException $e) {
                    $error = t_users('err_msg_failed', $translations, $lang) . ": " . $e->getMessage();
                }
            }
            elseif ($_POST['action'] === 'create' || $_POST['action'] === 'update') {
                $password = $_POST['password'] ?? '';
                if (!empty($password)) {
                    if (strlen($password) < 8 || !preg_match('@[A-Z]@', $password) || !preg_match('@[0-9]@', $password)) {
                        throw new Exception(t_users('err_password', $translations, $lang));
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
                    $success = t_users('success_created', $translations, $lang);
                } else {
                    $userController->updateUser($userId, $data);
                    $success = t_users('success_updated', $translations, $lang);
                }
            }
            elseif ($_POST['action'] === 'activate') { $userController->activateUser($userId); $success = t_users('success_activated', $translations, $lang); }
            

        } catch (Exception $e) { $error = $e->getMessage(); }
    }
}

// --- FETCH DATA WITH PAGINATION ---
$filters = ['role' => $_GET['role'] ?? '', 'is_active' => $_GET['is_active'] ?? '', 'search' => $_GET['search'] ?? ''];

$totalUsers = $userController->getTotalUsersCount($filters);
$totalPages = max(1, ceil($totalUsers / $limit));
$users = $userController->getAllUsersPaginated($filters, $limit, $offset);

// Pagination link query string (style matches applications.php)
$query_string = http_build_query(array_filter($filters));

$pageTitle = t_users('page_title', $translations, $lang);
$isAuthPage = true;
include __DIR__ . '/header.php';
?>

<style>
    /* ── BASE ── */
    .strength-meter { height: 5px; background-color: #e2e8f0; border-radius: 3px; margin-top: 6px; overflow: hidden; }
    .strength-bar { height: 100%; width: 0%; transition: all 0.3s ease; }
    .cursor-pointer { cursor: pointer; }
    .status-active { background-color: #d1e7dd; color: #0f5132; }
    .status-inactive { background-color: #f8d7da; color: #842029; }
    .img-verify-preview { width: 100%; height: 220px; object-fit: contain; border-radius: 8px; border: 1px solid var(--bs-border-color); cursor: pointer; background-color: var(--bs-tertiary-bg); transition: transform 0.2s; }
    .img-verify-preview:hover { transform: scale(1.02); border-color: #0d6efd; }
    #fullImagePreview { max-width: 100%; height: auto; border-radius: 4px; }
    .online-dot { height: 10px; width: 10px; background-color: #198754; border-radius: 50%; display: inline-block; margin-right: 5px; border: 2px solid var(--bs-body-bg); box-shadow: 0 0 0 1px #198754; }
    .offline-dot { height: 10px; width: 10px; background-color: #adb5bd; border-radius: 50%; display: inline-block; margin-right: 5px; }
    .pagination .page-link { color: #2c3e50; border: 1px solid #dee2e6; margin: 0 2px; border-radius: 4px; }
    .pagination .page-item.active .page-link { background-color: #0d6efd; border-color: #0d6efd; color: #fff; }
    .pagination .page-link:hover { background-color: #e7f1ff; border-color: #b6d4fe; color: #0d6efd; }
    .pagination .page-item.disabled .page-link { color: #bcbcbc; }

    /* ================================================
       MOBILE RESPONSIVE
       768px (Tablet) | 480px (Large Mobile) | 320px (Small Mobile)
       ================================================ */

    /* --- 768px: Tablet --- */
    @media (max-width: 768px) {

        .p-4 { padding: 1rem !important; }

        /* Page header: stack title + buttons */
        .d-flex.justify-content-between.align-items-center.mb-4 {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 10px;
        }
        .d-flex.justify-content-between.align-items-center.mb-4 .d-flex.gap-2 {
            width: 100%;
        }
        .d-flex.justify-content-between.align-items-center.mb-4 .btn {
            flex: 1;
            font-size: 0.82rem;
        }

        /* Filter form: stack inputs */
        .card-body .row.g-3 .col-md-5,
        .card-body .row.g-3 .col-md-2 {
            width: 100%;
            flex: 0 0 100%;
        }
        .card-body .row.g-3 .col-md-5:last-child { width: 100%; flex: 0 0 100%; }

        /* Table: shrink font, hide lower-priority columns */
        .table { font-size: 0.8rem; }
        .table th, .table td { padding: 0.5rem 0.4rem; }
        /* Hide Identity Verification col on tablet */
        .table thead th:nth-child(4),
        .table tbody td:nth-child(4) { display: none; }

        /* User details cell */
        .table td .fw-bold { font-size: 0.82rem; }
        .table td .text-muted.small { font-size: 0.72rem; }

        /* Action buttons: icon-only for history */
        .table .btn-sm { padding: 4px 8px; font-size: 0.75rem; }

        /* Card footer: stack pagination (style matches applications.php) */
        .card-footer .row { flex-direction: column; gap: 10px; text-align: center; }
        .card-footer .col-md-6:last-child { text-align: center !important; }
        .pagination { justify-content: center !important; }

        /* Pagination */
        .pagination .page-link { padding: 0.4rem 0.6rem; font-size: 0.8rem; }

        /* Modals */
        .modal-dialog.modal-lg,
        .modal-dialog.modal-xl {
            max-width: calc(100% - 1rem) !important;
            width: calc(100% - 1rem) !important;
            margin: 0.5rem auto;
        }
        .modal-body { padding: 1rem !important; }

        /* Verification modal: stack ID images */
        #verificationModal .col-md-6 { width: 100%; flex: 0 0 100%; }
        .img-verify-preview { height: 180px; }

        /* Create/Edit modal: stack 2-col fields */
        .modal-body .col-md-6 { width: 100%; flex: 0 0 100%; }
        .modal-body .row.g-3 { --bs-gutter-y: 0.5rem; }
    }

    /* --- 480px: Large Mobile --- */
    @media (max-width: 480px) {

        .p-4 { padding: 0.75rem !important; }

        /* Page header */
        .d-flex.justify-content-between.align-items-center.mb-4 h2 { font-size: 1.1rem; }
        .d-flex.justify-content-between.align-items-center.mb-4 p { font-size: 0.75rem; margin-bottom: 0; }
        .d-flex.justify-content-between.align-items-center.mb-4 .btn { font-size: 0.78rem; padding: 6px 10px; }

        /* Filter card */
        .card-body { padding: 0.75rem !important; }
        .form-control, .form-select { font-size: 0.82rem; padding: 6px 9px; }
        .card-body .row.g-3 { --bs-gutter-y: 0.4rem; }

        /* Table: also hide Role col */
        .table { font-size: 0.74rem; }
        .table th, .table td { padding: 0.4rem 0.3rem; }
        .table thead th:nth-child(2),
        .table tbody td:nth-child(2),
        .table thead th:nth-child(4),
        .table tbody td:nth-child(4) { display: none; }

        /* User details cell */
        .table td .fw-bold { font-size: 0.78rem; }
        .table td .text-muted.small { font-size: 0.68rem; }
        .online-dot, .offline-dot { width: 8px; height: 8px; }

        /* Action buttons */
        .table .btn-sm { font-size: 0.7rem; padding: 3px 7px; }

        /* Pagination */
        .pagination .page-link { padding: 0.35rem 0.5rem; font-size: 0.75rem; }
        .card-footer { padding: 0.6rem 0.75rem !important; }
        .card-footer .small { font-size: 0.72rem; }

        /* Modals */
        .modal-header { padding: 0.75rem 1rem !important; }
        .modal-title { font-size: 0.95rem; }
        .modal-body { padding: 0.75rem !important; }
        .modal-body .form-label { font-size: 0.75rem; margin-bottom: 2px; }
        .modal-body .form-control,
        .modal-body .form-select { font-size: 0.82rem; padding: 6px 9px; }
        .modal-body .mb-3 { margin-bottom: 0.6rem !important; }
        .modal-footer { padding: 0.6rem 0.75rem; }
        .modal-footer .btn { font-size: 0.82rem; padding: 7px 14px; }

        /* Verification modal */
        .img-verify-preview { height: 150px; }

        /* Logs modal table */
        #logsModal .table { font-size: 0.72rem; }
        #logsModal .table th, #logsModal .table td { padding: 0.35rem 0.3rem; }
    }

    /* --- 320px: Small Mobile --- */
    @media (max-width: 320px) {

        .p-4 { padding: 0.5rem !important; }

        /* Page header */
        .d-flex.justify-content-between.align-items-center.mb-4 h2 { font-size: 0.95rem; }
        .d-flex.justify-content-between.align-items-center.mb-4 p { font-size: 0.7rem; }
        .d-flex.justify-content-between.align-items-center.mb-4 .btn { font-size: 0.72rem; padding: 5px 8px; }

        /* Filter */
        .card-body { padding: 0.6rem !important; }
        .form-control, .form-select { font-size: 0.78rem; padding: 5px 8px; }

        /* Table: keep only User Details, Status, Actions */
        .table { font-size: 0.65rem; }
        .table th, .table td { padding: 0.3rem 0.2rem; }
        .table thead th:nth-child(2),
        .table tbody td:nth-child(2),
        .table thead th:nth-child(3),
        .table tbody td:nth-child(3),
        .table thead th:nth-child(4),
        .table tbody td:nth-child(4) { display: none; }

        /* User details cell */
        .table td .fw-bold { font-size: 0.72rem; }
        .table td .text-muted.small { font-size: 0.62rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 120px; }

        /* Action buttons: icon only, no text */
        .table .btn-sm { font-size: 0.62rem; padding: 2px 5px; }
        .table .btn-sm .bi-pencil-square ~ *,
        /* Hide "Edit" text label — keep icon only */
        .table td.text-center .btn-sm { min-width: 28px; }

        /* Pagination */
        .pagination { flex-wrap: wrap; gap: 2px; }
        .pagination .page-link { padding: 0.3rem 0.45rem; font-size: 0.68rem; }
        .card-footer { padding: 0.5rem 0.6rem !important; }
        .card-footer .small { font-size: 0.68rem; }

        /* Modals */
        .modal-dialog.modal-lg,
        .modal-dialog.modal-xl { margin: 0.25rem; }
        .modal-header { padding: 0.6rem 0.75rem !important; }
        .modal-title { font-size: 0.85rem; }
        .modal-body { padding: 0.6rem !important; }
        .modal-body .form-label { font-size: 0.68rem; margin-bottom: 1px; }
        .modal-body .form-control,
        .modal-body .form-select { font-size: 0.78rem; padding: 5px 8px; }
        .modal-body .mb-3 { margin-bottom: 0.45rem !important; }
        .modal-footer { padding: 0.5rem 0.6rem; gap: 6px; }
        /* Side-by-side footer buttons */
        .modal-footer {
            display: flex !important;
            flex-direction: row !important;
            justify-content: stretch;
        }
        .modal-footer .btn { flex: 1; text-align: center; font-size: 0.75rem; padding: 6px 8px; }

        /* Verification modal */
        .img-verify-preview { height: 120px; }

        /* Logs modal */
        #logsModal .table { font-size: 0.65rem; }
        #logsModal .table th, #logsModal .table td { padding: 0.28rem 0.25rem; }
        #logsModal .row.g-2 .col-6 { font-size: 0.65rem; }
    }
</style>

<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0 d-flex align-items-center gap-2" style="color: #1e293b;">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle">
                    <i class="bi bi-people" style="color:#14b8a6;font-size:1.9rem;"></i>
                </span>
                <?= t_users('page_title', $translations, $lang) ?>
            </h2>
            <p class="text-muted small mb-0"><?= t_users('page_subtitle', $translations, $lang) ?></p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success shadow-sm"
                onclick="openExportModal('csv', 'users', '?export=csv&<?= http_build_query($filters) ?>')">
                <i class="bi bi-download"></i> <?= t_users('btn_export_csv', $translations, $lang) ?>
            </button>
            <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="bi bi-person-plus"></i> <?= t_users('btn_create_user', $translations, $lang) ?>
            </button>
        </div>
    </div>
    
    <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-3" id="userFilterForm" onsubmit="return false;">
                <div class="col-md-5">
                    <div class="position-relative">
                        <input type="text" class="form-control" id="searchInput" name="search" autocomplete="off" placeholder="<?= t_users('filter_search_ph', $translations, $lang) ?>" value="<?= htmlspecialchars($filters['search']) ?>">
                        <span id="searchSpinner" class="spinner-border spinner-border-sm text-primary position-absolute top-50 end-0 translate-middle-y me-3" style="display:none;"></span>
                    </div>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="roleFilter" name="role">
                        <option value=""><?= t_users('filter_all_roles', $translations, $lang) ?></option>
                        <option value="applicant" <?= $filters['role'] === 'applicant' ? 'selected' : '' ?>><?= t_users('role_applicant', $translations, $lang) ?></option>
                        <option value="inspector" <?= $filters['role'] === 'inspector' ? 'selected' : '' ?>><?= t_users('role_inspector', $translations, $lang) ?></option>
                        <option value="zoning_officer" <?= $filters['role'] === 'zoning_officer' ? 'selected' : '' ?>><?= t_users('role_zoning_officer', $translations, $lang) ?></option>
                        <option value="building_official" <?= $filters['role'] === 'building_official' ? 'selected' : '' ?>><?= t_users('role_building_official', $translations, $lang) ?></option>
                        <option value="assessor" <?= $filters['role'] === 'assessor' ? 'selected' : '' ?>><?= t_users('role_assessor', $translations, $lang) ?></option>
                        <option value="admin" <?= $filters['role'] === 'admin' ? 'selected' : '' ?>><?= t_users('role_admin', $translations, $lang) ?></option>
                        <option value="super_admin" <?= $filters['role'] === 'super_admin' ? 'selected' : '' ?>><?= t_users('role_super_admin', $translations, $lang) ?></option>
                    </select>
                </div>
                <div class="col-md-5">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100"><?= t_users('btn_apply_filter', $translations, $lang) ?></button>
                        
                        <a href="users.php" class="btn btn-outline-secondary px-3 shadow-sm d-flex align-items-center justify-content-center" title="Reset Filters">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th><?= t_users('th_user_details', $translations, $lang) ?></th>
                                <th><?= t_users('th_role', $translations, $lang) ?></th>
                                <th><?= t_users('th_system_status', $translations, $lang) ?></th>
                                <th><?= t_users('th_id_verification', $translations, $lang) ?></th>
                                <th class="text-center"><?= t_users('th_actions', $translations, $lang) ?></th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
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
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="<?= $isOnline ? 'online-dot' : 'offline-dot' ?>" title="<?= $isOnline ? t_users('status_online', $translations, $lang) : t_users('status_offline', $translations, $lang) ?>"></div>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($user['email']) ?> | @<?= htmlspecialchars($user['username']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-secondary text-uppercase" style="font-size: 0.65rem;"><?= htmlspecialchars($user['role']) ?></span></td>
                                <td><span class="badge px-3 <?= $user['is_active'] ? 'status-active' : 'status-inactive' ?>"><?= $user['is_active'] ? t_users('status_active', $translations, $lang) : t_users('status_inactive', $translations, $lang) ?></span></td>
                                <td>
                                    <?php 
                                    $staffRoles = ['super_admin', 'admin', 'zoning_officer', 'building_official', 'assessor', 'inspector'];
                                    if (in_array(strtolower($user['role']), $staffRoles)): 
                                    ?>
                                        <span class="text-muted small"><?= t_users('label_staff_member', $translations, $lang) ?></span>
                                    <?php else: ?>
                                        <span class="small fw-bold cursor-pointer <?= $user['is_verified'] ? 'text-success' : 'text-warning' ?>" 
                                            onclick="openVerificationModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?>')">
                                            <i class="bi <?= $user['is_verified'] ? 'bi-check-circle-fill' : 'bi-clock-history' ?>"></i> 
                                            <?= $user['is_verified'] ? t_users('label_verified', $translations, $lang) : t_users('label_pending', $translations, $lang) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-dark" onclick="viewLogs(<?= $user['id'] ?>, '<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>')"><i class="bi bi-clock-history"></i></button>
                                    <button type="button" class="btn btn-sm btn-light border" onclick='editUser(<?= json_encode($user) ?>)'><i class="bi bi-pencil-square"></i> <?= t_users('btn_edit', $translations, $lang) ?></button>
                                    <?php if (!$user['is_active']): ?>
                                    <button type="button" class="btn btn-sm btn-outline-success border-0" onclick="quickAction(<?= $user['id'] ?>, 'activate')">
                                        <?= t_users('btn_activate', $translations, $lang) ?>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
            </div>
        </div>
        <div class="card-footer py-3 border-0">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <span class="info-text text-muted" id="paginationInfo">
                        <?= t_users('pagination_showing', $translations, $lang) ?> <strong><?= $totalUsers > 0 ? ($offset + 1) : 0 ?></strong> <?= t_users('pagination_to', $translations, $lang) ?>
                        <strong><?= min($offset + $limit, $totalUsers) ?></strong> <?= t_users('pagination_of', $translations, $lang) ?>
                        <strong><?= $totalUsers ?></strong> <?= t_users('pagination_users', $translations, $lang) ?>
                    </span>
                </div>
                <div class="col-md-6 text-md-end">
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm justify-content-center justify-content-md-end mb-0" id="paginationNav">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?p=1&<?= $query_string ?>" data-page="1"><i class="bi bi-chevron-double-left"></i></a>
                            </li>
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?p=<?= ($page - 1) ?>&<?= $query_string ?>" data-page="<?= ($page - 1) ?>">Prev</a>
                            </li>
                            <?php
                            $start = max(1, $page - 2);
                            $end = min($totalPages, $page + 2);
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?p=<?= $i ?>&<?= $query_string ?>" data-page="<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?p=<?= ($page + 1) ?>&<?= $query_string ?>" data-page="<?= ($page + 1) ?>">Next</a>
                            </li>
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?p=<?= $totalPages ?>&<?= $query_string ?>" data-page="<?= $totalPages ?>"><i class="bi bi-chevron-double-right"></i></a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
        </div>
</div>

<form id="quickActionForm" method="POST" style="display:none;">
    <input type="hidden" name="user_id" id="qa_user_id">
    <input type="hidden" name="action" id="qa_action">
</form>

<div class="modal fade" id="verificationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><?= t_users('modal_verify_title', $translations, $lang) ?>: <span id="v_name"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="verify_user">
                <input type="hidden" name="user_id" id="v_user_id">
                <div id="v_loading" class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2 text-muted"><?= t_users('modal_verify_loading', $translations, $lang) ?></p>
                </div>
                <div id="v_content" style="display:none;">
                    <div class="row g-3">
                        <div class="col-md-6 text-center">
                            <label class="small fw-bold d-block mb-1"><?= t_users('modal_id_front', $translations, $lang) ?></label>
                            <img src="" id="img_front" class="img-verify-preview" onclick="zoomImage(this.src)">
                        </div>
                        <div class="col-md-6 text-center">
                            <label class="small fw-bold d-block mb-1"><?= t_users('modal_id_back', $translations, $lang) ?></label>
                            <img src="" id="img_back" class="img-verify-preview" onclick="zoomImage(this.src)">
                        </div>
                    </div>
                    <div class="mt-4 p-3 bg-light rounded shadow-sm">
                        <label class="small fw-bold mb-2"><?= t_users('modal_verify_decision', $translations, $lang) ?></label>
                        <select name="status" id="v_decision" class="form-select shadow-sm" onchange="toggleRejectionBox(this.value)">
                            <option value="approve"><?= t_users('modal_approve', $translations, $lang) ?></option>
                            <option value="reject"><?= t_users('modal_reject', $translations, $lang) ?></option>
                        </select>
                        <div id="rejection_box" class="mt-3" style="display:none;">
                            <label class="small fw-bold text-danger"><?= t_users('modal_reject_reason', $translations, $lang) ?></label>
                            <select name="rejection_reason" id="v_rejection_reason" class="form-select mb-2" onchange="checkOtherReason(this.value)">
                                <option value="Blurry or Unreadable ID"><?= t_users('reject_blurry', $translations, $lang) ?></option>
                                <option value="Expired Identification Card"><?= t_users('reject_expired', $translations, $lang) ?></option>
                                <option value="ID Type not supported"><?= t_users('reject_unsupported', $translations, $lang) ?></option>
                                <option value="Name on ID does not match profile"><?= t_users('reject_name_mismatch', $translations, $lang) ?></option>
                                <option value="Missing back part of the ID"><?= t_users('reject_missing_back', $translations, $lang) ?></option>
                                <option value="Other"><?= t_users('reject_other', $translations, $lang) ?></option>
                            </select>
                            <textarea name="custom_reason" id="v_custom_reason" class="form-control" placeholder="Type specific reason here..." style="display:none;"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0" id="v_footer" style="display:none;">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= t_users('btn_close', $translations, $lang) ?></button>
                <button type="submit" class="btn btn-primary px-4"><?= t_users('btn_save_decision', $translations, $lang) ?></button>
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

<style>
    #createUserModal .modal-content, #editUserModal .modal-content { border-radius: 18px; overflow: hidden; }
    #createUserModal .modal-header, #editUserModal .modal-header { padding: 1.75rem 2rem 1.5rem; }
    #createUserModal .modal-header h5, #editUserModal .modal-header h5 { font-size: 1.35rem; letter-spacing: 0.2px; }
    #createUserModal .modal-header .header-subtitle, #editUserModal .modal-header .header-subtitle { font-size: 0.8rem; opacity: 0.85; font-weight: 400; margin-top: 2px; }
    #createUserModal .modal-body, #editUserModal .modal-body { padding: 2rem; background: #fbfbfd; }
    #createUserModal .form-section-label, #editUserModal .form-section-label {
        font-size: 0.7rem; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase;
        color: #8891a5; margin-bottom: 0.85rem; display: flex; align-items: center; gap: 8px;
    }
    #createUserModal .form-section-label::after, #editUserModal .form-section-label::after { content: ""; flex: 1; height: 1px; background: #e5e7ee; }
    #createUserModal .form-section + .form-section, #editUserModal .form-section + .form-section { margin-top: 1.6rem; }
    #createUserModal label.field-label, #editUserModal label.field-label { font-size: 0.78rem; font-weight: 600; color: #3a3f4b; margin-bottom: 0.35rem; display: block; }
    #createUserModal .input-icon-group, #editUserModal .input-icon-group { position: relative; }
    #createUserModal .input-icon-group > i.field-icon, #editUserModal .input-icon-group > i.field-icon {
        position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
        color: #9aa1b3; font-size: 0.9rem; pointer-events: none;
    }
    #createUserModal .input-icon-group .form-control,
    #createUserModal .input-icon-group .form-select,
    #editUserModal .input-icon-group .form-control,
    #editUserModal .input-icon-group .form-select { padding-left: 40px; }
    #createUserModal .form-control, #createUserModal .form-select,
    #editUserModal .form-control, #editUserModal .form-select {
        border-radius: 10px; border: 1px solid #e2e4ea; padding: 0.6rem 0.85rem; font-size: 0.9rem;
        background: #fff; transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    #createUserModal .form-control:focus, #createUserModal .form-select:focus,
    #editUserModal .form-control:focus, #editUserModal .form-select:focus {
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.12); border-color: #86b7fe;
    }
    #createUserModal .input-group .input-group-text, #editUserModal .input-group .input-group-text {
        border-radius: 0 10px 10px 0; border: 1px solid #e2e4ea; border-left: none; background: #fff;
    }
    #createUserModal .input-group .form-control, #editUserModal .input-group .form-control { border-radius: 10px 0 0 10px; }
    #createUserModal .modal-footer, #editUserModal .modal-footer { padding: 1.25rem 2rem 1.75rem; background: #fbfbfd; gap: 0.6rem; }
    #createUserModal .btn-create-submit, #editUserModal .btn-edit-submit {
        border-radius: 10px; padding: 0.7rem 1rem; font-weight: 600; font-size: 0.92rem;
        letter-spacing: 0.2px;
    }
    #editUserModal .btn-edit-cancel {
        border-radius: 10px; padding: 0.7rem 1.1rem; font-weight: 600; font-size: 0.92rem;
        letter-spacing: 0.2px; border: 1px solid #e2e4ea; color: #5a6072; background: #fff;
    }
    #editUserModal .btn-edit-cancel:hover { background: #f4f5f8; }

    /* ---- Edit User modal: compact sizing so it fits the viewport without scrolling ---- */
    #editUserModal .modal-dialog { max-width: 640px; }
    #editUserModal .modal-header { padding: 1.1rem 1.75rem 0.9rem; }
    #editUserModal .modal-header h5 { font-size: 1.15rem; }
    #editUserModal .modal-header .header-subtitle { font-size: 0.75rem; }
    #editUserModal .modal-body { padding: 1.25rem 1.75rem; background: #fbfbfd; }
    #editUserModal .form-section-label { font-size: 0.66rem; margin-bottom: 0.6rem; }
    #editUserModal .form-section + .form-section { margin-top: 1rem; }
    #editUserModal label.field-label { font-size: 0.74rem; margin-bottom: 0.25rem; }
    #editUserModal .form-control, #editUserModal .form-select { padding: 0.5rem 0.75rem; font-size: 0.87rem; }
    #editUserModal .input-icon-group .form-control, #editUserModal .input-icon-group .form-select { padding-left: 38px; }
    #editUserModal .modal-footer { padding: 0.9rem 1.75rem 1.25rem; }
    #editUserModal .row.g-3 { --bs-gutter-y: 0.75rem; }
</style>

<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white position-relative justify-content-center">
                <div class="text-center">
                    <h5 class="mb-0"><i class="bi bi-person-plus-fill me-2"></i><?= t_users('modal_create_title', $translations, $lang) ?></h5>
                    <div class="header-subtitle">Add a new staff or applicant account</div>
                </div>
                <button type="button" class="btn-close btn-close-white position-absolute end-0 me-3" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="create">

                <div class="form-section">
                    <div class="form-section-label"><i class="bi bi-person-vcard"></i> Personal Information</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="field-label"><?= t_users('label_first_name', $translations, $lang) ?></label>
                            <div class="input-icon-group">
                                <i class="bi bi-person field-icon"></i>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label"><?= t_users('label_last_name', $translations, $lang) ?></label>
                            <div class="input-icon-group">
                                <i class="bi bi-person field-icon"></i>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-label"><i class="bi bi-shield-lock"></i> Account &amp; Access</div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="field-label"><?= t_users('label_username', $translations, $lang) ?></label>
                            <div class="input-icon-group">
                                <i class="bi bi-at field-icon"></i>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="field-label"><?= t_users('label_email', $translations, $lang) ?></label>
                            <div class="input-icon-group">
                                <i class="bi bi-envelope field-icon"></i>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label"><?= t_users('label_password', $translations, $lang) ?></label>
                            <div class="input-group">
                                <input type="password" name="password" id="create_p" class="form-control" onkeyup="checkStrength(this.value, 's_create')" required>
                                <span class="input-group-text" style="cursor:pointer;" onclick="togglePasswordVisibility('create_p', 'create_eye')"><i class="bi bi-eye-slash" id="create_eye"></i></span>
                            </div>
                            <div class="strength-meter"><div id="s_create" class="strength-bar"></div></div>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label"><?= t_users('label_role', $translations, $lang) ?></label>
                            <select name="role" class="form-select">
                                <option value="applicant"><?= t_users('role_applicant', $translations, $lang) ?></option>
                                <option value="inspector"><?= t_users('role_inspector', $translations, $lang) ?></option>
                                <option value="zoning_officer"><?= t_users('role_zoning_officer', $translations, $lang) ?></option>
                                <option value="building_official"><?= t_users('role_building_official', $translations, $lang) ?></option>
                                <option value="assessor"><?= t_users('role_assessor', $translations, $lang) ?></option>
                                <option value="admin"><?= t_users('role_admin', $translations, $lang) ?></option>
                                <option value="super_admin"><?= t_users('role_super_admin', $translations, $lang) ?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="submit" class="btn btn-primary w-100 shadow-sm btn-create-submit">
                    <i class="bi bi-check-circle me-1"></i> <?= t_users('btn_create_account', $translations, $lang) ?>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white position-relative justify-content-center">
                <div class="text-center">
                    <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i><?= t_users('modal_edit_title', $translations, $lang) ?></h5>
                    <div class="header-subtitle">Update account details and permissions</div>
                </div>
                <button type="button" class="btn-close btn-close-white position-absolute end-0 me-3" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="update"><input type="hidden" name="user_id" id="e_id">

                <div class="form-section">
                    <div class="form-section-label"><i class="bi bi-person-vcard"></i> Personal Information</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="field-label"><?= t_users('label_first_name', $translations, $lang) ?></label>
                            <div class="input-icon-group">
                                <i class="bi bi-person field-icon"></i>
                                <input type="text" name="first_name" id="e_fname" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label"><?= t_users('label_last_name', $translations, $lang) ?></label>
                            <div class="input-icon-group">
                                <i class="bi bi-person field-icon"></i>
                                <input type="text" name="last_name" id="e_lname" class="form-control" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-label"><i class="bi bi-shield-lock"></i> Account &amp; Access</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="field-label"><?= t_users('label_username', $translations, $lang) ?></label>
                            <div class="input-icon-group">
                                <i class="bi bi-at field-icon"></i>
                                <input type="text" name="username" id="e_username" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label"><?= t_users('label_email', $translations, $lang) ?></label>
                            <div class="input-icon-group">
                                <i class="bi bi-envelope field-icon"></i>
                                <input type="email" name="email" id="e_email" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label"><?= t_users('label_phone', $translations, $lang) ?></label>
                            <div class="input-icon-group">
                                <i class="bi bi-telephone field-icon"></i>
                                <input type="text" name="phone" id="e_phone" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label"><?= t_users('label_role', $translations, $lang) ?></label>
                            <select name="role" id="e_role" class="form-select">
                                <option value="applicant"><?= t_users('role_applicant', $translations, $lang) ?></option>
                                <option value="inspector"><?= t_users('role_inspector', $translations, $lang) ?></option>
                                <option value="zoning_officer"><?= t_users('role_zoning_officer', $translations, $lang) ?></option>
                                <option value="building_official"><?= t_users('role_building_official', $translations, $lang) ?></option>
                                <option value="assessor"><?= t_users('role_assessor', $translations, $lang) ?></option>
                                <option value="admin"><?= t_users('role_admin', $translations, $lang) ?></option>
                                <option value="super_admin"><?= t_users('role_super_admin', $translations, $lang) ?></option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="field-label"><?= t_users('label_new_password', $translations, $lang) ?></label>
                            <div class="input-group">
                                <input type="password" name="password" id="e_p" class="form-control" onkeyup="checkStrength(this.value, 's_edit')">
                                <span class="input-group-text" style="cursor:pointer;" onclick="togglePasswordVisibility('e_p', 'edit_eye')"><i class="bi bi-eye-slash" id="edit_eye"></i></span>
                            </div>
                            <div class="strength-meter"><div id="s_edit" class="strength-bar"></div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-edit-cancel" data-bs-dismiss="modal"><?= t_users('btn_cancel', $translations, $lang) ?></button>
                <button type="submit" class="btn btn-primary flex-fill shadow-sm btn-edit-submit">
                    <i class="bi bi-check-circle me-1"></i> <?= t_users('btn_update_user', $translations, $lang) ?>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="logsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title"><?= t_users('modal_logs_title', $translations, $lang) ?>: <span id="log_user_name"></span></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="logs_content"></div>
        </div>
    </div>
</div>

<!-- ===== TOAST NOTIFICATION CONTAINER ===== -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
    <div id="exportToast" class="toast align-items-center border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2" id="exportToastBody">
                <i class="bi" id="exportToastIcon" style="font-size:1.1rem;flex-shrink:0;"></i>
                <span id="exportToastMsg"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- ===== SECURE EXPORT VERIFICATION MODAL ===== -->
<div class="modal fade" id="exportVerifyModal" tabindex="-1" aria-labelledby="exportVerifyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="exportVerifyModalLabel">
                    <i class="bi bi-shield-lock-fill me-2"></i><?= t_users('export_modal_title', $translations, $lang) ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:0.5rem 0.75rem;" class="d-flex align-items-center gap-2 small mb-4">
                    <i class="bi bi-exclamation-triangle-fill fs-5 text-warning flex-shrink-0"></i>
                    <span><?= t_users('export_warning', $translations, $lang) ?></span>
                </div>

                <div id="exportVerifyAlert" class="alert small py-2 mb-3" style="display:none;"></div>

                <div class="mb-3">
                    <label class="form-label small fw-bold"><?= t_users('export_purpose_label', $translations, $lang) ?> <span class="text-danger">*</span></label>
                    <select id="exportReason" class="form-select">
                        <option value=""><?= t_users('export_purpose_ph', $translations, $lang) ?></option>
                        <option value="Reporting">Reporting</option>
                        <option value="Auditing">Auditing</option>
                        <option value="Archiving">Archiving</option>
                        <option value="Compliance Review">Compliance Review</option>
                        <option value="Data Backup">Data Backup</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="mb-1">
                    <label class="form-label small fw-bold"><?= t_users('export_password_label', $translations, $lang) ?> <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" id="exportPassword" class="form-control"
                               placeholder="<?= t_users('export_password_ph', $translations, $lang) ?>">
                        <span class="input-group-text bg-white" style="cursor:pointer;"
                              onclick="togglePasswordVisibility('exportPassword', 'exportEyeIcon')">
                            <i class="bi bi-eye-slash" id="exportEyeIcon"></i>
                        </span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal"><?= t_users('btn_cancel', $translations, $lang) ?></button>
                <button type="button" class="btn btn-primary px-4" id="exportVerifyBtn">
                    <span id="exportBtnSpinner" class="spinner-border spinner-border-sm me-1 d-none"></span>
                    <i class="bi bi-download me-1" id="exportBtnIcon"></i> <?= t_users('btn_verify_download', $translations, $lang) ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const CURRENT_FILE = window.location.pathname.split('/').pop() || 'users.php';

// ===== LIVE SEARCH LOGIC =====
(function () {
    const searchInput   = document.getElementById('searchInput');
    const roleFilter     = document.getElementById('roleFilter');
    const searchSpinner  = document.getElementById('searchSpinner');
    const tbody          = document.getElementById('usersTableBody');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationNav  = document.getElementById('paginationNav');
    const filterForm     = document.getElementById('userFilterForm');

    let debounceTimer = null;
    let currentPage   = 1;
    let requestSeq    = 0; // guards against out-of-order responses

    function esc(str) {
        return String(str ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    function buildUrl(page) {
        const params = new URLSearchParams();
        params.set('action', 'search_users');
        params.set('search', searchInput.value.trim());
        params.set('role', roleFilter.value);
        params.set('p', page);
        return `${CURRENT_FILE}?${params.toString()}`;
    }

    function rowHtml(u, labels) {
        const statusClass = u.is_active ? 'status-active' : 'status-inactive';
        const statusLabel = u.is_active ? labels.active : labels.inactive;
        const dotClass = u.is_online ? 'online-dot' : 'offline-dot';
        const dotTitle = u.is_online ? labels.online : labels.offline;

        let idCell;
        if (u.is_staff) {
            idCell = `<span class="text-muted small">${esc(labels.staff)}</span>`;
        } else {
            const vClass = u.is_verified ? 'text-success' : 'text-warning';
            const vIcon  = u.is_verified ? 'bi-check-circle-fill' : 'bi-clock-history';
            const vText  = u.is_verified ? labels.verified : labels.pending;
            idCell = `<span class="small fw-bold cursor-pointer ${vClass}" onclick="openVerificationModal(${u.id}, '${esc(u.first_name + ' ' + u.last_name)}')">
                        <i class="bi ${vIcon}"></i> ${esc(vText)}
                      </span>`;
        }

        const activateBtn = !u.is_active
            ? `<button type="button" class="btn btn-sm btn-outline-success border-0" onclick="quickAction(${u.id}, 'activate')">${esc(labels.activate)}</button>`
            : '';

        // Build a plain object (mirrors PHP $user array) for the editUser() JS function
        const userObj = {
            id: u.id, first_name: u.first_name, last_name: u.last_name,
            username: u.username, email: u.email, role: u.role, phone: u.phone
        };

        return `<tr>
            <td>
                <div class="d-flex align-items-center">
                    <div class="${dotClass}" title="${esc(dotTitle)}"></div>
                    <div>
                        <div class="fw-bold">${esc(u.first_name + ' ' + u.last_name)}</div>
                        <div class="text-muted small">${esc(u.email)} | @${esc(u.username)}</div>
                    </div>
                </div>
            </td>
            <td><span class="badge bg-secondary text-uppercase" style="font-size: 0.65rem;">${esc(u.role)}</span></td>
            <td><span class="badge px-3 ${statusClass}">${esc(statusLabel)}</span></td>
            <td>${idCell}</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-dark" onclick="viewLogs(${u.id}, '${esc(u.first_name + ' ' + u.last_name)}')"><i class="bi bi-clock-history"></i></button>
                <button type="button" class="btn btn-sm btn-light border" onclick='editUser(${JSON.stringify(userObj)})'><i class="bi bi-pencil-square"></i> ${esc(labels.edit)}</button>
                ${activateBtn}
            </td>
        </tr>`;
    }

    function renderPagination(data) {
        const { page, totalPages } = data;
        const items = [];
        const item = (label, targetPage, disabled, active) => `
            <li class="page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${targetPage}">${label}</a>
            </li>`;

        items.push(item('<i class="bi bi-chevron-double-left"></i>', 1, page <= 1, false));
        items.push(item('Prev', page - 1, page <= 1, false));
        const start = Math.max(1, page - 2);
        const end = Math.min(totalPages, page + 2);
        for (let i = start; i <= end; i++) {
            items.push(item(i, i, false, page === i));
        }
        items.push(item('Next', page + 1, page >= totalPages, false));
        items.push(item('<i class="bi bi-chevron-double-right"></i>', totalPages, page >= totalPages, false));

        paginationNav.innerHTML = items.join('');
        paginationNav.querySelectorAll('a.page-link').forEach(a => {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                const p = parseInt(this.dataset.page, 10);
                if (!isNaN(p) && p >= 1) doSearch(p);
            });
        });
    }

    function renderInfo(data) {
        const { totalUsers, offset, limit, labels } = data;
        const from = totalUsers > 0 ? offset + 1 : 0;
        const to = Math.min(offset + limit, totalUsers);
        paginationInfo.innerHTML = `${esc(labels.showing)} <strong>${from}</strong> ${esc(labels.to)}
            <strong>${to}</strong> ${esc(labels.of)}
            <strong>${totalUsers}</strong> ${esc(labels.users)}`;
    }

    function doSearch(page) {
        currentPage = page || 1;
        const seq = ++requestSeq;
        searchSpinner.style.display = 'inline-block';

        fetch(buildUrl(currentPage))
            .then(res => res.json())
            .then(data => {
                if (seq !== requestSeq) return; // stale response, ignore
                if (!data.success) return;

                if (!data.users.length) {
                    tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">No matching users found.</td></tr>`;
                } else {
                    tbody.innerHTML = data.users.map(u => rowHtml(u, data.labels)).join('');
                }
                renderInfo(data);
                renderPagination(data);

                // Reflect state in the URL for shareable/bookmarkable links, without reloading
                const qp = new URLSearchParams();
                if (searchInput.value.trim()) qp.set('search', searchInput.value.trim());
                if (roleFilter.value) qp.set('role', roleFilter.value);
                if (currentPage > 1) qp.set('p', currentPage);
                const newUrl = window.location.pathname + (qp.toString() ? '?' + qp.toString() : '');
                history.replaceState(null, '', newUrl);
            })
            .catch(() => { /* silently ignore network hiccups */ })
            .finally(() => { searchSpinner.style.display = 'none'; });
    }

    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => doSearch(1), 350);
    });

    roleFilter.addEventListener('change', () => doSearch(1));

    // "Apply Filter" button still works, just without a full page reload
    filterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        clearTimeout(debounceTimer);
        doSearch(1);
    });
})();

// ===== EXPORT VERIFICATION LOGIC =====
const _exportModalEl = document.getElementById('exportVerifyModal');
let _exportType = '', _exportTable = '', _exportUrl = '';

/* ---- helpers ---- */
function _elmt(id) { return document.getElementById(id); }

function _resetExportModal() {
    _elmt('exportPassword').value   = '';
    _elmt('exportPassword').type    = 'password';
    _elmt('exportReason').value     = '';
    _elmt('exportEyeIcon').className = 'bi bi-eye-slash';
    _elmt('exportVerifyBtn').disabled = false;
    _elmt('exportBtnSpinner').classList.add('d-none');
    _elmt('exportBtnIcon').classList.remove('d-none');
    _hideAlert();
}

function _getAlertEl() {
    return document.getElementById('exportVerifyAlert');
}

function _hideAlert() {
    var el = _getAlertEl();
    if (!el) return;
    el.style.display = 'none';
    el.className = 'alert small py-2 mb-3';
    el.innerText = '';
}

function _showAlert(msg, type) {
    var el = _getAlertEl();
    if (!el) return;

    // FORCE RESET (important fix)
    el.style.display = 'none';
    el.innerHTML = '';

    // Force reflow (trick para mag refresh DOM)
    void el.offsetHeight;

    // Apply new content
    el.className = 'alert alert-' + type + ' small py-2 mb-3';
    el.innerText = msg;
    el.style.display = 'block';
}

function _setBtnLoading(on) {
    _elmt('exportVerifyBtn').disabled = on;
    _elmt('exportBtnSpinner').classList.toggle('d-none', !on);
    _elmt('exportBtnIcon').classList.toggle('d-none', on);
}

/* ---- toast notification ---- */
function _showToast(msg, type) {
    var toastEl   = _elmt('exportToast');
    var toastMsg  = _elmt('exportToastMsg');
    var toastIcon = _elmt('exportToastIcon');

    // Map type → Bootstrap bg class + icon
    var config = {
        warning: { bg: 'bg-warning',  text: 'text-dark',  icon: 'bi-exclamation-triangle-fill' },
        danger:  { bg: 'bg-danger',   text: 'text-white', icon: 'bi-x-circle-fill'              },
        success: { bg: 'bg-success',  text: 'text-white', icon: 'bi-check-circle-fill'          },
        info:    { bg: 'bg-info',     text: 'text-dark',  icon: 'bi-info-circle-fill'           }
    };
    var c = config[type] || config['info'];

    // Reset classes, then apply
    toastEl.className = 'toast align-items-center border-0 shadow ' + c.bg + ' ' + c.text;
    toastIcon.className = 'bi ' + c.icon;
    toastMsg.innerText = msg;

    var bsToast = bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 3500 });
    bsToast.show();
}

/* ---- open modal ---- */
function openExportModal(type, table, downloadUrl) {
    _exportType  = type.toUpperCase();
    _exportTable = table;
    _exportUrl   = new URL(downloadUrl, window.location.href).href;

    _resetExportModal();
    _hideAlert();        

    bootstrap.Modal.getOrCreateInstance(_exportModalEl).show();
}

/* ---- close modal on hide ---- */
_exportModalEl.addEventListener('hide.bs.modal', function () {
    var focused = _exportModalEl.querySelector(':focus');
    if (focused) focused.blur();
});

_exportModalEl.addEventListener('hidden.bs.modal', function () {
    _hideAlert();
});

/* ---- main submit ---- */
function submitExportVerification() {
    var password = _elmt('exportPassword').value.trim();
    var reason   = _elmt('exportReason').value;

    // Validate purpose
    if (!reason) {
        _showToast('Please select a purpose for this export.', 'warning');
        return;
    }

    // Validate password field not empty
    if (!password) {
        _showToast('Please enter your password to continue.', 'warning');
        return;
    }

    _setBtnLoading(true);
    _hideAlert();

    var fd = new FormData();
    fd.append('password',    password);
    fd.append('reason',      reason);
    fd.append('export_type', _exportType);
    fd.append('table_name',  _exportTable);

    fetch('verify_action.php', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function(res) {
            if (!res.ok) throw new Error('Server error: ' + res.status);
            return res.json();
        })
        .then(function(data) {
            if (!data.success) {
                // Wrong password or other server rejection
                _setBtnLoading(false);
                _showAlert(data.message || 'Incorrect password. Export denied.', 'danger');
                return;
            }

            // Password verified — trigger download directly via iframe
            _showAlert('Verification successful. Starting download...', 'success');
            var sep         = _exportUrl.includes('?') ? '&' : '?';
            var downloadUrl = _exportUrl + sep + 'export_token=' + encodeURIComponent(data.token);

            var iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = downloadUrl;
            document.body.appendChild(iframe);

            setTimeout(function() {
                document.body.removeChild(iframe);
                _setBtnLoading(false);
                bootstrap.Modal.getOrCreateInstance(_exportModalEl).hide();
            }, 3000);
        })
        .catch(function() {
            _setBtnLoading(false);
            _showAlert('Network error. Please try again.', 'danger');
        });
}

_elmt('exportVerifyBtn').onclick = submitExportVerification;
// ===== END EXPORT VERIFICATION LOGIC =====

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
    bootstrap.Modal.getOrCreateInstance(document.getElementById('imageZoomModal')).show();
}

function openVerificationModal(userId, name) {
    document.getElementById('v_user_id').value = userId;
    document.getElementById('v_name').innerText = name;
    document.getElementById('v_loading').style.display = 'block';
    document.getElementById('v_content').style.display = 'none';
    document.getElementById('v_footer').style.display = 'none';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('verificationModal')).show();

    fetch(`${CURRENT_FILE}?action=get_verification&user_id=${userId}`)
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                const placeholder = 'https://placehold.co/400x300?text=No+Image'; 
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
}

function editUser(u) {
    document.getElementById('e_id').value = u.id;
    document.getElementById('e_fname').value = u.first_name;
    document.getElementById('e_lname').value = u.last_name;
    document.getElementById('e_username').value = u.username;
    document.getElementById('e_email').value = u.email;
    document.getElementById('e_phone').value = u.phone || '';
    document.getElementById('e_role').value = u.role;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('editUserModal')).show();
}

function _statusBadgeClass(status) {
    const s = String(status || '').toLowerCase();
    if (s === 'approved') return 'bg-success text-white';
    if (s === 'submitted') return 'bg-primary text-white';
    if (s === 'rejected') return 'bg-danger text-white';
    return 'bg-light text-dark border';
}

function viewLogs(userId, userName) {
    document.getElementById('log_user_name').innerText = userName;
    const content = document.getElementById('logs_content');
    content.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>`;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('logsModal')).show();
    
    fetch(`${CURRENT_FILE}?action=get_history&user_id=${userId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                let appRows = (data.applications || []).map(app => 
                    `<tr><td><b>${app.application_number}</b></td><td>${app.project_name}</td><td><span class="badge ${_statusBadgeClass(app.status)}">${app.status}</span></td><td>${app.created_at}</td></tr>`
                ).join('') || '<tr><td colspan="4" class="text-center">No applications.</td></tr>';
                
                content.innerHTML = `
                    <div class="row g-2 mb-3">
                        <div class="col-6"><div class="p-2 border rounded small">LOGIN: <b>${data.last_login}</b></div></div>
                        <div class="col-6"><div class="p-2 border rounded small">APPS: <b>${data.app_count}</b></div></div>
                    </div>
                    <div class="table-responsive"><table class="table table-sm small border">
                        <thead><tr><th>ID</th><th>Project</th><th>Status</th><th>Date</th></tr></thead>
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
    if (bar) {
        bar.style.width = s + "%";
        bar.style.backgroundColor = s <= 50 ? "#dc3545" : (s <= 75 ? "#ffc107" : "#198754");
    }
}
</script>

<?php include __DIR__ . '/footer.php'; ?>
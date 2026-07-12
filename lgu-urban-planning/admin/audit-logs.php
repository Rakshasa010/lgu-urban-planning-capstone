<?php

// Audit Logs

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helper.php';
require_once __DIR__ . '/../modules/UserAccessManagement/UserController.php';

$auth = new Auth();
$auth->requirePermission('view_audit_logs');
$auth->requireRole(['admin', 'super_admin']);

$userController = new UserController();
$db = Database::getInstance();

// ── Load language from session (written by settings.php on every save) ────────
$lang = $_SESSION['locale_language'] ?? 'en_PH';

// ── Translation strings for audit-logs.php ───────────────────────────────────
$translations = [
    'en_PH' => [
        // Page
        'page_title'            => 'Audit Logs | LGU Urban Planning',
        'page_heading'          => 'Audit Logs',
        'page_subtitle'         => 'Official activity logs for transparency and administrative monitoring.',
        // Header buttons
        'btn_purge_settings'    => 'PURGE SETTINGS',
        'btn_generate_report'   => 'GENERATE EXCEL REPORT',
        // Filter labels & buttons
        'filter_action_label'   => 'Action Type',
        'filter_action_ph'      => 'Search action...',
        'filter_date_from'      => 'Date From',
        'filter_date_to'        => 'Date To',
        'btn_apply_filters'     => 'APPLY FILTERS',
        'btn_reset'             => 'RESET',
        // Table headers
        'th_severity'           => 'Severity',
        'th_timestamp'          => 'Timestamp',
        'th_user'               => 'User',
        'th_action'             => 'Action',
        'th_ip'                 => 'IP Address',
        'th_ref_id'             => 'Reference ID',
        // Table body
        'no_records'            => 'No audit records found matching your filters.',
        // Severity badges
        'severity_critical'     => 'CRITICAL',
        'severity_warning'      => 'WARNING',
        'severity_info'         => 'INFO',
        // Pagination
        'pg_showing'            => 'Showing',
        'pg_to'                 => 'to',
        'pg_of'                 => 'of',
        'pg_entries'            => 'entries',
        'pg_prev'               => 'Prev',
        'pg_next'               => 'Next',
        // Log detail modal
        'modal_log_performed'   => 'Performed By',
        'modal_log_ip'          => 'IP Address',
        'modal_log_timestamp'   => 'Timestamp',
        'modal_log_device'      => 'Device / Browser Info',
        'modal_log_changes'     => 'Changes Made',
        'modal_log_no_changes'  => 'No specific data changes recorded.',
        'btn_close'             => 'Close',
        // Export modal
        'export_modal_title'    => 'Secure Export Verification',
        'export_warning'        => 'You are about to export official audit records. Please confirm your identity to proceed.',
        'export_purpose_label'  => 'Purpose of Export',
        'export_purpose_ph'     => '— Select a reason —',
        'export_password_label' => 'Admin Password',
        'export_password_ph'    => 'Re-enter your account password',
        'btn_cancel'            => 'Cancel',
        'btn_verify_download'   => 'Verify & Download',
        // Purge modal
        'purge_modal_title'     => 'Secure Purge Verification',
        'purge_warning'         => 'You are about to permanently delete audit logs. This action cannot be undone. Please confirm your identity to proceed.',
        'purge_older_than'      => 'Delete Logs Older Than',
        'purge_1yr'             => '1 Year',
        'purge_2yr'             => '2 Years',
        'purge_3yr'             => '3 Years',
        'purge_5yr'             => '5 Years',
        'btn_verify_purge'      => 'Verify & Purge',
        // Purge success message
        'purge_success'         => 'Successfully purged %d logs older than %d year(s).',
        // JS toast / alert strings (passed to JS via json_encode)
        'js_export_select_reason'   => 'Please select a purpose for this export.',
        'js_export_enter_password'  => 'Please enter your password to continue.',
        'js_export_success'         => 'Verification successful. Starting download...',
        'js_export_network_error'   => 'Network error. Please try again.',
        'js_purge_enter_password'   => 'Please enter your password to confirm the purge.',
        'js_purge_success'          => 'Identity confirmed. Executing purge...',
        'js_network_error'          => 'Network error. Please try again.',
    ],
    'fil' => [
        // Page
        'page_title'            => 'Mga Audit Log | LGU Urban Planning',
        'page_heading'          => 'Mga Audit Log',
        'page_subtitle'         => 'Mga opisyal na log ng aktibidad para sa transparency at administratibong pagmamatyag.',
        // Header buttons
        'btn_purge_settings'    => 'MGA SETTING NG PURGE',
        'btn_generate_report'   => 'GUMAWA NG ULAT SA EXCEL',
        // Filter labels & buttons
        'filter_action_label'   => 'Uri ng Aksyon',
        'filter_action_ph'      => 'Maghanap ng aksyon...',
        'filter_date_from'      => 'Petsa Mula',
        'filter_date_to'        => 'Petsa Hanggang',
        'btn_apply_filters'     => 'ILAPAT ANG MGA FILTER',
        'btn_reset'             => 'I-RESET',
        // Table headers
        'th_severity'           => 'Antas',
        'th_timestamp'          => 'Timestamp',
        'th_user'               => 'Gumagamit',
        'th_action'             => 'Aksyon',
        'th_ip'                 => 'IP Address',
        'th_ref_id'             => 'Reference ID',
        // Table body
        'no_records'            => 'Walang mga rekord ng audit na natuklasan na akma sa iyong mga filter.',
        // Severity badges
        'severity_critical'     => 'KRITIKAL',
        'severity_warning'      => 'BABALA',
        'severity_info'         => 'IMPORMASYON',
        // Pagination
        'pg_showing'            => 'Ipinapakita',
        'pg_to'                 => 'hanggang',
        'pg_of'                 => 'sa',
        'pg_entries'            => 'mga entry',
        'pg_prev'               => 'Nakaraan',
        'pg_next'               => 'Susunod',
        // Log detail modal
        'modal_log_performed'   => 'Ginawa Ni',
        'modal_log_ip'          => 'IP Address',
        'modal_log_timestamp'   => 'Timestamp',
        'modal_log_device'      => 'Device / Browser Info',
        'modal_log_changes'     => 'Mga Pagbabagong Ginawa',
        'modal_log_no_changes'  => 'Walang partikular na pagbabago ng datos na naitala.',
        'btn_close'             => 'Isara',
        // Export modal
        'export_modal_title'    => 'Secure na Pag-verify ng Export',
        'export_warning'        => 'Mag-e-export ka ng mga opisyal na audit record. Mangyaring kumpirmahin ang iyong pagkakakilanlan upang magpatuloy.',
        'export_purpose_label'  => 'Layunin ng Export',
        'export_purpose_ph'     => '— Pumili ng dahilan —',
        'export_password_label' => 'Password ng Admin',
        'export_password_ph'    => 'Muling ilagay ang iyong password',
        'btn_cancel'            => 'Kanselahin',
        'btn_verify_download'   => 'I-verify at I-download',
        // Purge modal
        'purge_modal_title'     => 'Secure na Pag-verify ng Purge',
        'purge_warning'         => 'Permanenteng mabubura ang mga audit log. Hindi na ito mababawi. Mangyaring kumpirmahin ang iyong pagkakakilanlan upang magpatuloy.',
        'purge_older_than'      => 'Burahin ang mga Log na Mas Matanda Sa',
        'purge_1yr'             => '1 Taon',
        'purge_2yr'             => '2 Taon',
        'purge_3yr'             => '3 Taon',
        'purge_5yr'             => '5 Taon',
        'btn_verify_purge'      => 'I-verify at I-purge',
        // Purge success message
        'purge_success'         => 'Matagumpay na nabura ang %d na log na mas matanda sa %d taon.',
        // JS toast / alert strings
        'js_export_select_reason'   => 'Mangyaring pumili ng layunin para sa export na ito.',
        'js_export_enter_password'  => 'Mangyaring ilagay ang iyong password upang magpatuloy.',
        'js_export_success'         => 'Matagumpay na na-verify. Nagsisimula na ang pag-download...',
        'js_export_network_error'   => 'Error sa network. Mangyaring subukan ulit.',
        'js_purge_enter_password'   => 'Mangyaring ilagay ang iyong password upang kumpirmahin ang purge.',
        'js_purge_success'          => 'Nakumpirma ang pagkakakilanlan. Isinasagawa ang purge...',
        'js_network_error'          => 'Error sa network. Mangyaring subukan ulit.',
    ],
];

// Helper: get translated string, fallback to English
function t_audit(string $key, array $translations, string $lang): string {
    return $translations[$lang][$key] ?? $translations['en_PH'][$key] ?? $key;
}

// --- AUTO-PURGE LOGIC ---
$purgeMessage = "";
if (isset($_POST['purge_logs'])) {
    $years = (int)$_POST['purge_years'];
    if ($years >= 1) {
        $pdo = $db->getConnection(); 
        $stmt = $pdo->prepare("DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? YEAR)");
        
        if ($stmt->execute([$years])) {
            $deletedCount = $stmt->rowCount();
            $purgeMsg = sprintf(t_audit('purge_success', $translations, $lang), $deletedCount, $years);
            $purgeMessage = "<div class='alert alert-success alert-dismissible fade show small mx-4 mt-3' role='alert'>
                                <i class='bi bi-check-circle-fill me-2'></i>" . htmlspecialchars($purgeMsg) . "
                                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                             </div>";
        }
    }
}

// Helper Function

function getSeverityTag($action, $translations, $lang) {
    $action = strtolower($action);
    
    // CRITICAL: Deletion or System Changes
    if (strpos($action, 'delete') !== false || strpos($action, 'remove') !== false || strpos($action, 'config') !== false || strpos($action, 'setting') !== false) {
        $label = $translations[$lang]['severity_critical'] ?? $translations['en_PH']['severity_critical'];
        return '<span class="badge bg-danger text-white border-0 shadow-sm px-2 py-1"><i class="bi bi-exclamation-octagon me-1"></i>' . $label . '</span>';
    }
    
    // WARNING: Profile updates or Password changes
    if (strpos($action, 'update') !== false || strpos($action, 'edit') !== false || strpos($action, 'password') !== false || strpos($action, 'profile') !== false || strpos($action, 'change') !== false) {
        $label = $translations[$lang]['severity_warning'] ?? $translations['en_PH']['severity_warning'];
        return '<span class="badge bg-warning text-dark border-0 shadow-sm px-2 py-1"><i class="bi bi-exclamation-triangle me-1"></i>' . $label . '</span>';
    }
    
    // INFO: Login, Logout, View (Default)
    $label = $translations[$lang]['severity_info'] ?? $translations['en_PH']['severity_info'];
    return '<span class="badge bg-info text-white border-0 shadow-sm px-2 py-1"><i class="bi bi-info-circle me-1"></i>' . $label . '</span>';
}

// --- FILTERS ---
$filters = [
    'action'    => $_GET['action'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to'   => $_GET['date_to'] ?? ''
];

// --- 1. EXPORT HANDLER (token-gated) ---
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
        $tokenTable === 'audit_logs' &&
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
             <a href="audit-logs.php">Go back</a></div>');
    }

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
$totalPages = max(1, ceil($totalLogs / $limit));
$logs       = $userController->getAuditLogs($filters, $limit, $offset);

$query_string = http_build_query(array_filter($filters));

$pageTitle = t_audit('page_title', $translations, $lang);
$isAuthPage = true;
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
    .pagination .page-item.active .page-link { background-color: #0d6efd; border-color: #0d6efd; color: white; }
    .pagination .page-link:hover { background-color: #e7f1ff; border-color: #b6d4fe; color: #0d6efd; }
    .info-text { font-size: 0.875rem; color: #6c757d; }
    .table-lgu thead { background-color: #f8f9fa; border-top: 2px solid #1a5c2b; }
    .breadcrumb-item a { color: #1a5c2b; text-decoration: none; }
    .table-hover tbody tr { cursor: pointer; transition: background 0.2s; }
    .table-hover tbody tr:hover { background-color: rgba(26, 92, 43, 0.05) !important; }
    .text-device { font-size: 0.75rem; color: #95a5a6; }
    .badge { font-size: 0.65rem; letter-spacing: 0.5px; font-weight: 700; }

    /* ── 768px: Tablet ─────────────────────────────────────────────────────── */
    @media (max-width: 768px) {

        .p-4.page-container { padding: 1rem !important; }

        /* Header: stack title and action buttons */
        .row.align-items-center.mb-4 { flex-direction: column; align-items: flex-start !important; gap: 12px; }
        .row.align-items-center.mb-4 .col-md-6 { width: 100%; flex: 0 0 100%; }
        .row.align-items-center.mb-4 .col-md-6.text-md-end {
            text-align: left !important;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .row.align-items-center.mb-4 h2 { font-size: 1.3rem; }
        .btn-purge, .btn-export-lgu { font-size: 0.78rem; padding: 7px 12px; }

        /* Filter card: full-width fields */
        .card .row.g-2 .col-md-3,
        .card .row.g-2 .col-md-2,
        .card .row.g-2 .col-md-5 { width: 100%; flex: 0 0 100%; }
        .btn-group.w-100 { width: 100% !important; }

        /* Table: hide IP Address + Reference ID */
        .table-lgu thead th:nth-child(5),
        .table-lgu tbody td:nth-child(5),
        .table-lgu thead th:nth-child(6),
        .table-lgu tbody td:nth-child(6) { display: none; }

        .table-lgu { font-size: 0.78rem; }
        .table-lgu th, .table-lgu td { padding: 0.5rem 0.4rem; }

        /* Pagination */
        .card-footer .row { flex-direction: column; gap: 10px; text-align: center; }
        .card-footer .col-md-6:last-child { text-align: center !important; }
        .pagination { justify-content: center !important; }
    }

    /* ── 480px: Large Mobile ───────────────────────────────────────────────── */
    @media (max-width: 480px) {

        .p-4.page-container { padding: 0.75rem !important; }

        /* Header */
        .row.align-items-center.mb-4 h2 { font-size: 1.1rem; }
        .row.align-items-center.mb-4 p { font-size: 0.75rem; }
        .btn-purge, .btn-export-lgu {
            font-size: 0.72rem;
            padding: 6px 10px;
            width: 100%;
            justify-content: center;
        }

        /* Filter card */
        .card.border-0.shadow-sm.mb-4 .card-body { padding: 0.75rem !important; }
        .card .row.g-2 { --bs-gutter-y: 0.4rem; }
        .form-control-sm { font-size: 0.78rem; }
        .form-label { font-size: 0.65rem !important; }
        .btn-group .btn-sm { font-size: 0.75rem; padding: 6px 10px; }

        /* Table: hide User + IP Address + Reference ID */
        .table-lgu thead th:nth-child(3),
        .table-lgu tbody td:nth-child(3),
        .table-lgu thead th:nth-child(5),
        .table-lgu tbody td:nth-child(5),
        .table-lgu thead th:nth-child(6),
        .table-lgu tbody td:nth-child(6) { display: none; }

        .table-lgu { font-size: 0.72rem; }
        .table-lgu th, .table-lgu td { padding: 0.4rem 0.3rem; }
        .table-lgu td .badge { font-size: 0.6rem; padding: 3px 6px; }
        .table-lgu td span.badge.bg-light {
            font-size: 0.65rem;
            max-width: 110px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
            vertical-align: middle;
        }

        /* Pagination */
        .pagination .page-link { font-size: 0.72rem; padding: 4px 8px; }
        .card-footer { padding: 0.6rem 0.75rem; }
        .info-text { font-size: 0.72rem; }

        /* Modals */
        .modal-body { padding: 1rem; font-size: 0.82rem; }
        .modal-header { padding: 0.75rem 1rem; }
        .modal-footer .btn { font-size: 0.78rem; padding: 6px 12px; }
    }

    /* ── 320px: Small Mobile ───────────────────────────────────────────────── */
    @media (max-width: 320px) {

        .p-4.page-container { padding: 0.5rem !important; }

        /* Header */
        .row.align-items-center.mb-4 h2 { font-size: 0.95rem; }
        .row.align-items-center.mb-4 p { font-size: 0.68rem; }
        .btn-purge, .btn-export-lgu { font-size: 0.68rem; padding: 5px 8px; }

        /* Filter */
        .card.border-0.shadow-sm.mb-4 .card-body { padding: 0.5rem !important; }
        .form-control-sm { font-size: 0.72rem; padding: 3px 6px; }
        .btn-group .btn-sm { font-size: 0.68rem; padding: 5px 8px; }

        /* Table: keep only Severity + Timestamp + Action */
        .table-lgu thead th:nth-child(3),
        .table-lgu tbody td:nth-child(3),
        .table-lgu thead th:nth-child(5),
        .table-lgu tbody td:nth-child(5),
        .table-lgu thead th:nth-child(6),
        .table-lgu tbody td:nth-child(6) { display: none; }

        .table-lgu { font-size: 0.65rem; }
        .table-lgu th, .table-lgu td { padding: 0.3rem 0.2rem; }
        .table-lgu td .badge { font-size: 0.55rem; padding: 2px 5px; }
        .table-lgu td span.badge.bg-light {
            max-width: 80px;
            font-size: 0.58rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
            vertical-align: middle;
        }

        /* Pagination: show only prev/next + current */
        .pagination .page-item:not(:first-child):not(:nth-child(2)):not(:last-child):not(:nth-last-child(2)) { display: none; }
        .pagination .page-link { font-size: 0.65rem; padding: 3px 7px; }
        .card-footer { padding: 0.5rem; }
        .info-text { font-size: 0.65rem; }

        /* Modals */
        .modal-dialog { margin: 0.4rem; }
        .modal-body { padding: 0.6rem; font-size: 0.75rem; }
        .modal-header { padding: 0.5rem 0.6rem; }
        .modal-footer { padding: 0.4rem 0.6rem; }
        .modal-footer .btn { font-size: 0.68rem; padding: 4px 8px; }
    }
</style>

<div class="p-4 page-container">

    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-shield-check text-success me-2"></i><?= t_audit('page_heading', $translations, $lang) ?>
            </h2>
            <p class="text-muted small mb-0"><?= t_audit('page_subtitle', $translations, $lang) ?></p>
        </div>
        <div class="col-md-6 text-md-end">
            <button type="button" class="btn btn-purge shadow-sm me-2" onclick="openPurgeModal()">
                <i class="bi bi-trash3-fill me-1"></i> <?= t_audit('btn_purge_settings', $translations, $lang) ?>
            </button>
            <button type="button" class="btn-export-lgu shadow-sm"
                onclick="openExportModal('csv', 'audit_logs', '?export=csv&<?= $query_string ?>')">
                <i class="bi bi-file-earmark-excel-fill"></i>
                <span><?= t_audit('btn_generate_report', $translations, $lang) ?></span>
            </button>
        </div>
    </div>

    <?= $purgeMessage ?>

    <div class="card border-0 shadow-sm mb-4" style="border-left: 5px solid #1a5c2b !important;">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-uppercase text-muted" style="font-size: 0.7rem;"><?= t_audit('filter_action_label', $translations, $lang) ?></label>
                    <input type="text" class="form-control form-control-sm" name="action" placeholder="<?= t_audit('filter_action_ph', $translations, $lang) ?>" value="<?= htmlspecialchars($filters['action']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-uppercase text-muted" style="font-size: 0.7rem;"><?= t_audit('filter_date_from', $translations, $lang) ?></label>
                    <input type="date" class="form-control form-control-sm" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-uppercase text-muted" style="font-size: 0.7rem;"><?= t_audit('filter_date_to', $translations, $lang) ?></label>
                    <input type="date" class="form-control form-control-sm" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>">
                </div>
                <div class="col-md-5">
                    <div class="btn-group w-100 shadow-sm">
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold"><?= t_audit('btn_apply_filters', $translations, $lang) ?></button>
                        <a href="audit-logs.php" class="btn btn-sm border fw-bold text-muted"><?= t_audit('btn_reset', $translations, $lang) ?></a>
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
                            <th class="ps-4 py-3 text-muted small text-uppercase"><?= t_audit('th_severity', $translations, $lang) ?></th>
                            <th class="text-muted small text-uppercase"><?= t_audit('th_timestamp', $translations, $lang) ?></th>
                            <th class="text-muted small text-uppercase"><?= t_audit('th_user', $translations, $lang) ?></th>
                            <th class="text-muted small text-uppercase"><?= t_audit('th_action', $translations, $lang) ?></th>
                            <th class="text-muted small text-uppercase"><?= t_audit('th_ip', $translations, $lang) ?></th>
                            <th class="text-muted small text-uppercase"><?= t_audit('th_ref_id', $translations, $lang) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted small italic"><?= t_audit('no_records', $translations, $lang) ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                            <tr onclick="showLogDetails(this)" 
                                data-user="<?= htmlspecialchars($log['username'] ?? 'SYSTEM') ?>"
                                data-action="<?= htmlspecialchars($log['action']) ?>"
                                data-time="<?= Helper::formatDateTime($log['created_at']) ?>"
                                data-details="<?= htmlspecialchars($log['details']) ?>"
                                data-ip="<?= htmlspecialchars($log['ip_address']) ?>"
                                data-agent="<?= htmlspecialchars($log['user_agent'] ?? 'Unknown Device') ?>">
                                <td class="ps-4"><?= getSeverityTag($log['action'], $translations, $lang) ?></td>
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
                        <?= t_audit('pg_showing', $translations, $lang) ?> <strong><?= ($offset + 1) ?></strong> <?= t_audit('pg_to', $translations, $lang) ?> 
                        <strong><?= min($offset + $limit, $totalLogs) ?></strong> <?= t_audit('pg_of', $translations, $lang) ?> 
                        <strong><?= $totalLogs ?></strong> <?= t_audit('pg_entries', $translations, $lang) ?>
                    </span>
                </div>
                <div class="col-md-6 text-md-end">
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm justify-content-center justify-content-md-end mb-0">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?p=1&<?= $query_string ?>"><i class="bi bi-chevron-double-left"></i></a>
                            </li>
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?p=<?= ($page - 1) ?>&<?= $query_string ?>"><?= t_audit('pg_prev', $translations, $lang) ?></a>
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
                                <a class="page-link" href="?p=<?= ($page + 1) ?>&<?= $query_string ?>"><?= t_audit('pg_next', $translations, $lang) ?></a>
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

<!-- Hidden purge form — submitted programmatically after password is verified -->
<form id="purgeForm" method="POST" style="display:none;">
    <input type="hidden" name="purge_logs" value="1">
    <input type="hidden" name="purge_years" id="purgeYearsHidden" value="2">
</form>

<!-- ===== PURGE VERIFY MODAL ===== -->
<div class="modal fade" id="purgeVerifyModal" tabindex="-1" aria-labelledby="purgeVerifyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="purgeVerifyModalLabel">
                    <i class="bi bi-shield-lock-fill me-2"></i><?= t_audit('purge_modal_title', $translations, $lang) ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div style="background:#f8d7da;border:1px solid #f5c2c7;border-radius:6px;padding:0.5rem 0.75rem;" class="d-flex align-items-center gap-2 small mb-4">
                    <i class="bi bi-exclamation-octagon-fill fs-5 text-danger flex-shrink-0"></i>
                    <span><?= t_audit('purge_warning', $translations, $lang) ?></span>
                </div>

                <div id="purgeVerifyAlert" class="alert small py-2 mb-3" style="display:none;"></div>

                <div class="mb-3">
                    <label class="form-label small fw-bold"><?= t_audit('purge_older_than', $translations, $lang) ?> <span class="text-danger">*</span></label>
                    <select id="purgeYearsSelect" class="form-select">
                        <option value="1"><?= t_audit('purge_1yr', $translations, $lang) ?></option>
                        <option value="2" selected><?= t_audit('purge_2yr', $translations, $lang) ?></option>
                        <option value="3"><?= t_audit('purge_3yr', $translations, $lang) ?></option>
                        <option value="5"><?= t_audit('purge_5yr', $translations, $lang) ?></option>
                    </select>
                </div>

                <div class="mb-1">
                    <label class="form-label small fw-bold"><?= t_audit('export_password_label', $translations, $lang) ?> <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" id="purgePassword" class="form-control"
                               placeholder="<?= t_audit('export_password_ph', $translations, $lang) ?>">
                        <span class="input-group-text bg-white" style="cursor:pointer;"
                              onclick="togglePasswordVisibility('purgePassword', 'purgeEyeIcon')">
                            <i class="bi bi-eye-slash" id="purgeEyeIcon"></i>
                        </span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal"><?= t_audit('btn_cancel', $translations, $lang) ?></button>
                <button type="button" class="btn btn-danger px-4" id="purgeVerifyBtn">
                    <span id="purgeBtnSpinner" class="spinner-border spinner-border-sm me-1 d-none"></span>
                    <i class="bi bi-trash3-fill me-1" id="purgeBtnIcon"></i> <?= t_audit('btn_verify_purge', $translations, $lang) ?>
                </button>
            </div>
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
                        <label class="text-uppercase small fw-bold text-muted d-block"><?= t_audit('modal_log_performed', $translations, $lang) ?></label>
                        <span id="modalUser" class="fw-bold text-primary"></span>
                    </div>
                    <div class="col-6 text-end">
                        <label class="text-uppercase small fw-bold text-muted d-block"><?= t_audit('modal_log_ip', $translations, $lang) ?></label>
                        <span id="modalIP" class="text-dark font-monospace small"></span>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="text-uppercase small fw-bold text-muted d-block"><?= t_audit('modal_log_timestamp', $translations, $lang) ?></label>
                    <span id="modalTime" class="text-dark"></span>
                </div>
                <div class="mb-3 p-2 bg-light border rounded">
                    <label class="text-uppercase small fw-bold text-muted d-block" style="font-size: 0.65rem;"><?= t_audit('modal_log_device', $translations, $lang) ?></label>
                    <span id="modalAgentDisplay" class="fw-bold d-block"></span>
                    <span id="modalAgentRaw" class="text-muted small italic" style="font-size: 0.7rem;"></span>
                </div>
                <hr>
                <div class="mb-0">
                    <label class="text-uppercase small fw-bold text-muted d-block mb-2"><?= t_audit('modal_log_changes', $translations, $lang) ?></label>
                    <div id="modalDetails" class="p-3 bg-dark text-light border rounded small italic" style="white-space: pre-wrap; font-family: monospace;"></div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal"><?= t_audit('btn_close', $translations, $lang) ?></button>
            </div>
        </div>
    </div>
</div>

<!-- ===== TOAST NOTIFICATION CONTAINER ===== -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
    <div id="auditToast" class="toast align-items-center border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i class="bi" id="auditToastIcon" style="font-size:1.1rem;flex-shrink:0;"></i>
                <span id="auditToastMsg"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- ===== SECURE EXPORT VERIFICATION MODAL ===== -->
<div class="modal fade" id="exportVerifyModal" tabindex="-1" aria-labelledby="exportVerifyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background-color:#1a5c2b;">
                <h5 class="modal-title" id="exportVerifyModalLabel">
                    <i class="bi bi-shield-lock-fill me-2"></i><?= t_audit('export_modal_title', $translations, $lang) ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:0.5rem 0.75rem;" class="d-flex align-items-center gap-2 small mb-4">
                    <i class="bi bi-exclamation-triangle-fill fs-5 text-warning flex-shrink-0"></i>
                    <span><?= t_audit('export_warning', $translations, $lang) ?></span>
                </div>

                <div id="exportVerifyAlert" class="alert small py-2 mb-3" style="display:none;"></div>

                <div class="mb-3">
                    <label class="form-label small fw-bold"><?= t_audit('export_purpose_label', $translations, $lang) ?> <span class="text-danger">*</span></label>
                    <select id="exportReason" class="form-select">
                        <option value=""><?= t_audit('export_purpose_ph', $translations, $lang) ?></option>
                        <option value="Reporting">Reporting</option>
                        <option value="Auditing">Auditing</option>
                        <option value="Archiving">Archiving</option>
                        <option value="Compliance Review">Compliance Review</option>
                        <option value="Data Backup">Data Backup</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="mb-1">
                    <label class="form-label small fw-bold"><?= t_audit('export_password_label', $translations, $lang) ?> <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" id="exportPassword" class="form-control"
                               placeholder="<?= t_audit('export_password_ph', $translations, $lang) ?>">
                        <span class="input-group-text bg-white" style="cursor:pointer;"
                              onclick="togglePasswordVisibility('exportPassword', 'exportEyeIcon')">
                            <i class="bi bi-eye-slash" id="exportEyeIcon"></i>
                        </span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal"><?= t_audit('btn_cancel', $translations, $lang) ?></button>
                <button type="button" class="btn text-white px-4" id="exportVerifyBtn"
                        style="background-color:#1a5c2b;">
                    <span id="exportBtnSpinner" class="spinner-border spinner-border-sm me-1 d-none"></span>
                    <i class="bi bi-download me-1" id="exportBtnIcon"></i> <?= t_audit('btn_verify_download', $translations, $lang) ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ── Translations passed from PHP ──────────────────────────────────────────────
const AUDIT_T = <?php echo json_encode([
    'export_select_reason'  => t_audit('js_export_select_reason',  $translations, $lang),
    'export_enter_password' => t_audit('js_export_enter_password', $translations, $lang),
    'export_success'        => t_audit('js_export_success',        $translations, $lang),
    'export_network_error'  => t_audit('js_export_network_error',  $translations, $lang),
    'purge_enter_password'  => t_audit('js_purge_enter_password',  $translations, $lang),
    'purge_success'         => t_audit('js_purge_success',         $translations, $lang),
    'network_error'         => t_audit('js_network_error',         $translations, $lang),
    'no_changes'            => t_audit('modal_log_no_changes',     $translations, $lang),
]); ?>;

// ===== EXPORT VERIFICATION LOGIC =====
const _exportModalEl = document.getElementById('exportVerifyModal');
const _purgeModalEl  = document.getElementById('purgeVerifyModal');
let _exportType = '', _exportTable = '', _exportUrl = '';

/* ---- shared helper ---- */
function _elmt(id) { return document.getElementById(id); }

/* ---- shared toggle password visibility ---- */
function togglePasswordVisibility(inputId, eyeId) {
    var input = document.getElementById(inputId);
    var eye   = document.getElementById(eyeId);
    if (input.type === 'password') {
        input.type = 'text';
        eye.classList.replace('bi-eye-slash', 'bi-eye');
    } else {
        input.type = 'password';
        eye.classList.replace('bi-eye', 'bi-eye-slash');
    }
}

/* ---- shared toast ---- */
function _showToast(msg, type) {
    var toastEl   = _elmt('auditToast');
    var toastMsg  = _elmt('auditToastMsg');
    var toastIcon = _elmt('auditToastIcon');
    var config = {
        warning: { bg: 'bg-warning', text: 'text-dark',  icon: 'bi-exclamation-triangle-fill' },
        danger:  { bg: 'bg-danger',  text: 'text-white', icon: 'bi-x-circle-fill'             },
        success: { bg: 'bg-success', text: 'text-white', icon: 'bi-check-circle-fill'         },
        info:    { bg: 'bg-info',    text: 'text-dark',  icon: 'bi-info-circle-fill'          }
    };
    var c = config[type] || config['info'];
    toastEl.className   = 'toast align-items-center border-0 shadow ' + c.bg + ' ' + c.text;
    toastIcon.className = 'bi ' + c.icon;
    toastMsg.innerText  = msg;
    bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 3500 }).show();
}

/* ---- shared inline alert (for server responses inside modal) ---- */
function _showAlert(alertId, msg, type) {
    var el = _elmt(alertId);
    if (!el) return;
    el.style.display = 'none';
    el.innerHTML = '';
    void el.offsetHeight; // force reflow
    el.className = 'alert alert-' + type + ' small py-2 mb-3';
    el.innerText = msg;
    el.style.display = 'block';
}

function _hideAlert(alertId) {
    var el = _elmt(alertId);
    if (!el) return;
    el.style.display = 'none';
    el.className = 'alert small py-2 mb-3';
    el.innerText = '';
}

/* ---- export: reset modal ---- */
function _resetExportModal() {
    _elmt('exportPassword').value    = '';
    _elmt('exportPassword').type     = 'password';
    _elmt('exportReason').value      = '';
    _elmt('exportEyeIcon').className = 'bi bi-eye-slash';
    _elmt('exportVerifyBtn').disabled = false;
    _elmt('exportBtnSpinner').classList.add('d-none');
    _elmt('exportBtnIcon').classList.remove('d-none');
    _hideAlert('exportVerifyAlert');
}

/* ---- purge: reset modal ---- */
function _resetPurgeModal() {
    _elmt('purgePassword').value      = '';
    _elmt('purgePassword').type       = 'password';
    _elmt('purgeYearsSelect').value   = '2';
    _elmt('purgeEyeIcon').className   = 'bi bi-eye-slash';
    _elmt('purgeVerifyBtn').disabled  = false;
    _elmt('purgeBtnSpinner').classList.add('d-none');
    _elmt('purgeBtnIcon').classList.remove('d-none');
    _hideAlert('purgeVerifyAlert');
}

/* ---- export: open modal ---- */
function openExportModal(type, table, downloadUrl) {
    _exportType  = type.toUpperCase();
    _exportTable = table;
    _exportUrl   = new URL(downloadUrl, window.location.href).href;
    _resetExportModal();
    bootstrap.Modal.getOrCreateInstance(_exportModalEl).show();
}

/* ---- purge: open modal ---- */
function openPurgeModal() {
    _resetPurgeModal();
    bootstrap.Modal.getOrCreateInstance(_purgeModalEl).show();
}

/* ---- clean up on close ---- */
_exportModalEl.addEventListener('hide.bs.modal', function () {
    var f = _exportModalEl.querySelector(':focus'); if (f) f.blur();
});
_exportModalEl.addEventListener('hidden.bs.modal', function () { _hideAlert('exportVerifyAlert'); });

_purgeModalEl.addEventListener('hide.bs.modal', function () {
    var f = _purgeModalEl.querySelector(':focus'); if (f) f.blur();
});
_purgeModalEl.addEventListener('hidden.bs.modal', function () { _hideAlert('purgeVerifyAlert'); });

/* ---- export: loading state ---- */
function _setExportBtnLoading(on) {
    _elmt('exportVerifyBtn').disabled = on;
    _elmt('exportBtnSpinner').classList.toggle('d-none', !on);
    _elmt('exportBtnIcon').classList.toggle('d-none', on);
}

/* ---- purge: loading state ---- */
function _setPurgeBtnLoading(on) {
    _elmt('purgeVerifyBtn').disabled = on;
    _elmt('purgeBtnSpinner').classList.toggle('d-none', !on);
    _elmt('purgeBtnIcon').classList.toggle('d-none', on);
}

/* ---- export: submit ---- */
function submitExportVerification() {
    var password = _elmt('exportPassword').value.trim();
    var reason   = _elmt('exportReason').value;

    if (!reason) {
        _showToast(AUDIT_T.export_select_reason, 'warning');
        return;
    }
    if (!password) {
        _showToast(AUDIT_T.export_enter_password, 'warning');
        return;
    }

    _setExportBtnLoading(true);
    _hideAlert('exportVerifyAlert');

    var basePath   = window.location.pathname.replace(/\/[^/]+$/, '/');
    var verifyPath = basePath + 'verify_action.php';
    var fd = new FormData();
    fd.append('password',    password);
    fd.append('reason',      reason);
    fd.append('export_type', _exportType);
    fd.append('table_name',  _exportTable);

    fetch(verifyPath, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function(res) { if (!res.ok) throw new Error('Server error: ' + res.status); return res.json(); })
        .then(function(data) {
            if (!data.success) {
                _setExportBtnLoading(false);
                _showAlert('exportVerifyAlert', data.message || AUDIT_T.export_select_reason, 'danger');
                return;
            }
            _showAlert('exportVerifyAlert', AUDIT_T.export_success, 'success');
            var sep         = _exportUrl.includes('?') ? '&' : '?';
            var downloadUrl = _exportUrl + sep + 'export_token=' + encodeURIComponent(data.token);
            var iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = downloadUrl;
            document.body.appendChild(iframe);
            setTimeout(function() {
                document.body.removeChild(iframe);
                _setExportBtnLoading(false);
                bootstrap.Modal.getOrCreateInstance(_exportModalEl).hide();
            }, 3000);
        })
        .catch(function() {
            _setExportBtnLoading(false);
            _showAlert('exportVerifyAlert', AUDIT_T.export_network_error, 'danger');
        });
}

/* ---- purge: submit ---- */
function submitPurgeVerification() {
    var password = _elmt('purgePassword').value.trim();
    var years    = _elmt('purgeYearsSelect').value;

    if (!password) {
        _showToast(AUDIT_T.purge_enter_password, 'warning');
        return;
    }

    _setPurgeBtnLoading(true);
    _hideAlert('purgeVerifyAlert');

    var basePath   = window.location.pathname.replace(/\/[^/]+$/, '/');
    var verifyPath = basePath + 'verify_action.php';
    var fd = new FormData();
    fd.append('password',    password);
    fd.append('reason',      'Purge audit_logs older than ' + years + ' year(s)');
    fd.append('export_type', 'PURGE');
    fd.append('table_name',  'audit_logs');

    fetch(verifyPath, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function(res) { if (!res.ok) throw new Error('Server error: ' + res.status); return res.json(); })
        .then(function(data) {
            if (!data.success) {
                _setPurgeBtnLoading(false);
                _showAlert('purgeVerifyAlert', data.message || AUDIT_T.purge_enter_password, 'danger');
                return;
            }
            _showAlert('purgeVerifyAlert', AUDIT_T.purge_success, 'success');
            _elmt('purgeYearsHidden').value = years;
            setTimeout(function() {
                bootstrap.Modal.getOrCreateInstance(_purgeModalEl).hide();
                _elmt('purgeForm').submit();
            }, 1200);
        })
        .catch(function() {
            _setPurgeBtnLoading(false);
            _showAlert('purgeVerifyAlert', AUDIT_T.network_error, 'danger');
        });
}

_elmt('exportVerifyBtn').onclick = submitExportVerification;
_elmt('purgeVerifyBtn').onclick  = submitPurgeVerification;
// ===== END VERIFICATION LOGIC =====


// ===== LOG DETAIL MODAL =====
function showLogDetails(row) {
    var user    = row.getAttribute('data-user');
    var action  = row.getAttribute('data-action');
    var time    = row.getAttribute('data-time');
    var details = row.getAttribute('data-details');
    var ip      = row.getAttribute('data-ip');
    var agent   = row.getAttribute('data-agent');

    var displayDevice  = 'Unknown Device';
    var displayBrowser = 'Unknown Browser';

    if      (agent.includes('Windows NT 10.0')) displayDevice = 'Windows 10/11 Desktop';
    else if (agent.includes('Android'))          displayDevice = 'Android Mobile';
    else if (agent.includes('iPhone'))           displayDevice = 'iPhone/iOS';
    else if (agent.includes('Macintosh'))        displayDevice = 'Mac Desktop';

    if      (agent.includes('Chrome') && !agent.includes('Edg'))    displayBrowser = 'Google Chrome';
    else if (agent.includes('Edg'))                                  displayBrowser = 'Microsoft Edge';
    else if (agent.includes('Firefox'))                              displayBrowser = 'Mozilla Firefox';
    else if (agent.includes('Safari') && !agent.includes('Chrome')) displayBrowser = 'Apple Safari';

    document.getElementById('modalTitle').innerText        = action;
    document.getElementById('modalUser').innerText         = user;
    document.getElementById('modalTime').innerText         = time;
    document.getElementById('modalIP').innerText           = ip;
    document.getElementById('modalAgentDisplay').innerText = displayDevice + ' (' + displayBrowser + ')';
    document.getElementById('modalAgentRaw').innerText     = agent;
    document.getElementById('modalDetails').innerText      = details ? details : AUDIT_T.no_changes;

    bootstrap.Modal.getOrCreateInstance(document.getElementById('logModal')).show();
}
</script>

<?php include __DIR__ . '/footer.php'; ?>
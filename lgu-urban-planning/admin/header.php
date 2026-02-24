<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Helper.php';
require_once __DIR__ . '/../core/Auth.php';
$dbHeader = Database::getInstance();
$unreadMessages = 0;
if (isset($_SESSION['user_id'])) {
    $row = $dbHeader->fetchOne("SELECT COUNT(*) AS cnt FROM messages WHERE receiver_id = ? AND is_read = 0", [$_SESSION['user_id']]);
    $unreadMessages = $row['cnt'] ?? 0;
}

$current_path = $_SERVER['PHP_SELF'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>LGU Urban Planning System - Admin Portal</title>
    <link rel="icon" type="image/x-icon" href="/lgu-urban-planning/assets/favicon.jpg" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment: fixed;
            min-height: 100vh;
        }
        
        .top-navbar {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            backdrop-filter: blur(10px);
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            position: sticky;
            top: 0;
            z-index: 1000;
            margin-bottom: 0;
        }
        
        .top-navbar h5 {
            color: white !important;
        }
        
        .top-navbar .user-info {
            color: white;
        }
        
        .top-navbar .user-info .text-muted {
            color: rgba(255, 255, 255, 0.8) !important;
        }
        
        .top-navbar .user-info div[style*="color: #1e293b"] {
            color: white !important;
        }
        
        /* --- SIDEBAR UPDATED --- */
        .sidebar {
            height: calc(100vh - 70px);
            background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 50%, #1e3a8a 100%);
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 70px;
            overflow: hidden;
            transition: all 0.3s ease;
            width: 250px;
            display: flex !important;
            flex-direction: column;
        }
        
        .sidebar.collapsed {
            width: 80px;
        }
        
        .sidebar.collapsed .sidebar-text {
            display: none;
        }
        
        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding: 14px 10px;
        }
        
        .sidebar.collapsed h4 {
            font-size: 1rem;
            text-align: center;
        }
        
        /* --- SCROLLBAR HIDER LOGIC --- */
        .sidebar-content {
            flex: 1;
            padding: 20px 0;
            overflow-y: auto; 
            scrollbar-width: none; 
            -ms-overflow-style: none; 
        }

        .sidebar-content::-webkit-scrollbar {
            display: none; 
        }

        .sidebar-footer {
            padding: 10px 0 20px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-toggle {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .sidebar-toggle:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .top-navbar .sidebar-toggle {
            margin-bottom: 0;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .top-navbar .sidebar-toggle:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M 20 0 L 0 0 0 20" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }
        
        .sidebar > * {
            position: relative;
            z-index: 1;
        }

        .sidebar h4 {
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            padding: 0 20px;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.9);
            padding: 14px 20px;
            margin: 4px 12px;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .sidebar .nav-link i {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .sidebar .nav-link.active {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: #fff;
            box-shadow: 0 4px 16px rgba(59, 130, 246, 0.4);
            font-weight: 600;
        }

        .sidebar .nav-link.logout-btn {
            color: #ffbaba;
        }

        .sidebar .nav-link.logout-btn:hover {
            background: rgba(220, 53, 69, 0.2);
            color: #fff;
        }
        
        .main-content {
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
            min-height: 100vh;
            padding: 30px;
            transition: all 0.3s ease;
            flex: 1;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .nav-link-toggle {
            cursor: pointer;
        }
        .submenu .nav-link {
            padding-left: 30px;
        }
        .toggle-caret {
            transition: transform 0.2s ease;
        }
        .collapse.show + .toggle-caret,
        .nav-link-toggle[aria-expanded="true"] .toggle-caret {
            transform: rotate(180deg);
        }

        /* --- DARK MODE UI --- */
        [data-bs-theme="dark"] body { color: #ced4da !important; }
        [data-bs-theme="dark"] h1, [data-bs-theme="dark"] h2, [data-bs-theme="dark"] h3, 
        [data-bs-theme="dark"] h4, [data-bs-theme="dark"] h5, [data-bs-theme="dark"] h6,
        [data-bs-theme="dark"] p, [data-bs-theme="dark"] label, [data-bs-theme="dark"] strong { color: #ffffff !important; }
        [data-bs-theme="dark"] .sidebar { background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%) !important; }
        [data-bs-theme="dark"] .main-content { background: #0f172a !important; }
        [data-bs-theme="dark"] .top-navbar { background: #1e293b !important; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        [data-bs-theme="dark"] .card { background-color: #1e293b !important; border: 1px solid rgba(255, 255, 255, 0.1) !important; }
        [data-bs-theme="dark"] .form-control, [data-bs-theme="dark"] .form-select { background-color: #0f172a !important; border-color: #334155 !important; color: #ffffff !important; }
        [data-bs-theme="dark"] .overdue-alert { background: linear-gradient(135deg, #2d1616 0%, #1e293b 100%) !important; border: 1px solid rgba(239, 68, 68, 0.2) !important; border-left: 5px solid #ef4444 !important; }
        [data-bs-theme="dark"] .overdue-alert .alert-text { color: #e2e8f0 !important; }
        [data-bs-theme="dark"] .overdue-alert strong { color: #ff8080 !important; }
        [data-bs-theme="dark"] .empty-placeholder { background-color: rgba(255, 255, 255, 0.05); color: #adb5bd !important; border: 1px solid rgba(255, 255, 255, 0.1); }
        [data-bs-theme="dark"] .fc {
        --fc-border-color: #444;
        --fc-page-bg-color: #2b3035;
        --fc-neutral-bg-color: #343a40;
        --fc-list-event-hover-bg-color: #3d4246;
        color: #dee2e6; }
        [data-bs-theme="dark"] .fc-theme-bootstrap5 a {
        color: #fff; }
        [data-bs-theme="dark"] .status-active { background-color: #0a2e1f; color: #75b798; }
        [data-bs-theme="dark"] .status-inactive { background-color: #2c0b0e; color: #ea868f; }
        [data-bs-theme="dark"] .card { border: 1px solid rgba(255,255,255,0.1); }
        [data-bs-theme="dark"] .table { color: #dee2e6; }
        [data-bs-theme="dark"] .modal-content { border: 1px solid rgba(255,255,255,0.15); }
        [data-bs-theme="dark"] .pagination .page-link { background-color: #1a1d20; border-color: #373b3e; color: #dee2e6; }
        [data-bs-theme="dark"] .pagination .page-item.active .page-link { background-color: #3d444b; border-color: #495057; }
        [data-bs-theme="dark"] .page-container { background-color: #0f172a !important; }
        [data-bs-theme="dark"] .card { background-color: #1e293b !important; border-color: rgba(255, 255, 255, 0.1) !important; }
        [data-bs-theme="dark"] .table-lgu thead { background-color: #334155 !important; border-top: 2px solid #22c55e !important; }
        [data-bs-theme="dark"] .table-lgu { color: #e2e8f0 !important; }
        [data-bs-theme="dark"] .table-hover tbody tr:hover { background-color: rgba(255, 255, 255, 0.05) !important; }
        [data-bs-theme="dark"] .modal-content
        [data-bs-theme="dark"] .modal-header
        [data-bs-theme="dark"] .modal-footer { background-color: #1e293b !important; color: #ffffff !important; border-color: rgba(255, 255, 255, 0.1) !important; }
        [data-bs-theme="dark"] .bg-light { background-color: #334155 !important; color: #ffffff !important; }
        [data-bs-theme="dark"] .breadcrumb-item a { color: #4ade80 !important; }
        [data-bs-theme="dark"] .text-muted { color: #94a3b8 !important; }
        [data-bs-theme="dark"] .report-main-grid, 
        [data-bs-theme="dark"] .empty-report-state,
        [data-bs-theme="dark"] .chart-card-container,
        [data-bs-theme="dark"] .table-container-fixed,
        [data-bs-theme="dark"] .card {
            background-color: #1e293b !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
            color: #f1f5f9 !important;
        }

        [data-bs-theme="dark"] .card-header, 
        [data-bs-theme="dark"] .card-footer {
            background-color: #334155 !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        [data-bs-theme="dark"] .permits-table thead {
            background-color: #0f172a !important;
        }

        [data-bs-theme="dark"] .permits-table td {
            color: #cbd5e1 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        [data-bs-theme="dark"] .empty-report-state {
            background: #1e293b !important;
            border: 2px dashed #475569 !important;
        }

        [data-bs-theme="dark"] .form-label, 
        [data-bs-theme="dark"] h2, 
        [data-bs-theme="dark"] h4, 
        [data-bs-theme="dark"] h5 {
            color: #ffffff !important;
        }

    </style>
    <script src="/lgu-urban-planning/assets/js/admin.js" defer></script>
</head>
<body>
    <div class="container-fluid p-0">
        <nav class="top-navbar">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
                        <i class="bi bi-list"></i>
                    </button>
                    <h5 class="mb-0" style="font-weight: 600;">Urban Planning and Development</h5>
                </div>
                <div class="user-info">
                    <button class="btn btn-link text-white me-3 p-0" onclick="toggleDarkMode()" title="Toggle Dark/Light Mode" style="text-decoration: none;">
                        <i id="themeIcon" class="bi bi-moon-stars" style="font-size: 1.2rem;"></i>
                    </button>

                    <div class="me-3 position-relative">
                        <a href="/lgu-urban-planning/admin/messages.php" class="text-white" title="Messages">
                            <i class="bi bi-bell" style="font-size: 1.4rem;"></i>
                            <?php if ($unreadMessages > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                                    <?php echo $unreadMessages > 99 ? '99+' : $unreadMessages; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </div>

                    <div class="text-end d-none d-md-block text-white me-3">
                        <div style="font-weight: 600; font-size: 0.9rem; line-height: 1.2;">
                            <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </div>
                        <small style="color: rgba(255, 255, 255, 0.8); font-size: 0.75rem;">
                            <?php echo Helper::getRoleName($_SESSION['role']); ?>
                        </small>
                    </div>

                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                    </div>
                </div> 
            </div> 
        </nav>
        
        <div class="d-flex">
            <nav class="sidebar" id="sidebar">
                <div class="sidebar-content">
                    <h4 class="text-white mb-4 sidebar-text">Admin Portal</h4>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo (strpos($current_path, '/admin/index.php') !== false) ? 'active' : ''; ?>" href="/lgu-urban-planning/admin/index.php" title="Dashboard">
                                <i class="bi bi-house-door"></i> <span class="sidebar-text">Dashboard</span>
                            </a>
                        </li>
                        
                        <?php if ($_SESSION['role'] === 'inspector'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (strpos($current_path, 'my_tasks.php') !== false) ? 'active' : ''; ?>" href="/lgu-urban-planning/permit/my_tasks.php">
                                <i class="bi bi-clipboard-check"></i> <span class="sidebar-text">My Inspections</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php 
                        $isMonitoring = (strpos($current_path, '/monitoring/') !== false);
                        $isApps = (basename($current_path) == 'applications.php');
                        $appsOpen = $isApps || $isMonitoring; 
                        ?>
                        
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center justify-content-between nav-link-toggle <?php echo $appsOpen ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" data-bs-target="#sidebarApps" aria-expanded="<?php echo $appsOpen ? 'true' : 'false'; ?>" aria-controls="sidebarApps">
                                <div><i class="bi bi-file-earmark-text"></i> <span class="sidebar-text">Applications</span></div>
                                <i class="bi bi-caret-down-fill sidebar-text toggle-caret" style="font-size: 0.8rem;"></i>
                            </a>
                            <div class="collapse <?php echo $appsOpen ? 'show' : ''; ?>" id="sidebarApps">
                                <ul class="nav flex-column submenu ms-3 mb-2">
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo $isApps ? 'active' : ''; ?>" href="/lgu-urban-planning/permit/applications.php">
                                            <span class="sidebar-text">Development Permits</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo $isMonitoring ? 'active' : ''; ?>" href="/lgu-urban-planning/monitoring/index.php">
                                            <span class="sidebar-text">Monitoring &amp; Inspections</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link <?php echo (strpos($current_path, '/gis/') !== false) ? 'active' : ''; ?>" href="/lgu-urban-planning/gis/map.php" title="GIS Map">
                                <i class="bi bi-map"></i> <span class="sidebar-text">GIS Map</span>
                            </a>
                        </li>

                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo (basename($current_path) == 'users.php') ? 'active' : ''; ?>" href="/lgu-urban-planning/admin/users.php" title="User Management">
                                    <i class="bi bi-people"></i> <span class="sidebar-text">User Management</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo (basename($current_path) == 'audit-logs.php') ? 'active' : ''; ?>" href="/lgu-urban-planning/admin/audit-logs.php" title="Audit Logs">
                                    <i class="bi bi-journal-text"></i> <span class="sidebar-text">Audit Logs</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (in_array($_SESSION['role'], ['admin', 'zoning_officer'])): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo (strpos($current_path, '/reports/') !== false) ? 'active' : ''; ?>" href="/lgu-urban-planning/reports/index.php" title="Reports">
                                    <i class="bi bi-graph-up"></i> <span class="sidebar-text">Reports</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="sidebar-footer">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link logout-btn text-white" href="/lgu-urban-planning/logout.php" title="Logout">
                                <i class="bi bi-box-arrow-right text-white"></i> <span class="sidebar-text">Logout</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            <main class="main-content" style="flex: 1;">
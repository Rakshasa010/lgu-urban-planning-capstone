<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>LGU Urban Planning System - User Portal</title>
    <link rel="icon" type="image/x-icon" href="/lgu-urban-planning/assets/favicon.jpg" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap');
        
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        body, .sidebar, .main-content, .top-navbar {
        transition: background-color 0.3s ease, color 0.3s ease; }

        body {
            background: rgba(0, 0, 0, 0.35);
            background-attachment: fixed;
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
        }
        
        .top-navbar {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            backdrop-filter: blur(10px);
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            position: sticky;
            top: 0;
            z-index: 1050;
        }
        
        .top-navbar h5 {
            color: white !important;
            margin: 0;
            font-weight: 600;
        }
        
        .top-navbar .user-info {
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        /* --- SIDEBAR STYLE */
        .sidebar {
            position: fixed;
            top: 70px; 
            left: 0;
            width: 250px;
            height: calc(100vh - 70px);
            background: rgba(255, 255, 255, 0.795); 
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            box-shadow: 4px 0 25px rgba(0,0,0,0.15);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.3s ease;
            z-index: 1000;
            border-right: 1px solid rgba(255, 255, 255, 0.25);
        }
        
        .sidebar.collapsed {
            width: 80px;
        }
        
        .sidebar.collapsed .sidebar-text, 
        .sidebar.collapsed .badge,
        .sidebar.collapsed .sidebar-logo-container,
        .sidebar.collapsed .welcome-text {
            display: none;
        }
        
        /* Logo and Divider Styling */
        .sidebar-logo-container {
            padding: 20px;
            text-align: center;
        }

        .sidebar-logo-container img {
            max-width: 100px;
            height: auto;
            margin-bottom: 15px;
        }

        .sidebar-divider {
            height: 2px;
            background: rgba(0, 0, 0, 0.1);
            margin: 0 20px 20px 20px;
        }
        
        .sidebar-top {
            flex-grow: 1;
            overflow-y: auto;
            scrollbar-width: none;
        }
        
        .sidebar-top::-webkit-scrollbar {
            display: none;
        }

        .sidebar h4 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.25rem;
            color: #000000;
            padding: 0 20px;
            margin-bottom: 20px;
        }
        
        .sidebar .nav-list {
            list-style: none;
            padding: 0 15px;
            margin: 0;
        }

        .sidebar .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #000000;
            text-decoration: none;
            padding: 12px 15px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .sidebar .nav-link i {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }
        
        .sidebar .nav-link:hover {
            background: #97a4c2;
            transform: translateX(8px);
            color: #000;
        }
        
        .sidebar .nav-link.active {
            background: #3762c8;
            color: #fff;
            box-shadow: 0 4px 12px rgba(55, 98, 200, 0.3);
        }

        /* --- LOGOUT BUTTON AND WELCOME SA IBABA --- */
        .sidebar-footer {
            padding: 20px 15px;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .welcome-text {
            display: block;
            text-align: center;
            font-weight: 600;
            font-size: 0.9rem;
            color: #000000;
            margin-bottom: 10px;
        }

        .logout-btn-sidebar {
            background: #3762c8;
            color: #fff !important;
            padding: 10px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            transition: 0.3s;
            width: 100%;
            font-weight: 500;
        }

        .logout-btn-sidebar:hover {
            background: #285ccd;
            transform: translateY(-2px);
            color: #fff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .sidebar-toggle {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .sidebar-toggle:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .main-content {
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
            min-height: 100vh;
            margin-left: 250px;
            padding: 30px;
            transition: all 0.3s ease;
            flex: 1;
        }

        .sidebar.collapsed + .main-content {
            margin-left: 80px;
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
        }

        /* --- NOTIFICATION BELL STYLES --- */
        .notif-dropdown .dropdown-menu {
            width: 320px;
            border: none;
            box-shadow: 0 5px 25px rgba(0,0,0,0.2);
            border-radius: 12px;
            padding: 0;
            margin-top: 10px !important;
        }
        .notif-header {
            background: #f8f9fa;
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            border-radius: 12px 12px 0 0;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .notif-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f8f8f8;
            display: block;
            text-decoration: none;
            color: #333;
            transition: 0.2s;
        }
        .notif-item:hover { background: #f0f4ff; }
        .notif-item.unread { background: #edf2ff; border-left: 3px solid #3762c8; }
        .notif-footer {
            padding: 10px;
            text-align: center;
            border-top: 1px solid #eee;
        }

        @media (max-width: 768px) {
            .sidebar { margin-left: -250px; }
            .sidebar.show { margin-left: 0; }
            .main-content { margin-left: 0 !important; }
        }

        /* Dark Mode Overrides */
    [data-bs-theme="dark"] body { background: #121212 !important; }
    [data-bs-theme="dark"] .top-navbar { background: #1e293b !important; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
    [data-bs-theme="dark"] .sidebar { background: rgba(30, 30, 30, 0.9) !important; border-right: 1px solid rgba(255, 255, 255, 0.1); }
    [data-bs-theme="dark"] .main-content { background: #1a1a1a !important; color: #e0e0e0 !important; }
    [data-bs-theme="dark"] .sidebar .nav-link, [data-bs-theme="dark"] .sidebar h4, [data-bs-theme="dark"] .welcome-text { color: #ffffff !important; }
    [data-bs-theme="dark"] .sidebar-divider { background: rgba(255, 255, 255, 0.1); }
    [data-bs-theme="dark"] .notif-dropdown .dropdown-menu { background: #2d3748; color: white; }
    [data-bs-theme="dark"] .notif-header { background: #1a202c; color: white; border-bottom: 1px solid #4a5568; }
    [data-bs-theme="dark"] .notif-item { color: #cbd5e0; border-bottom: 1px solid #4a5568; }
    [data-bs-theme="dark"] .notif-item:hover { background: #2c5282; }

    </style>
    <script src="/lgu-urban-planning/assets/js/user.js"></script>
</head>
<body>
    <nav class="top-navbar">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                <h5 class="mb-0">Urban Planning and Development</h5>
            </div>
            <div class="user-info">
                    
                    <?php
                        $db = Database::getInstance();
                        $userId = $_SESSION['user_id'] ?? 0;
                        $notifCount = $db->fetchOne("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0", [$userId]);
                        $latestNotifs = $db->fetchAll("SELECT * FROM messages WHERE receiver_id = ? ORDER BY created_at DESC LIMIT 5", [$userId]);
                    ?>
                    <div class="dropdown notif-dropdown">
                        <button class="btn btn-link text-white me-2 p-0 position-relative" id="notifBell" data-bs-toggle="dropdown" aria-expanded="false" style="text-decoration: none;">
                            <i class="bi bi-bell" style="font-size: 1.2rem;"></i>
                            <?php if ($notifCount['count'] > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem; padding: 0.25em 0.4em;">
                                    <?php echo $notifCount['count']; ?>
                                </span>
                            <?php endif; ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="notifBell">
                            <div class="notif-header">Notifications</div>
                            <div style="max-height: 300px; overflow-y: auto;">
                                <?php if (empty($latestNotifs)): ?>
                                    <div class="p-3 text-center text-muted small">No notifications yet.</div>
                                <?php else: ?>
                                    <?php foreach ($latestNotifs as $n): ?>
                                        <a href="/lgu-urban-planning/applicant/messages.php" class="notif-item <?php echo $n['is_read'] == 0 ? 'unread' : ''; ?>">
                                            <div class="fw-bold small"><?php echo htmlspecialchars($n['subject']); ?></div>
                                            <div class="text-muted truncate small" style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <?php echo htmlspecialchars($n['message']); ?>
                                            </div>
                                            <small class="text-primary" style="font-size: 0.7rem;"><?php echo date('M d, h:i A', strtotime($n['created_at'])); ?></small>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="notif-footer">
                                <a href="/lgu-urban-planning/applicant/messages.php" class="small text-decoration-none fw-bold">View All Messages</a>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-link text-white me-3 p-0" onclick="toggleDarkMode()" title="Toggle Dark/Light Mode" style="text-decoration: none;">
                        <i id="themeIcon" class="bi bi-moon-stars" style="font-size: 1.2rem;"></i>
                    </button>

                <div class="text-end d-none d-md-block">
                    <div style="font-weight: 600; font-size: 0.85rem; line-height: 1.2;">
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
            <div class="sidebar-top">
                <div class="sidebar-logo-container">
                    <img src="../assets/img/lgu-logo.png" alt="LGU Logo">
                </div>
                <div class="sidebar-divider"></div>

                <ul class="nav-list">
                    <li>
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="/lgu-urban-planning/user/index.php">
                            <i class="bi bi-house-door"></i> <span class="sidebar-text">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link" href="/lgu-urban-planning/applicant/apply.php">
                            <i class="bi bi-file-earmark-plus"></i> <span class="sidebar-text">Submit Application</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link" href="/lgu-urban-planning/applicant/applications.php">
                            <i class="bi bi-list-ul"></i> <span class="sidebar-text">My Applications</span>
                        </a>
                    </li>
                    <li>
    <a class="nav-link" href="/lgu-urban-planning/applicant/messages.php">
        <i class="bi bi-envelope"></i> <span class="sidebar-text">Messages</span>
        <?php
            require_once __DIR__ . '/../modules/ApplicantSelfService/ApplicantController.php';
            $appController = new ApplicantController();
            $unread = $appController->getUnreadMessageCount();
        ?>
        <span id="sidebarNotifBadge" class="badge bg-danger ms-auto sidebar-text" 
              style="<?php echo ($unread > 0) ? '' : 'display: none;'; ?>">
            <?php echo ($unread > 0) ? $unread : ''; ?>
        </span>
    </a>
</li>
                </ul>
            </div>

            <div class="sidebar-footer">
                <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="/lgu-urban-planning/logout.php" class="logout-btn-sidebar">
                    <i class="bi bi-box-arrow-right"></i> <span class="sidebar-text">Logout</span>
                </a>
            </div>
        </nav>

        <main class="main-content">
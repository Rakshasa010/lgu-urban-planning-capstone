<?php
/**
 * Main Entry Point
 * - Logged-in users are redirected to their portal (admin or applicant)
 * - Everyone else sees the public landing page
 */

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only redirect if someone is actually logged in
if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
    $role = $_SESSION['role'];
    if ($role === 'applicant') {
        header('Location: /lgu-urban-planning/user/index.php');
    } else {
        header('Location: /lgu-urban-planning/admin/index.php');
    }
    exit;
}

// Not logged in — show the public landing page
require __DIR__ . '/landingpage.php';
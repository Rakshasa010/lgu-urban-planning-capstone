<?php
/**
 * Main Entry Point - Redirects to appropriate portal
 */

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Auth.php';

$auth = new Auth();
$auth->requireLogin();

// Redirect based on user role
$role = $_SESSION['role'];
if ($role === 'applicant') {
    header('Location: /lgu-urban-planning/user/index.php');
} else {
    header('Location: /lgu-urban-planning/admin/index.php');
}
exit;


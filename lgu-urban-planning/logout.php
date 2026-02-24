<?php
/**
 * Logout
 */

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Auth.php';

$auth = new Auth();
$auth->logout();

header('Location: /lgu-urban-planning/login.php');
exit;


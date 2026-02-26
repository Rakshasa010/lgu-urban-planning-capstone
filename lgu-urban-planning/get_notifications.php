<?php
session_start();
// I-adjust ang path depende kung nasaan ang core/Database.php mo
require_once __DIR__ . '/core/Database.php';

$db = Database::getInstance();
$userId = $_SESSION['user_id'] ?? 0;

if ($userId > 0) {
    // 1. Bilangin ang unread messages
    $notifData = $db->fetchOne("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0", [$userId]);
    
    // 2. Kunin ang huling 5 messages
    $messages = $db->fetchAll("SELECT * FROM messages WHERE receiver_id = ? ORDER BY created_at DESC LIMIT 5", [$userId]);

    // I-format ang oras para sa JavaScript
    foreach ($messages as &$m) {
        $m['formatted_date'] = date('M d, h:i A', strtotime($m['created_at']));
    }

    header('Content-Type: application/json');
    echo json_encode([
        'count' => (int)$notifData['count'],
        'messages' => $messages
    ]);
} else {
    echo json_encode(['count' => 0, 'messages' => []]);
}
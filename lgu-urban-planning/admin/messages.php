<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helper.php';

$auth = new Auth();
$auth->requireRole(['admin', 'zoning_officer', 'building_official', 'assessor', 'inspector']);

$db = Database::getInstance();
$error = '';
$success = '';

// Mark as read
if (isset($_GET['mark_read'])) {
    $db->query("UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?", [$_GET['mark_read'], $_SESSION['user_id']]);
    header('Location: /lgu-urban-planning/admin/messages.php');
    exit;
}

// Fetch messages sent to current user (staff)
$messages = $db->fetchAll(
    "SELECT m.*, 
            us.first_name AS sender_first_name, us.last_name AS sender_last_name, us.role AS sender_role,
            ur.first_name AS receiver_first_name, ur.last_name AS receiver_last_name,
            a.application_number
     FROM messages m
     LEFT JOIN users us ON m.sender_id = us.id
     LEFT JOIN users ur ON m.receiver_id = ur.id
     LEFT JOIN applications a ON m.application_id = a.id
     WHERE m.receiver_id = ?
     ORDER BY m.created_at DESC",
    [$_SESSION['user_id']]
);

$pageTitle = 'Messages';
include __DIR__ . '/../admin/header.php';
?>

<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-1">Messages</h2>
            <p class="text-muted mb-0">Inbox from applicants and system notifications</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if (empty($messages)): ?>
                <p class="text-muted mb-0">No messages.</p>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="mb-3 p-3 border rounded <?php echo !$msg['is_read'] ? 'bg-light' : ''; ?>">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong><?php echo htmlspecialchars(trim(($msg['sender_first_name'] ?? '') . ' ' . ($msg['sender_last_name'] ?? '')) ?: 'System'); ?></strong>
                                <?php if ($msg['application_number']): ?>
                                    <span class="badge bg-info ms-2"><?php echo htmlspecialchars($msg['application_number']); ?></span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted"><?php echo Helper::formatDateTime($msg['created_at']); ?></small>
                        </div>
                        <?php if ($msg['subject']): ?>
                            <div class="fw-bold mt-2"><?php echo htmlspecialchars($msg['subject']); ?></div>
                        <?php endif; ?>
                        <div class="mt-2"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                        <div class="mt-2 d-flex gap-2">
                            <?php if (!$msg['is_read']): ?>
                                <a href="?mark_read=<?php echo $msg['id']; ?>" class="btn btn-sm btn-outline-primary">Mark as Read</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../admin/footer.php'; ?>



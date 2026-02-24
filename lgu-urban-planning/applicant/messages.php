<?php
/**
 * Messages & Notifications
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helper.php';
require_once __DIR__ . '/../modules/ApplicantSelfService/ApplicantController.php';

$auth = new Auth();
$auth->requireRole('applicant');

$applicantController = new ApplicantController();
$applicationId = $_GET['application_id'] ?? null;
$filter = $_GET['filter'] ?? 'all'; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5; 
$offset = ($page - 1) * $limit;

$messagesData = $applicantController->getMessagesPaginated($applicationId, $filter, $limit, $offset);
$messages = $messagesData['items'];
$totalMessages = $messagesData['total'];
$totalPages = ceil($totalMessages / $limit);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiverId = $_POST['receiver_id'] ?? 0;
    $message = $_POST['message'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $appId = $_POST['application_id'] ?? null;
    
    if (empty($message)) {
        $error = 'Message is required';
    } else {
        $applicantController->sendMessage($receiverId, $message, $subject, $appId);
        header("Location: messages.php?filter=sent&success=1");
        exit;
    }
}

if (isset($_GET['mark_read'])) {
    $applicantController->markMessageAsRead($_GET['mark_read']);
    header('Location: messages.php?filter=' . $filter . '&page=' . $page);
    exit;
}

$pageTitle = 'Messages';
include __DIR__ . '/../user/header.php';
?>

<div class="p-4">
    <h2 class="mb-4 fw-bold text-body">Messages & Notifications</h2>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> Message sent successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center border-bottom bg-transparent">
                    <h5 class="mb-2 mb-sm-0 fw-bold text-primary">
                        <i class="bi bi-envelope-paper me-2"></i>
                        <?php 
                            if($filter === 'sent') echo 'Sent Messages';
                            elseif($filter === 'unread') echo 'Unread Inbox';
                            elseif($filter === 'read') echo 'Read Inbox';
                            else echo 'Inbox (All)';
                        ?>
                    </h5>
                    
                    <div class="btn-group btn-group-sm shadow-sm">
                        <a href="?filter=all" class="btn btn-outline-primary <?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
                        <a href="?filter=unread" class="btn btn-outline-primary <?php echo $filter === 'unread' ? 'active' : ''; ?>">Unread</a>
                        <a href="?filter=read" class="btn btn-outline-primary <?php echo $filter === 'read' ? 'active' : ''; ?>">Read</a>
                        <a href="?filter=sent" class="btn btn-outline-primary <?php echo $filter === 'sent' ? 'active' : ''; ?>">Sent</a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($messages)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-chat-left-dots text-muted opacity-50" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2">No messages found here.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="mb-3 p-3 border rounded shadow-sm <?php echo (!$msg['is_read'] && $filter !== 'sent') ? 'bg-body-tertiary border-primary' : 'bg-body'; ?>" 
                                 style="border-left: 5px solid <?php echo (!$msg['is_read'] && $filter !== 'sent') ? '#0d6efd' : 'var(--bs-border-color)'; ?> !important;">
                                
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong class="text-body">
                                            <?php if ($filter === 'sent'): ?>
                                                To: <?php echo htmlspecialchars($msg['receiver_name'] ?? 'Officer'); ?>
                                            <?php else: ?>
                                                From: <?php echo htmlspecialchars($msg['sender_first_name'] . ' ' . $msg['sender_last_name']); ?>
                                            <?php endif; ?>
                                        </strong>
                                        <?php if ($msg['application_number']): ?>
                                            <span class="badge bg-info text-dark ms-1"><?php echo htmlspecialchars($msg['application_number']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted"><?php echo Helper::formatDateTime($msg['created_at']); ?></small>
                                </div>

                                <div class="fw-bold mt-2 text-body"><?php echo htmlspecialchars($msg['subject'] ?: '(No Subject)'); ?></div>
                                <div class="mt-1 text-body-secondary" style="font-size: 0.95rem;"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                
                                <?php if (!$msg['is_read'] && $filter !== 'sent'): ?>
                                    <div class="mt-3 text-end">
                                        <a href="?mark_read=<?php echo $msg['id']; ?>&filter=<?php echo $filter; ?>&page=<?php echo $page; ?>" 
                                           class="btn btn-sm btn-primary">
                                           <i class="bi bi-check2-all me-1"></i> Mark as Read
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <?php if ($totalPages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination pagination-sm justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link shadow-none" href="?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                        <a class="page-link shadow-none" href="?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link shadow-none" href="?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mt-4 mt-md-0">
            <div class="card shadow-sm border-0 bg-body">
                <div class="card-header bg-primary text-white py-3 border-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2"></i>New Message</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-body">To (Officer)</label>
                            <select class="form-select bg-body text-body border-secondary-subtle" name="receiver_id" required>
                                <option value="">Select Officer</option>
                                <?php
                                $db = Database::getInstance();
                                $officers = $db->fetchAll("SELECT id, first_name, last_name, role FROM users WHERE role IN ('zoning_officer', 'building_official', 'admin') AND is_active = 1");
                                foreach ($officers as $off): ?>
                                    <option value="<?php echo $off['id']; ?>"><?php echo htmlspecialchars($off['first_name'].' '.$off['last_name'].' ('.Helper::getRoleName($off['role']).')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-body">Subject</label>
                            <input type="text" class="form-control bg-body text-body border-secondary-subtle" name="subject" placeholder="Application Inquiry">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-body">Message</label>
                            <textarea class="form-control bg-body text-body border-secondary-subtle" name="message" rows="5" required placeholder="Type your message..."></textarea>
                        </div>
                        <button type="submit" name="send_message" class="btn btn-primary w-100 fw-bold">
                            <i class="bi bi-send me-2"></i>Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../user/footer.php'; ?>
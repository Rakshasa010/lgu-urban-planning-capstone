<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../core/Database.php';

$pageTitle = 'Dashboard';
$db = Database::getInstance();

// 1. FRESH DATA FETCH
if (isset($_SESSION['user_id'])) {
    $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    $_SESSION['user_data'] = $user;
}
$user = $_SESSION['user_data'] ?? null;
$userId = $_SESSION['user_id'] ?? 0;

// --- SERVICE MONITORING LOGIC ---
date_default_timezone_set('Asia/Manila');
$currentDay = date('N'); // 1 (Mon) to 7 (Sun)
$currentDate = date('Y-m-d');
$currentTime = date('H:i');

// Listahan ng Regular Holidays sa Pilipinas (Y-m-d)
$holidays = [
    date('Y') . "-01-01", // New Year's Day
    date('Y') . "-04-09", // Araw ng Kagitingan
    date('Y') . "-05-01", // Labor Day
    date('Y') . "-06-12", // Independence Day
    date('Y') . "-08-25", // National Heroes Day
    date('Y') . "-11-01", // All Saints Day
    date('Y') . "-12-25", // Christmas Day
    date('Y') . "-12-30", // Rizal Day
];

$isWeekend = ($currentDay >= 6); 
$isHoliday = in_array($currentDate, $holidays);
$isOfficeHours = ($currentTime >= '08:00' && $currentTime <= '17:00');

$isOpen = (!$isWeekend && !$isHoliday && $isOfficeHours);

if ($isHoliday) {
    $statusMsg = "Closed (Holiday)";
    $statusColor = "text-danger";
} elseif ($isWeekend) {
    $statusMsg = "Closed (Weekend)";
    $statusColor = "text-danger";
} elseif (!$isOfficeHours) {
    $statusMsg = "Closed (Outside Office Hours)";
    $statusColor = "text-warning";
} else {
    $statusMsg = "Open Now (Accepting Applications)";
    $statusColor = "text-success";
}

// 2. FETCH DASHBOARD STATS & INSPECTIONS
try {
    $myApps = $db->fetchAll("SELECT * FROM applications WHERE applicant_id = ? ORDER BY created_at DESC", [$userId]);
    
    $inspections = $db->fetchAll("SELECT i.*, ap.project_name, ap.application_number 
                                   FROM inspections i 
                                   JOIN applications ap ON i.application_id = ap.id 
                                   WHERE ap.applicant_id = ? AND i.status != 'cancelled'
                                   ORDER BY i.scheduled_at ASC", [$userId]);

    $apptDates = [];
    foreach ($inspections as $ins) {
        $dateKey = date('Y-m-d', strtotime($ins['scheduled_at']));
        $apptDates[$dateKey][] = [
            'id' => $ins['id'],
            'project_name' => $ins['project_name'],
            'application_number' => $ins['application_number'],
            'scheduled_at' => $ins['scheduled_at']
        ];
    }
} catch (Exception $e) {
    $myApps = $myApps ?? [];
    $inspections = [];
    $apptDates = [];
}

$unreadMsgs = $db->fetchOne("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0", [$userId]);

$dashboardData = [
    'my_applications' => $myApps,
    'unread_messages' => $unreadMsgs['count'] ?? 0
];

// 3. CALENDAR CALCULATIONS
$month = date('m');
$year = date('Y');
$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDayOfMonth);
$firstDayOfWeek = date('w', $firstDayOfMonth);
?>

<style>
    .calendar-table { table-layout: fixed; width: 100%; }
    .calendar-table th { font-size: 0.7rem; color: #adb5bd; text-transform: uppercase; text-align: center; }
    .calendar-day { 
        height: 40px; text-align: center; vertical-align: middle; 
        cursor: pointer; font-size: 0.85rem; border-radius: 8px; transition: all 0.2s;
    }
    .calendar-day:hover { background-color: #f8f9fa; color: #0d6efd; }
    .calendar-day.has-appt { 
        background-color: #0d6efd !important; color: white !important; 
        font-weight: bold; border: 2px solid #0056b3;
    }
    .calendar-day.today { border-bottom: 3px solid #dc3545; }
    
    /* Status Card Animation */
    .status-dot { animation: blink 2s infinite; }
    @keyframes blink { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }
</style>

<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-1" style="font-weight: 700;">My Dashboard</h2>
        <div class="badge bg-primary shadow-sm" style="font-size: 0.9rem; padding: 10px 20px;">
            <i class="bi bi-calendar3 me-2"></i><?php echo date('F d, Y'); ?>
        </div>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] == 'rescheduled'): ?>
    <div class="alert alert-info border-0 shadow-sm mb-4 alert-dismissible fade show">
        <i class="bi bi-info-circle-fill me-2"></i>
        Your reschedule request has been sent to the Admin/Official inbox.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

        <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="card-body p-0">
                    <div class="d-flex align-items-center">
                        <div class="p-4 <?php echo $isOpen ? 'bg-success' : 'bg-secondary'; ?> text-white">
                            <i class="bi <?php echo $isOpen ? 'bi-door-open-fill' : 'bi-door-closed-fill'; ?>" style="font-size: 1.8rem;"></i>
                        </div>
                        <div class="p-3 flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <div>
                                    <h6 class="mb-1 fw-bold text-muted small text-uppercase">Office Service Status</h6>
                                    <h5 class="mb-0 fw-bold <?php echo $statusColor; ?>">
                                        <i class="bi bi-circle-fill me-2 status-dot" style="font-size: 0.7rem;"></i>
                                        <?php echo $statusMsg; ?>
                                    </h5>
                                </div>
                                <div class="text-md-end mt-2 mt-md-0">
                                    <span class="badge bg-light text-dark border">Mon - Fri</span>
                                    <span class="badge bg-light text-dark border">8:00 AM - 5:00 PM</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4 g-4">
        <div class="col-md-6">
            <div class="card text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-white-50 mb-2">My Applications</h6>
                            <h2 class="mb-0 fw-bold"><?php echo count($dashboardData['my_applications']); ?></h2>
                        </div>
                        <i class="bi bi-file-earmark-text opacity-50" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-white-50 mb-2">Unread Messages</h6>
                            <h2 class="mb-0 fw-bold"><?php echo $dashboardData['unread_messages']; ?></h2>
                        </div>
                        <i class="bi bi-envelope opacity-50" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Site Inspections</h5>
                    <small class="fw-bold text-muted"><?php echo date('F Y'); ?></small>
                </div>
                <div class="card-body p-3">
                    <table class="table table-sm table-borderless calendar-table mb-3">
                        <thead>
                            <tr>
                                <th>Su</th><th>Mo</th><th>Tu</th><th>We</th><th>Th</th><th>Fr</th><th>Sa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            echo "<tr>";
                            for ($i = 0; $i < $firstDayOfWeek; $i++) echo "<td></td>";

                            for ($day = 1; $day <= $daysInMonth; $day++) {
                                if (($i + $day - 1) % 7 == 0 && $day != 1) echo "</tr><tr>";
                                
                                $dateStr = "$year-$month-" . str_pad($day, 2, "0", STR_PAD_LEFT);
                                $hasAppt = isset($apptDates[$dateStr]) ? 'has-appt' : '';
                                $isToday = ($dateStr == date('Y-m-d')) ? 'today' : '';
                                
                                echo "<td class='calendar-day $hasAppt $isToday' onclick='showApptDetail(\"$dateStr\")'>$day</td>";
                            }
                            echo "</tr>";
                            ?>
                        </tbody>
                    </table>

                    <div id="appt-detail-panel" class="p-3 border rounded-3 bg-light shadow-sm" style="display:none;">
                        <h6 class="fw-bold small mb-2 text-uppercase text-muted border-bottom pb-1">Inspection Details</h6>
                        <div id="appt-info-content"></div>
                    </div>

                    <div id="no-appt-msg" class="text-center py-4">
                        <p class="text-muted small mb-0">Select a highlighted date to view details.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header py-3 border-bottom">
                    <h5 class="mb-0 fw-bold">Recent Applications</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Application #</th>
                                    <th>Project Name</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($dashboardData['my_applications'])): ?>
                                    <tr><td colspan="4" class="text-center py-4">No applications found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($dashboardData['my_applications'] as $app): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-primary"><?php echo htmlspecialchars($app['application_number']); ?></td>
                                        <td><?php echo htmlspecialchars($app['project_name']); ?></td>
                                        <td>
                                            <?php 
                                                $statusClass = ($app['status'] == 'approved') ? 'bg-success' : (($app['status'] == 'pending') ? 'bg-warning' : 'bg-primary');
                                            ?>
                                            <span class="badge rounded-pill <?php echo $statusClass; ?>">
                                                <?php echo ucfirst($app['status']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="/lgu-urban-planning/applicant/view.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-outline-primary px-3">View</a>                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rescheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="process_reschedule.php" method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold">Request Reschedule</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="appointment_id" id="modal_appt_id">
                <p class="mb-3">Requesting reschedule for: <b id="modal_app_num" class="text-danger"></b></p>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Preferred New Date</label>
                    <input type="date" name="new_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="mb-0">
                    <label class="form-label fw-bold small">Reason for Rescheduling</label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="State your reason here..." required></textarea>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger px-4 fw-bold">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<script>
    const apptData = <?php echo json_encode($apptDates ?? []); ?>;

    function showApptDetail(date) {
        const details = apptData[date];
        const panel = document.getElementById('appt-detail-panel');
        const content = document.getElementById('appt-info-content');
        const noApptMsg = document.getElementById('no-appt-msg');
        
        if (!details) {
            panel.style.display = 'none';
            if(noApptMsg) noApptMsg.style.display = 'block';
            return;
        }

        if(noApptMsg) noApptMsg.style.display = 'none';
        panel.style.display = 'block';
        content.innerHTML = details.map(a => `
            <div class="mb-3 p-2 bg-white rounded border-start border-3 border-primary">
                <div class="text-primary fw-bold mb-1" style="font-size: 0.9rem;">${a.project_name}</div>
                <div class="text-muted small mb-2">
                    <i class="bi bi-clock-fill me-1"></i>
                    ${new Date(a.scheduled_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', hour12: true})}
                </div>
                <button onclick="openRescheduleModal(${a.id}, '${a.application_number}')" 
                        class="btn btn-sm btn-danger w-100 py-1 fw-bold shadow-sm" style="font-size: 0.75rem;">
                    Request Reschedule
                </button>
            </div>
        `).join('');
    }

    function openRescheduleModal(id, appNum) {
        document.getElementById('modal_appt_id').value = id;
        document.getElementById('modal_app_num').innerText = appNum;
        new bootstrap.Modal(document.getElementById('rescheduleModal')).show();
    }
</script>
<?php
$pageTitle = 'Dashboard';

// 1. PERFORMANCE METRICS (NEW)
// Average Processing Time (Days from creation to decision)
$avgTimeResult = $db->fetchOne("SELECT ROUND(AVG(DATEDIFF(updated_at, created_at)), 1) as avg_days 
                                FROM applications 
                                WHERE status IN ('approved', 'rejected')");
$avgProcessingTime = $avgTimeResult['avg_days'] ?? '0';

// Top Performing Barangay (Most applications in total)
$topBrgyResult = $db->fetchOne("SELECT barangay, COUNT(*) as count 
                                FROM applications 
                                GROUP BY barangay 
                                ORDER BY count DESC LIMIT 1");
$topBarangay = $topBrgyResult['barangay'] ?? 'No Data';

// Upcoming Inspections (Next 7 days)
$upcomingInspections = $db->fetchOne("SELECT COUNT(*) as total FROM inspections 
                                      WHERE scheduled_at >= CURDATE() 
                                      AND scheduled_at <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                                      AND status = 'scheduled'");
$inspectionCount = $upcomingInspections['total'] ?? 0;

// 2. STATUS CARDS DATA
$overdueResult = $db->fetchOne("SELECT COUNT(*) as total FROM applications 
                                WHERE (status = 'submitted' OR status = 'Pending') 
                                AND created_at <= DATE_SUB(NOW(), INTERVAL 3 DAY)");
$overdueCount = $overdueResult['total'] ?? 0;

$stats = $db->fetchOne("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'submitted' OR status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review,
    SUM(CASE WHEN status = 'for_revision' THEN 1 ELSE 0 END) as for_revision
    FROM applications");

$dashboardData['stats'] = [
    'total_applications' => $stats['total'] ?? 0,
    'pending_review' => $stats['pending'] ?? 0,
    'approved' => $stats['approved'] ?? 0,
    'rejected' => $stats['rejected'] ?? 0
];

// 3. CHART DATA FETCHING
$statusLabels = ['Approved', 'Pending', 'Under Review', 'Revision', 'Rejected'];
$statusCounts = [
    $stats['approved'] ?? 0, 
    $stats['pending'] ?? 0, 
    $stats['under_review'] ?? 0, 
    $stats['for_revision'] ?? 0, 
    $stats['rejected'] ?? 0
];

$landUseData = $db->fetchAll("SELECT project_type, COUNT(*) as total FROM applications WHERE project_type IS NOT NULL GROUP BY project_type");
$landLabels = array_column($landUseData, 'project_type');
$landCounts = array_column($landUseData, 'total');

$barangayData = $db->fetchAll("SELECT barangay, COUNT(*) as total FROM applications GROUP BY barangay ORDER BY total DESC LIMIT 10");
$brgyLabels = array_column($barangayData, 'barangay');
$brgyCounts = array_column($barangayData, 'total');

$monthlyData = $db->fetchAll("SELECT MONTHNAME(created_at) as month, COUNT(*) as total FROM applications WHERE YEAR(created_at) = YEAR(CURDATE()) GROUP BY MONTH(created_at) ORDER BY MONTH(created_at)");
$monthLabels = array_column($monthlyData, 'month');
$monthCounts = array_column($monthlyData, 'total');

// 4. RECENT APPLICATIONS & FILTERS
$filters = ['status' => $_GET['status'] ?? '', 'search' => $_GET['search'] ?? ''];

if ($_SESSION['role'] === 'inspector') {
    // Para kay Inspector: Kunin lang ang mga applications na naka-assign sa kanya sa 'inspections' table
    $sql = "SELECT a.*, u.first_name, u.last_name 
            FROM applications a 
            LEFT JOIN users u ON a.applicant_id = u.id 
            JOIN inspections i ON a.id = i.application_id 
            WHERE i.inspector_id = ? 
            ORDER BY a.created_at DESC LIMIT 10";
    $recentApps = $db->fetchAll($sql, [$_SESSION['user_id']]);
} else {
    // Para sa Admin/Zoning/Staff: Ito ang original logic mo
    $sql = "SELECT a.*, u.first_name, u.last_name FROM applications a LEFT JOIN users u ON a.applicant_id = u.id WHERE 1=1";
    $params = [];
    if (!empty($filters['status'])) { $sql .= " AND a.status = ?"; $params[] = $filters['status']; }
    if (!empty($filters['search'])) {
        $sql .= " AND (a.project_name LIKE ? OR a.application_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
        $val = "%".$filters['search']."%"; $params = array_merge($params, [$val, $val, $val, $val]);
    }
    $sql .= " ORDER BY a.created_at DESC LIMIT 10";
    $recentApps = $db->fetchAll($sql, $params);
}

// 5. ANNOUNCEMENT SETTINGS
$db_raw = Database::getInstance()->getConnection();
$stmt = $db_raw->query("SELECT * FROM system_settings WHERE setting_key = 'system_announcement' LIMIT 1");
$sys_settings = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<style>
    .overdue-alert { background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%); }
    .chart-card { border-radius: 15px; border: none; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
    .chart-container { position: relative; height: 280px; width: 100%; }
    .metric-card { border-radius: 15px; transition: transform 0.2s; }
    .metric-card:hover { transform: translateY(-5px); }
</style>

<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0" style="color: #1e293b;">Admin Dashboard</h2>
            <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></p>
        </div>
        <div class="badge bg-primary p-2 px-3"><i class="bi bi-calendar3 me-2"></i><?php echo date('F d, Y'); ?></div>
    </div>

<?php if ($_SESSION['role'] !== 'inspector'): ?>
    <div class="row mb-4 g-4">
        <?php 
        $cards = [
            ['Total Applications', $dashboardData['stats']['total_applications'], 'bi-file-earmark-text', 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)'],
            ['Pending Review', $dashboardData['stats']['pending_review'], 'bi-clock-history', 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)'],
            ['Approved', $dashboardData['stats']['approved'], 'bi-check-circle', 'linear-gradient(135deg, #10b981 0%, #059669 100%)'],
            ['Rejected', $dashboardData['stats']['rejected'], 'bi-x-circle', 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)']
        ];
        foreach($cards as $card): ?>
        <div class="col-md-3">
            <div class="card text-white shadow-sm border-0 metric-card" style="background: <?php echo $card[3]; ?>;">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-white-50 mb-2" style="font-weight: 500;"><?php echo $card[0]; ?></h6>
                            <h2 class="text-black mb-0" style="font-weight: 700;"><?php echo $card[1]; ?></h2>
                        </div>
                        <i class="bi <?php echo $card[2]; ?>" style="font-size: 1.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-primary border-0 shadow-sm">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Welcome, Inspector! Check your assigned applications below.</h5>
            </div>
        </div>
    </div>
<?php endif; ?>

    <div class="row mb-4 g-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-white" style="border-radius: 15px; border-left: 5px solid #6366f1;">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3"><i class="bi bi-speedometer2 text-primary fs-4"></i></div>
                    <div><h6 class="text-muted mb-1">Avg. Processing Time</h6><h4 class="fw-bold mb-0"><?php echo $avgProcessingTime; ?> Days</h4></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-white" style="border-radius: 15px; border-left: 5px solid #8b5cf6;">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-purple bg-opacity-10 p-3 me-3" style="background: rgba(139,92,246,0.1);"><i class="bi bi-trophy text-purple fs-4" style="color:#8b5cf6;"></i></div>
                    <div><h6 class="text-muted mb-1">Top Performing Barangay</h6><h4 class="fw-bold mb-0"><?php echo htmlspecialchars($topBarangay); ?></h4></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-white" style="border-radius: 15px; border-left: 5px solid #ec4899;">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-pink bg-opacity-10 p-3 me-3" style="background: rgba(236,72,153,0.1);"><i class="bi bi-calendar-check text-pink fs-4" style="color:#ec4899;"></i></div>
                    <div><h6 class="text-muted mb-1">Upcoming Inspections</h6><h4 class="fw-bold mb-0"><?php echo $inspectionCount; ?></h4></div>
                </div>
            </div>
        </div>
    </div>

        <?php if ($overdueCount > 0): ?>
    <div class="card mb-4 border-0 shadow-sm overdue-alert" style="border-radius: 15px; border-left: 5px solid #ef4444 !important;">
        <div class="card-body p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3">
                    <i class="bi bi-exclamation-octagon-fill text-danger fs-3"></i>
                </div>
                <div>
                    <h5 class="mb-1 fw-bold text-danger">Priority Action Required</h5>
                    <p class="mb-0 alert-text">
                        There are <strong><?php echo $overdueCount; ?></strong> applications pending for more than 3 days.
                    </p>
                </div>
            </div>
            <a href="/lgu-urban-planning/permit/applications.php?status=submitted&filter=overdue" class="btn btn-danger px-4 shadow-sm" style="border-radius: 10px; font-weight: 600;">
                Review Overdue <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-megaphone me-2 text-primary"></i>System Announcement</h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="updateStatus" <?php echo ($sys_settings['is_active'] ?? false) ? 'checked' : ''; ?> onchange="updateAnnouncementStatus(this.checked)">
                    <label class="form-check-label">Live Banner</label>
                </div>
            </div>
            <div class="input-group">
                <input type="text" id="announcementText" class="form-control" value="<?php echo htmlspecialchars($sys_settings['setting_value'] ?? ''); ?>">
                <button class="btn btn-primary px-4" onclick="saveAnnouncementText()">Update</button>
            </div>
        </div>
    </div>
    

        <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
        <div class="card-header py-3 border-0 custom-card-header">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col">
                    <h5 class="mb-0 fw-bold">Recent Applications</h5>
                </div>
                
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text border-0 custom-input-accent"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control form-control-sm bg-light border-0" 
                            placeholder="Search ID, Project, or Applicant..." 
                            value="<?php echo htmlspecialchars($filters['search']); ?>">
                    </div>
                </div>

                <div class="col-md-3">
                    <select class="form-select form-select-sm bg-light border-0" name="status" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <?php 
                        $opts = ['submitted', 'under_review', 'for_revision', 'approved', 'rejected'];
                        foreach($opts as $opt): ?>
                            <option value="<?php echo $opt; ?>" <?php echo $filters['status'] === $opt ? 'selected' : ''; ?>>
                                <?php echo ucfirst(str_replace('_', ' ', $opt)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if($filters['status'] || $filters['search']): ?>
                <div class="col-auto">
                    <a href="index.php" class="btn btn-sm btn-link text-muted text-decoration-none">Reset</a>
                </div>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted">
                    <tr style="font-size: 0.8rem;">
                        <th class="ps-4">APPLICATION #</th>
                        <th>PROJECT NAME</th>
                        <th>APPLICANT</th>
                        <th>STATUS</th>
                        <th>DATE</th>
                        <th class="text-end pe-4">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentApps)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">No applications found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentApps as $app): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary">#<?php echo htmlspecialchars($app['application_number']); ?></td>
                            <td><?php echo htmlspecialchars($app['project_name']); ?></td>
                            <td><?php echo htmlspecialchars(($app['first_name'] ?? '') . ' ' . ($app['last_name'] ?? '')); ?></td>
                            <td>
                                <span class="badge rounded-pill bg-<?php echo Helper::getStatusBadge($app['status']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?>
                                </span>
                            </td>
                            <td class="text-muted"><?php echo Helper::formatDate($app['created_at']); ?></td>
                            <td class="text-end pe-4">
                                <a href="/lgu-urban-planning/permit/view.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-outline-primary px-3">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($_SESSION['role'] !== 'inspector'): ?>
    <div class="row mb-4 g-4">
        <div class="col-md-6">
            <div class="card chart-card p-4">
                <h5 class="fw-bold mb-4">Application Status</h5>
                <div class="chart-container"><canvas id="statusPieChart"></canvas></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card chart-card p-4">
                <h5 class="fw-bold mb-4">Project Categories</h5>
                <div class="chart-container"><canvas id="landUsePieChart"></canvas></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card chart-card p-4">
                <h5 class="fw-bold mb-4">Applications per Barangay</h5>
                <div class="chart-container"><canvas id="barangayChart"></canvas></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card chart-card p-4">
                <h5 class="fw-bold mb-4">Monthly Growth Trend</h5>
                <div class="chart-container"><canvas id="trendChart"></canvas></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1060;">
    <div id="successToast" class="toast align-items-center text-white bg-success border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-check-circle-fill me-2"></i> 
                <span id="toastMessage">System settings updated successfully.</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const commonOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } };

// Status Pie
new Chart(document.getElementById('statusPieChart'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($statusLabels); ?>,
        datasets: [{ data: <?php echo json_encode($statusCounts); ?>, backgroundColor: ['#10b981', '#f59e0b', '#3b82f6', '#8b5cf6', '#ef4444'] }]
    },
    options: commonOptions
});

// Project Pie
new Chart(document.getElementById('landUsePieChart'), {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(!empty($landLabels) ? $landLabels : ['No Data']); ?>,
        datasets: [{ data: <?php echo json_encode(!empty($landCounts) ? $landCounts : [1]); ?>, backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#6366f1'] }]
    },
    options: commonOptions
});

// Barangay Bar
new Chart(document.getElementById('barangayChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($brgyLabels); ?>,
        datasets: [{ label: 'Total', data: <?php echo json_encode($brgyCounts); ?>, backgroundColor: '#3b82f6', borderRadius: 5 }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

// Trend Line
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($monthLabels); ?>,
        datasets: [{ label: 'Applications', data: <?php echo json_encode($monthCounts); ?>, borderColor: '#3b82f6', tension: 0.4, fill: true, backgroundColor: 'rgba(59, 130, 246, 0.1)' }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

// Announcement Logic
function saveAnnouncementText() { 
    updateData(
        document.getElementById('announcementText').value, 
        document.getElementById('updateStatus').checked ? 1 : 0,
        'Announcement text updated successfully.'
    ); 
}

function updateAnnouncementStatus(status) { 
    updateData(
        document.getElementById('announcementText').value, 
        status ? 1 : 0,
        status ? 'Announcement banner is now LIVE.' : 'Announcement banner has been disabled.'
    ); 
}

function updateData(t, a, successMsg) {
    const fd = new FormData(); 
    fd.append('setting_value', t); 
    fd.append('is_active', a);
    
    fetch('update_settings.php', { 
        method: 'POST', 
        body: fd 
    })
    .then(r => r.json())
    .then(d => { 
        if(d.success) {
            // Update the toast message text
            document.getElementById('toastMessage').innerText = successMsg;
            
            // Show Bootstrap Toast
            const toastEl = document.getElementById('successToast');
            const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
            toast.show();
        } else {
            // Optional: Error handling
            console.error('Update failed');
        }
    })
    .catch(err => console.error('Error:', err));
}
</script>
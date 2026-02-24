<?php

// Reports & Analytics

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helper.php';
require_once __DIR__ . '/../modules/DocumentReportManagement/DocumentController.php';

$auth = new Auth();
$auth->requirePermission('generate_reports');
$auth->requireRole(['admin', 'zoning_officer']);

$documentController = new DocumentController();
$report = null;
$error = '';

// --- INITIALIZE CHART DATA ---
$chartData = [
    'status' => ['Approved' => 0, 'Rejected' => 0, 'Pending' => 0],
    'months' => ['Jan'=>0, 'Feb'=>0, 'Mar'=>0, 'Apr'=>0, 'May'=>0, 'Jun'=>0, 'Jul'=>0, 'Aug'=>0, 'Sep'=>0, 'Oct'=>0, 'Nov'=>0, 'Dec'=>0],
    'barangays' => [],
    'yoy_comparison' => ['current' => 0, 'previous' => 0]
];

// --- PAGINATION SETTINGS ---
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;

if (isset($_REQUEST['report_type'])) {
    $reportType = $_REQUEST['report_type'];
    $selectedYear = !empty($_REQUEST['year']) ? (int)$_REQUEST['year'] : (int)date('Y');
    
    $filters = [];
    if (!empty($_REQUEST['date_from'])) $filters['date_from'] = $_REQUEST['date_from'];
    if (!empty($_REQUEST['date_to'])) $filters['date_to'] = $_REQUEST['date_to'];
    $filters['year'] = $selectedYear;
    
    $report = $documentController->generateReport($reportType, $filters);
    $isValid = (is_array($report) && !empty($report['data']));

    if (!$isValid) {
        $error = "Notice: " . ($report['error'] ?? 'No records found for the selected year.');
        $report = null; 
    } else {
        // 1. Get Previous Year Data for Comparison
        $prevFilters = $filters;
        $prevFilters['year'] = $selectedYear - 1;
        $prevYearReport = $documentController->generateReport($reportType, $prevFilters);
        $chartData['yoy_comparison']['current'] = count($report['data']);
        $chartData['yoy_comparison']['previous'] = (is_array($prevYearReport) && isset($prevYearReport['data'])) ? count($prevYearReport['data']) : 0;

        foreach ($report['data'] as $row) {
            $s = strtolower($row['status'] ?? '');
            if ($s === 'approved') $chartData['status']['Approved']++;
            elseif ($s === 'rejected') $chartData['status']['Rejected']++;
            else $chartData['status']['Pending']++;

            $dateKey = $row['created_at'] ?? $row['date_issued'] ?? '';
            if ($dateKey) {
                $m = date('M', strtotime($dateKey));
                if (isset($chartData['months'][$m])) $chartData['months'][$m]++;
            }

            $brgy = $row['barangay'] ?? '';
            if ($brgy) {
                $chartData['barangays'][$brgy] = ($chartData['barangays'][$brgy] ?? 0) + 1;
            }
        }
        arsort($chartData['barangays']);
        $chartData['barangays'] = array_slice($chartData['barangays'], 0, 5);

        $totalItems = count($report['data']);
        $totalPages = ceil($totalItems / $itemsPerPage);
        $offset = ($currentPage - 1) * $itemsPerPage;
        $allDataForExport = $report['data']; 
        $report['data'] = array_slice($allDataForExport, $offset, $itemsPerPage); 
    }
}

$pageTitle = 'Reports & Analytics';
include __DIR__ . '/../admin/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .report-main-grid { display: grid; grid-template-columns: 350px 1fr; gap: 1.5rem; align-items: start; }
    .report-display-area { min-width: 0; }
    .table-container-fixed { width: 100%; overflow-x: auto; border: 1px solid #e3e6f0; border-radius: 8px; background: white; }
    .permits-table { table-layout: fixed; width: 100%; min-width: 1000px; margin-bottom: 0; }
    .permits-table th, .permits-table td { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding: 12px; }
    .empty-report-state { background: #fff; border: 2px dashed #e3e6f0; border-radius: 15px; padding: 100px 20px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #858796; }
    .analytics-section { margin-top: 2rem; }
    .chart-card-container { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e3e6f0; height: 100%; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .pagination .page-link { color: #2d5a27; border: 1px solid #dee2e6; margin: 0 2px; border-radius: 4px; }
    .pagination .page-item.active .page-link { background-color: #2d5a27; border-color: #2d5a27; color: white; }
    .pagination .page-item.disabled .page-link { color: #6c757d; background-color: #f8f9fa; }
    @media (max-width: 992px) { .report-main-grid { grid-template-columns: 1fr; } }
    .table-dark-header { background-color: #f8f9fa; }
    [data-bs-theme="dark"] .table-dark-header { background-color: #0f172a !important; }
</style>

<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0 fw-bold text-dark">Reports & Analytics</h2>
        <span class="badge bg-primary px-3 py-2 rounded-pill">System Date: <?php echo date('M d, Y'); ?></span>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-info alert-dismissible fade show border-0 shadow-sm rounded-3">
            <i class="bi bi-info-circle-fill me-2"></i>
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="report-main-grid">
        <div class="filter-sidebar">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-filter-left me-2 text-primary"></i>Generate Report</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Report Type</label>
                            <select class="form-select shadow-sm" name="report_type" required>
                                <option value="applications_summary" <?php echo (isset($_REQUEST['report_type']) && $_REQUEST['report_type'] == 'applications_summary') ? 'selected' : ''; ?>>Applications Summary</option>
                                <option value="permits_issued" <?php echo (isset($_REQUEST['report_type']) && $_REQUEST['report_type'] == 'permits_issued') ? 'selected' : ''; ?>>Permits Issued</option>
                                <option value="zoning_compliance" <?php echo (isset($_REQUEST['report_type']) && $_REQUEST['report_type'] == 'zoning_compliance') ? 'selected' : ''; ?>>Zoning Compliance Report</option>
                                <option value="inspector_performance" <?php echo (isset($_REQUEST['report_type']) && $_REQUEST['report_type'] == 'inspector_performance') ? 'selected' : ''; ?>>Inspector Performance</option>
                                <option value="audit_summary" <?php echo (isset($_REQUEST['report_type']) && $_REQUEST['report_type'] == 'audit_summary') ? 'selected' : ''; ?>>Audit Summary</option>
                                <option value="user_growth" <?php echo (isset($_REQUEST['report_type']) && $_REQUEST['report_type'] == 'user_growth') ? 'selected' : ''; ?>>User Growth Report</option>
                                <option value="monthly_analytics" <?php echo (isset($_REQUEST['report_type']) && $_REQUEST['report_type'] == 'monthly_analytics') ? 'selected' : ''; ?>>Monthly Analytics</option>
                            </select>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Date From</label>
                                <input type="date" class="form-control" name="date_from" value="<?php echo $_REQUEST['date_from'] ?? ''; ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Date To</label>
                                <input type="date" class="form-control" name="date_to" value="<?php echo $_REQUEST['date_to'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Year</label>
                            <input type="number" class="form-control" name="year" value="<?php echo $_REQUEST['year'] ?? date('Y'); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm rounded-3">
                            <i class="bi bi-gear-fill me-2"></i>Generate Report
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="report-display-area">
            <?php if ($report && !empty($report['data'])): ?>
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-bottom">
                        <h5 class="mb-0 fw-bold text-success"><?php echo htmlspecialchars($report['name']); ?></h5>
                        <form method="POST" action="/lgu-urban-planning/reports/export.php">
                            <input type="hidden" name="report_data" value="<?php echo htmlspecialchars(json_encode($allDataForExport)); ?>">
                            <button type="submit" name="export_format" value="csv" class="btn btn-outline-success btn-sm rounded-pill px-3">Export All CSV</button>
                        </form>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-container-fixed">
                            <table class="table table-hover align-middle mb-0 permits-table">
                                <thead class="table-dark-header">
                                    <tr>
                                        <?php 
                                        $headers = array_keys($report['data'][0]);
                                        foreach ($headers as $header): ?>
                                            <th class="text-uppercase small fw-bold text-secondary"><?php echo str_replace('_', ' ', $header); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report['data'] as $row): ?>
                                        <tr>
                                            <?php foreach ($row as $value): ?>
                                                <td><?php echo htmlspecialchars($value); ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php if (isset($totalPages) && $totalPages > 1): ?>
                    <div class="card-footer bg-white py-3 border-top">
                        <div class="d-flex justify-content-between align-items-center">
                            <nav>
                                <ul class="pagination mb-0">
                                    <?php 
                                    $linkParams = $_GET;
                                    $linkParams['page'] = 1;
                                    echo '<li class="page-item '.($currentPage <= 1 ? 'disabled' : '').'"><a class="page-link" href="?'.http_build_query($linkParams).'"><i class="bi bi-chevron-double-left"></i></a></li>';
                                    $linkParams['page'] = max(1, $currentPage - 1);
                                    echo '<li class="page-item '.($currentPage <= 1 ? 'disabled' : '').'"><a class="page-link" href="?'.http_build_query($linkParams).'">Prev</a></li>';
                                    for ($i = 1; $i <= $totalPages; $i++): 
                                        $linkParams['page'] = $i;
                                        $queryString = http_build_query($linkParams);
                                    ?>
                                        <li class="page-item <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo $queryString; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <?php 
                                    $linkParams['page'] = min($totalPages, $currentPage + 1);
                                    echo '<li class="page-item '.($currentPage >= $totalPages ? 'disabled' : '').'"><a class="page-link" href="?'.http_build_query($linkParams).'">Next</a></li>';
                                    $linkParams['page'] = $totalPages;
                                    echo '<li class="page-item '.($currentPage >= $totalPages ? 'disabled' : '').'"><a class="page-link" href="?'.http_build_query($linkParams).'"><i class="bi bi-chevron-double-right"></i></a></li>';
                                    ?>
                                </ul>
                            </nav>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="empty-report-state shadow-sm mb-4">
                    <i class="bi bi-file-earmark-bar-graph fs-1 opacity-25 mb-3 text-primary"></i>
                    <h4 class="fw-bold text-dark mb-2">Ready to Generate</h4>
                    <p class="text-muted text-center mb-0">Select a report type and filters to view data and analytics.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="analytics-section">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="chart-card-container">
                    <h6 class="fw-bold mb-3 text-uppercase small text-muted">Year-on-Year Growth (Total)</h6>
                    <div style="height: 250px;"><canvas id="yoyGrowthChart"></canvas></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-card-container">
                    <h6 class="fw-bold mb-3 text-uppercase small text-muted">Application Status Rate</h6>
                    <div style="height: 250px;"><canvas id="permitDoughnutChart"></canvas></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-card-container">
                    <h6 class="fw-bold mb-3 text-uppercase small text-muted">Monthly Trend (<?php echo $selectedYear ?? date('Y'); ?>)</h6>
                    <div style="height: 250px;"><canvas id="revenueBarChart"></canvas></div>
                </div>
            </div>
            <div class="col-12">
                <div class="chart-card-container">
                    <h6 class="fw-bold mb-3 text-uppercase small text-muted">Top 5 Barangays by Projects</h6>
                    <div style="height: 300px;"><canvas id="barangayHorizontalChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusValues = <?php echo json_encode(array_values($chartData['status'])); ?>;
    const monthLabels = <?php echo json_encode(array_keys($chartData['months'])); ?>;
    const monthValues = <?php echo json_encode(array_values($chartData['months'])); ?>;
    const brgyLabels = <?php echo json_encode(array_keys($chartData['barangays'])); ?>;
    const brgyValues = <?php echo json_encode(array_values($chartData['barangays'])); ?>;
    const yoyCurrent = <?php echo (int)$chartData['yoy_comparison']['current']; ?>;
    const yoyPrev = <?php echo (int)$chartData['yoy_comparison']['previous']; ?>;
    const currentYearStr = '<?php echo $selectedYear; ?>';
    const prevYearStr = '<?php echo $selectedYear - 1; ?>';
    const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';

    if (isDark) {
        Chart.defaults.color = '#94a3b8'; 
        Chart.defaults.scale.grid.color = 'rgba(255, 255, 255, 0.1)'; 
    }

    // New YoY Comparison Chart
    new Chart(document.getElementById('yoyGrowthChart'), {
        type: 'bar',
        data: {
            labels: [prevYearStr, currentYearStr],
            datasets: [{
                label: 'Total Applications',
                data: [yoyPrev, yoyCurrent],
                backgroundColor: isDark ? ['#475569', '#10b981'] : ['#94a3b8', '#10b981'],
                borderRadius: 8
            }]
        },
        options: { 
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    new Chart(document.getElementById('permitDoughnutChart'), {
        type: 'doughnut',
        data: {
            labels: ['Approved', 'Rejected', 'Pending'],
            datasets: [{
                data: statusValues,
                backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                hoverOffset: 10
            }]
        },
        options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    new Chart(document.getElementById('revenueBarChart'), {
        type: 'bar',
        data: {
            labels: monthLabels,
            datasets: [{
                label: 'Applications',
                data: monthValues,
                backgroundColor: '#3b82f6',
                borderRadius: 5
            }]
        },
        options: { 
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } 
        }
    });

    new Chart(document.getElementById('barangayHorizontalChart'), {
        type: 'bar',
        data: {
            labels: brgyLabels.length ? brgyLabels : ['No Data'],
            datasets: [{
                label: 'Project Count',
                data: brgyValues.length ? brgyValues : [0],
                backgroundColor: '#6366f1',
                borderRadius: 5
            }]
        },
        options: { 
            indexAxis: 'y', 
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });
});
</script>

<?php include __DIR__ . '/../admin/footer.php'; ?>
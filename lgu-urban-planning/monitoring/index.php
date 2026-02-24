<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../modules/MonitoringAndInspection/MonitoringController.php';

$auth = new Auth();
$auth->requireRole(['admin', 'zoning_officer', 'building_official', 'inspector']);
$controller = new MonitoringController();

$apps = $controller->getApplicationsForDropdown();
$staffs = $controller->getStaffList();
$violations = $controller->getRecentViolations();

include __DIR__ . '/../admin/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style> 
    #inspectionCalendar { min-height: 550px; background: var(--bs-card-bg, white); border-radius: 8px; } 
    
    .empty-placeholder { 
        color: #adb5bd; text-align: center; padding: 40px 20px;
        background-color: #f8f9fa; border-radius: 8px; border: 1px dashed #dee2e6;
    }
    .empty-placeholder i { font-size: 2.5rem; display: block; margin-bottom: 10px; opacity: 0.5; }

    /* Custom Violation Styling */
    .violation-item { transition: background 0.2s; border-left: 4px solid #dc3545 !important; }
    .violation-item:hover { background-color: #fff8f8; }
    
    .checklist-btn { 
        transition: all 0.2s; 
        border: 1px solid #e9ecef;
        background: #fff;
    }
    .checklist-btn:hover { 
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        background-color: #f8fff9;
        border-color: #198754;
    }

    .fc-event { cursor: pointer; }
</style>

<div class="p-4">
    <div class="d-flex justify-content-between mb-4 align-items-center">
        <div>
            <h2 class="mb-0 fw-bold">Monitoring & Inspections</h2>
            <p class="text-muted">Dynamic site visits and compliance tracking.</p>
        </div>
        <button class="btn btn-primary px-4 shadow-sm fw-bold" onclick="openScheduleModal()">
            <i class="bi bi-calendar-plus me-2"></i>Schedule Inspection
        </button>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div id="inspectionCalendar"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card shadow-sm mb-4 border-0 overflow-hidden">
                <div class="card-header py-3 border-bottom d-flex align-items-center">
                    <i class="bi bi-exclamation-octagon-fill text-danger me-2 fs-5"></i>
                    <h6 class="mb-0 fw-bold text-danger">Recent Violations</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if (!empty($violations)): foreach($violations as $v): ?>
                            <div class="list-group-item violation-item d-flex justify-content-between align-items-center p-3">
                                <div>
                                    <div class="fw-bold text-emphasis"><?= htmlspecialchars($v['violation_type'] ?? 'General Violation') ?></div>
                                    <small class="text-secondary">
                                        <i class="bi bi-hash"></i>App #<?= htmlspecialchars($v['application_number']) ?>
                                    </small>
                                </div>
                                <span class="badge rounded-pill bg-danger shadow-sm">
                                    <?= htmlspecialchars($v['severity'] ?? 'Notice') ?>
                                </span>
                            </div>
                        <?php endforeach; else: ?>
                            <div class="p-4 text-center">
                                <div class="empty-placeholder text-secondary">
                                    <i class="bi bi-shield-check text-success fs-2"></i>
                                    <p class="mb-0 small fw-bold">No active violations</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header py-3 border-bottom d-flex align-items-center">
                    <i class="bi bi-clipboard2-check-fill text-success me-2 fs-5"></i>
                    <h6 class="mb-0 fw-bold text-success">Final Compliance</h6>
                </div>
                <div class="card-body p-3">
                    <?php if (!empty($apps)): foreach($apps as $app): ?>
                        <div class="checklist-btn rounded p-3 mb-2 d-flex justify-content-between align-items-center bg-body-tertiary border" 
                            style="cursor:pointer;" 
                            onclick="openOccupancyModal(<?= (int)$app['id'] ?>)">
                            <div>
                                <div class="fw-bold text-success small">FOR REVIEW</div>
                                <div class="text-emphasis">#<?= htmlspecialchars($app['application_number']) ?></div>
                            </div>
                            <i class="bi bi-chevron-right text-secondary"></i>
                        </div>
                    <?php endforeach; else: ?>
                        <div class="p-4 text-center text-secondary">
                            <i class="bi bi-journal-check fs-2"></i>
                            <p class="mb-0 small fw-bold">All caught up!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="inspectionForm" class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">Schedule Inspection</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold small">Select Application</label>
                    <select name="application_id" class="form-select" required>
                        <option value="">-- Choose Project --</option>
                        <?php foreach($apps as $app): ?>
                            <option value="<?= $app['id'] ?>">#<?= $app['application_number'] ?> - <?= $app['project_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Assign Inspector</label>
                    <select name="inspector_id" class="form-select" required>
                        <option value="">-- Choose Staff --</option>
                        <?php foreach($staffs as $s): ?>
                            <?php if($s['role'] === 'inspector'): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Inspection Date</label>
                    <input type="datetime-local" name="scheduled_at" class="form-control" required min="<?= date('Y-m-d\TH:i') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Remarks</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Specific areas to check..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4 shadow-sm" id="btnSaveSchedule">Save Schedule</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="occupancyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold">Final Checklist</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="occupancy_app_id">
                <p class="text-muted small mb-4">Confirm all systems are verified before granting occupancy.</p>
                <div class="form-check mb-3">
                    <input class="form-check-input chk-comp" type="checkbox" id="c1">
                    <label class="form-check-label fw-bold" for="c1">Fire Safety Systems Passed</label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input chk-comp" type="checkbox" id="c2">
                    <label class="form-check-label fw-bold" for="c2">Electrical Systems Verified</label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input chk-comp" type="checkbox" id="c3">
                    <label class="form-check-label fw-bold" for="c3">Sanitary & Plumbing Approved</label>
                </div>
            </div>
            <div class="modal-footer p-0">
                <button id="btnApprove" class="btn btn-success w-100 py-3 rounded-0 rounded-bottom fw-bold" disabled>GRANT OCCUPANCY PERMIT</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const ACTION_PATH = '../modules/MonitoringAndInspection/monitoring_action.php';

function openScheduleModal() { new bootstrap.Modal(document.getElementById('scheduleModal')).show(); }
function openOccupancyModal(id) { 
    document.getElementById('occupancy_app_id').value = id;
    new bootstrap.Modal(document.getElementById('occupancyModal')).show(); 
}

document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('inspectionCalendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        themeSystem: 'bootstrap5',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek' },
        events: ACTION_PATH + '?action=fetch_events',
        
        eventClick: function(info) {
            Swal.fire({
                title: 'Cancel Inspection?',
                text: "Do you want to delete this schedule for App #" + info.event.title.split('#')[1] + "?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-trash"></i> Yes, delete it!',
                cancelButtonText: 'No, keep it',
                reverseButtons: true,
                customClass: { confirmButton: 'btn btn-danger px-4 mx-2', cancelButton: 'btn btn-secondary px-4 mx-2' },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    const fd = new FormData();
                    fd.append('id', info.event.id);

                    fetch(ACTION_PATH + '?action=delete_event', { method: 'POST', body: fd })
                    .then(res => res.json())
                    .then(data => {
                        if(data.success) {
                            info.event.remove();
                            Swal.fire({ title: 'Deleted!', text: 'Schedule removed successfully.', icon: 'success', timer: 1500, showConfirmButton: false });
                        } else {
                            Swal.fire('Error', data.message || "Could not delete.", 'error');
                        }
                    });
                }
            });
        },

        eventDataTransform: function(item) {
            let isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            return {
                id: item.id,
                title: 'App #' + item.application_number,
                start: item.scheduled_at,
                backgroundColor: item.status === 'completed' ? '#198754' : (isDark ? '#3d8bfd' : '#0d6efd'),
                borderColor: 'transparent',
                textColor: '#ffffff'
            };
        }
    });
    calendar.render();

    // Form submission with Professional Alert
    document.getElementById('inspectionForm').onsubmit = function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSaveSchedule');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

        fetch(ACTION_PATH + '?action=save_schedule', { method: 'POST', body: new FormData(this) })
        .then(res => res.json())
        .then(data => {
            if(data.success) { 
                Swal.fire({
                    title: 'Success!',
                    text: 'Inspection schedule has been created.',
                    icon: 'success',
                    confirmButtonText: 'Great'
                }).then(() => {
                    location.reload(); 
                });
            } else { 
                Swal.fire('Error', data.message || "Error saving schedule.", 'error');
                btn.disabled = false;
                btn.innerText = 'Save Schedule';
            }
        });
    };

    const chks = document.querySelectorAll('.chk-comp');
    const btnApprove = document.getElementById('btnApprove');
    chks.forEach(c => c.onchange = () => btnApprove.disabled = !Array.from(chks).every(i => i.checked));

    btnApprove.onclick = function() {
        Swal.fire({
            title: 'Grant Occupancy?',
            text: "Are you sure you want to approve this permit?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Yes, Grant it!'
        }).then((result) => {
            if(result.isConfirmed) {
                const fd = new FormData();
                fd.append('application_id', document.getElementById('occupancy_app_id').value);
                fetch(ACTION_PATH + '?action=approve_occupancy', { method: 'POST', body: fd })
                .then(res => res.json()).then(data => { 
                    if(data.success) location.reload(); 
                });
            }
        });
    };
});
</script>

<?php include __DIR__ . '/../admin/footer.php'; ?>
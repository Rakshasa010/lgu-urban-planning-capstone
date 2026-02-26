<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../modules/MonitoringAndInspection/MonitoringController.php';

$auth = new Auth();
$auth->requireRole(['admin', 'zoning_officer', 'building_official', 'inspector']);
$controller = new MonitoringController();

$apps = $controller->getApplicationsForDropdown();
$staffs = $controller->getStaffList();
// Ginagamit ang function na ito para sa Table Log (siguraduhing existing ito sa Controller)
$inspections = $controller->getInspectionLogs(); 

include __DIR__ . '/../admin/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style> 
    /* Layout Adjustments */
    #inspectionCalendar { min-height: 500px; background: #fff; border-radius: 8px; padding: 10px; } 
    .log-table-container { max-height: 500px; overflow-y: auto; }
    
    /* UI Enhancements */
    .status-badge { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
    .table thead th { background-color: #f8f9fa; position: sticky; top: 0; z-index: 5; }

    .fc-event { cursor: pointer; padding: 2px 4px; font-size: 0.85em; }
    .fc-toolbar-title { font-size: 1.1rem !important; font-weight: bold; }

    .fc-event-title {
    font-weight: 600 !important;
    font-size: 0.75rem !important;
    padding: 1px 3px;
}

.fc-daygrid-event {
    border-radius: 4px !important;
    margin: 1px 2px !important;
    /* Pinipigilan ang pag-stretch */
    white-space: nowrap !important; 
}
</style>

<div class="p-4">
    <div class="d-flex justify-content-between mb-4 align-items-center">
        <div>
            <h2 class="mb-0 fw-bold text-dark">Monitoring & Inspections</h2>
            <p class="text-muted">Real-time inspection tracking and scheduling.</p>
        </div>
        <button class="btn btn-primary px-4 shadow-sm fw-bold" onclick="openScheduleModal()">
            <i class="bi bi-calendar-plus me-2"></i>Schedule Inspection
        </button>
    </div>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card shadow-sm border-0 h-100">
<div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-bold">
        <i class="bi bi-list-check me-2 text-primary"></i>Inspection Record Log
    </h6>

    <ul class="nav nav-pills border rounded-pill p-1 bg-light shadow-sm" id="monitoringTabs" style="font-size: 0.75rem;">
    <li class="nav-item">
        <button class="nav-link active rounded-pill py-0 px-2 fw-bold" data-bs-toggle="pill" onclick="filterTable('all')">
            All
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link rounded-pill py-0 px-2 fw-bold text-secondary" data-bs-toggle="pill" onclick="filterTable('inspection')">
            For Inspection
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link rounded-pill py-0 px-2 fw-bold text-secondary" data-bs-toggle="pill" onclick="filterTable('scheduled')">
            Scheduled
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link rounded-pill py-0 px-2 fw-bold text-secondary" data-bs-toggle="pill" onclick="filterTable('completed')">
            Completed
        </button>
    </li>
</ul>
</div>
                <div class="card-body p-0 log-table-container">
                    <div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead>
        <tr class="text-secondary small">
            <th class="ps-3">APP ID</th>
            <th>INSPECTOR</th>
            <th>DATE</th>
            <th class="text-center">STATUS</th>
            <th class="text-center">ACTION</th> </tr>
    </thead>
<tbody class="small" id="inspectionTableBody">
    <?php if (!empty($inspections)): foreach($inspections as $log): ?>
    <tr data-status="<?= htmlspecialchars($log['display_status']) ?>"> 
        <td class="ps-3 fw-bold text-primary">#<?= htmlspecialchars($log['application_number']) ?></td>
        <td><?= htmlspecialchars($log['inspector_name'] ?? 'Unassigned') ?></td>
        <td class="text-muted">
            <?php 
            if (!empty($log['scheduled_at']) && $log['scheduled_at'] !== '0000-00-00 00:00:00') {
                echo date('M d, Y', strtotime($log['scheduled_at']));
            } else {
                echo '<span class="badge bg-light text-danger border italic">TBD / No Schedule</span>';
            }
            ?>
        </td>
        <td class="text-center">
            <?php 
                // Visual Badge Logic
                $currentStatus = strtolower($log['display_status']);
                if($currentStatus == 'inspection') {
                    $statusClass = 'bg-secondary-subtle text-secondary border-secondary-subtle';
                    $label = 'For Inspection';
                } elseif($log['status'] == 'completed') {
                    $statusClass = 'bg-success-subtle text-success border-success-subtle';
                    $label = 'Passed';
                } else {
                    $statusClass = 'bg-warning-subtle text-dark border-warning-subtle';
                    $label = 'Scheduled';
                }
            ?>
            <span class="badge border <?= $statusClass ?> px-2 py-1 status-badge">
                <?= $label ?>
            </span>
        </td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-primary fw-bold px-3 shadow-sm" 
                        onclick='viewInspectionDetails(<?= json_encode($log) ?>)'>
                    <i class="bi bi-eye me-1"></i> View
                </button>
            </td>
        </tr>
    <?php endforeach; else: ?>
        <tr class="no-records"><td colspan="5" class="text-center py-5 text-muted italic">No records found.</td></tr>
    <?php endif; ?>
</tbody>
</table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div id="inspectionCalendar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form id="inspectionForm" class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">New Inspection Schedule</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold small">SELECT APPLICATION</label>
                    <select name="application_id" class="form-select shadow-sm" required>
                        <option value="">-- Choose Project --</option>
                        <?php foreach($apps as $app): ?>
                            <option value="<?= $app['id'] ?>">#<?= $app['application_number'] ?> - <?= $app['project_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">ASSIGN INSPECTOR</label>
                    <select name="inspector_id" class="form-select shadow-sm" required>
                        <option value="">-- Choose Staff --</option>
                        <?php foreach($staffs as $s): 
                            $userRole = strtolower($s['role']);
                            if($userRole === 'admin' || $userRole === 'inspector'): ?>
                                <option value="<?= $s['id'] ?>">
                                    <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?> (<?= ucfirst($userRole) ?>)
                                </option>
                        <?php endif; endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">INSPECTION DATE</label>
                    <input type="datetime-local" name="scheduled_at" class="form-control shadow-sm" required min="<?= date('Y-m-d\TH:i') ?>">
                </div>

                <div class="mb-0">
                    <label class="form-label fw-bold small">REMARKS / NOTES</label>
                    <textarea name="notes" class="form-control shadow-sm" rows="2" placeholder="Specific items to monitor..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 px-4">
                <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4 shadow-sm fw-bold" id="btnSaveSchedule">Save Schedule</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-info-circle me-2"></i>Inspection Details & Checklist</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-4 text-center">
                    <h6 class="text-muted small fw-bold mb-1">PROJECT NAME</h6>
                    <h4 id="view_project_name" class="fw-bold text-primary mb-0"></h4>
                    <span id="view_app_number" class="badge bg-light text-secondary border mt-2"></span>
                </div>
                
                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div class="p-2 border rounded bg-light">
                            <label class="text-muted small fw-bold d-block">INSPECTOR</label>
                            <span id="view_inspector" class="text-dark fw-semibold"></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded bg-light">
                            <label class="text-muted small fw-bold d-block">DATE & TIME</label>
                            <span id="view_date" class="text-dark fw-semibold"></span>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="text-muted small fw-bold mb-1">REMARKS / NOTES FROM SCHEDULER</label>
                    <div id="view_notes" class="p-3 border rounded bg-white small italic shadow-sm" style="min-height: 60px;"></div>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card border-success shadow-sm h-100">
                            <div class="card-header bg-success text-white py-2 small fw-bold">
                                <i class="bi bi-card-checklist me-2"></i>INSPECTION CHECKLIST
                            </div>
                            <div class="card-body bg-light">
    <form id="checklistForm">
        <input type="hidden" name="inspection_id" id="checklist_ins_id">

        <div class="mb-3">
            <h6 class="fw-bold text-primary border-bottom pb-1 small text-uppercase">
                <i class="bi bi-map-fill me-1"></i> I. Zoning & Land Use Compliance
            </h6>
            <div class="form-check mb-2">
                <input class="form-check-input border-secondary" type="checkbox" name="land_use_check" id="check_land" required>
                <label class="form-check-label small fw-bold" for="check_land">
                    ACTUAL LAND USE VERIFICATION
                </label>
                <div class="text-muted" style="font-size: 0.75rem;">
                    The actual use of the building/lot is consistent with the Zoning Classification (e.g., R-1, C-2) and the approved Development Permit.
                </div>
            </div>
        </div>

        <div class="mb-3">
            <h6 class="fw-bold text-primary border-bottom pb-1 small text-uppercase">
                <i class="bi bi-rulers me-1"></i> II. Development Standards
            </h6>
            <div class="form-check mb-2">
                <input class="form-check-input border-secondary" type="checkbox" name="plan_consistency" id="check_plan" required>
                <label class="form-check-label small fw-bold" for="check_plan">
                    PLAN & SETBACK CONSISTENCY
                </label>
                <div class="text-muted" style="font-size: 0.75rem;">
                    As-built structure conforms to the required setbacks, building footprint, and dimensions indicated in the automated zoning plan.
                </div>
            </div>
        </div>

        <div class="mb-3">
            <h6 class="fw-bold text-primary border-bottom pb-1 small text-uppercase">
                <i class="bi bi-search me-1"></i> III. Monitoring & Expansion Control
            </h6>
            <div class="form-check mb-2">
                <input class="form-check-input border-secondary" type="checkbox" name="expansion_check" id="check_expansion" required>
                <label class="form-check-label small fw-bold" for="check_expansion">
                    NON-VIOLATION OF EXPANSION
                </label>
                <div class="text-muted" style="font-size: 0.75rem;">
                    No unauthorized horizontal or vertical expansions beyond the Floor Area Ratio (FAR) allowed in the Zoning Ordinance.
                </div>
            </div>
        </div>

        <div class="mb-3 pt-2 border-top">
            <label class="form-label small fw-bold text-muted text-uppercase">Zoning Officer's Remarks</label>
            <textarea name="inspection_notes" class="form-control form-control-sm border-primary" rows="2" placeholder="Input specific zoning findings here..."></textarea>
        </div>

        <button type="button" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm py-2" onclick="saveChecklist()">
            <i class="bi bi-shield-check me-1"></i> VALIDATE ZONING COMPLIANCE
        </button>
    </form>
</div>
                        </div>
                    </div>

<div class="col-md-6">
    <div class="card border-danger shadow-sm h-100" id="violationSection">
        <div class="card-header bg-danger text-white py-2 small fw-bold d-flex justify-content-between align-items-center">
            <span><i class="bi bi-exclamation-triangle-fill me-2"></i>OFFICIAL VIOLATION REPORT</span>
            <span class="badge bg-white text-danger">LEGAL ACTION</span>
        </div>
        <div class="card-body bg-danger-subtle">
            <form id="violationForm" enctype="multipart/form-data">
                <input type="hidden" name="inspection_id" id="viol_ins_id">
                <input type="hidden" name="application_id" id="viol_app_id">
                
                <div class="mb-2">
                    <label class="small fw-bold mb-1 text-danger">NATURE OF VIOLATION</label>
                    <select name="violation_type" class="form-select form-select-sm shadow-sm border-danger" required>
                        <option value="">-- Select Critical Violation --</option>
                        <option value="Deviation from Approved Plan">Deviation from Approved Plan (Blueprint Mismatch)</option>
                        <option value="Encroachment/Illegal Expansion">Encroachment/Illegal Expansion (Boundary/Setback Violation)</option>
                        <option value="Unauthorized Change of Use">Change of Use (e.g. Residential to Industrial)</option>
                        <option value="Safety & Structural Hazard">Structural/Safety Hazard</option>
                        <option value="No Valid Permits/Documentation">Lack of Proper Documentation</option>
                    </select>
                </div>

                <div class="mb-2">
                    <label class="small fw-bold mb-1 text-danger">PHOTO EVIDENCE (Violation Proof)</label>
                    <input type="file" name="violation_photo" class="form-control form-control-sm shadow-sm border-danger" accept="image/*" required>
                </div>

                <div class="mb-3">
                    <label class="small fw-bold mb-1 text-danger">REMARKS / FINDINGS</label>
                    <textarea name="notes" class="form-control form-control-sm shadow-sm border-danger" rows="3" placeholder="State exact details (e.g., 'Extra floor added without permit')" required></textarea>
                </div>

                <div class="mt-3 mb-2 px-1 text-danger" style="font-size: 0.7rem; line-height: 1.2;">
                    <i class="bi bi-info-circle-fill me-1"></i> 
                    <strong>SYSTEM PROTOCOL:</strong> Submission will trigger a <u>VIOLATION DETECTED</u> status. 
                    This action automatically suspends Certificate issuance and flags the application for mandatory resolution.
                </div>

                <button type="submit" class="btn btn-danger btn-sm w-100 fw-bold shadow-sm mt-1">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i> ISSUE NOTICE OF VIOLATION
                </button>
            </form>
        </div>
    </div>
</div>
                </div> </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const ACTION_PATH = '../modules/MonitoringAndInspection/monitoring_action.php';

function openScheduleModal() {
    const myModal = new bootstrap.Modal(document.getElementById('scheduleModal'));
    myModal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    // === 1. CALENDAR INITIALIZATION ===
    const calendarEl = document.getElementById('inspectionCalendar');
    if (calendarEl) {
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            themeSystem: 'bootstrap5',
            eventDisplay: 'block', 
            displayEventTime: false, 
            headerToolbar: { 
                left: 'prev,next', 
                center: 'title', 
                right: 'today' 
            },
            events: ACTION_PATH + '?action=fetch_events',
            height: 'auto',
            eventMaxStack: 2, 
            dayMaxEvents: true,
            eventClick: function(info) {
                Swal.fire({
                    title: 'Cancel Inspection?',
                    text: "Delete schedule for " + info.event.title + "?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Yes, delete it!',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        const fd = new FormData();
                        fd.append('id', info.event.id);
                        fetch(ACTION_PATH + '?action=delete_event', { method: 'POST', body: fd })
                        .then(res => res.json())
                        .then(data => {
                            if(data.success) {
                                info.event.remove();
                                Swal.fire('Deleted!', 'Schedule removed.', 'success');
                            }
                        });
                    }
                });
            },
            eventDataTransform: function(item) {
                const shortID = item.application_number.split('-').pop();
                return {
                    id: item.id,
                    title: '#' + shortID, 
                    start: item.scheduled_at,
                    allDay: true, 
                    backgroundColor: item.status === 'completed' ? '#198754' : '#ffc107',
                    borderColor: 'transparent',
                    textColor: item.status === 'completed' ? '#ffffff' : '#000000'
                };
            }
        });
        calendar.render();
    }

    // === 2. FORM SUBMISSIONS ===
    const insForm = document.getElementById('inspectionForm');
    if(insForm) {
        insForm.onsubmit = function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSaveSchedule');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

            fetch(ACTION_PATH + '?action=save_schedule', { method: 'POST', body: new FormData(this) })
            .then(res => res.json())
            .then(data => {
                if(data.success) { 
                    Swal.fire({ icon: 'success', title: 'Schedule Saved!', showConfirmButton: false, timer: 1500 })
                    .then(() => location.reload());
                } else { 
                    Swal.fire('Error', data.message || "Error saving.", 'error');
                    btn.disabled = false;
                    btn.innerText = 'Save Schedule';
                }
            });
        };
    }

// I-update ang onsubmit handler para sa Violation Form
const violForm = document.getElementById('violationForm');
if(violForm) {
    violForm.onsubmit = function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

        // Gagamit ng FormData para masalo ang File Upload
        const fd = new FormData(this);

        fetch(ACTION_PATH + '?action=report_violation', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Violation Reported',
                    text: 'The application has been flagged and the Notice of Violation is now active.',
                    confirmButtonText: 'Print Notice'
                }).then(() => {
                    // Dito pwede mo i-redirect sa isang printable page (Optional sa defense)
                    // window.open('print_notice.php?id=' + fd.get('application_id'), '_blank');
                    location.reload();
                });
            } else {
                Swal.fire('Error', data.message, 'error');
                btn.disabled = false;
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Server Error', 'Check your file upload size or directory permissions.', 'error');
            btn.disabled = false;
        });
    };
}
});

// === 3. GLOBAL FUNCTIONS ===

function viewInspectionDetails(data) {
    document.getElementById('view_project_name').innerText = data.project_name || 'Project Name N/A';
    document.getElementById('view_app_number').innerText = 'App #' + data.application_number;
    document.getElementById('view_inspector').innerText = data.inspector_name;
    document.getElementById('view_date').innerText = new Date(data.scheduled_at).toLocaleString('en-US', { 
        month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' 
    });
    document.getElementById('view_notes').innerText = data.notes || 'No notes recorded for this schedule.';
    
    if(document.getElementById('checklist_ins_id')) {
        document.getElementById('checklist_ins_id').value = data.id;
    }
    document.getElementById('viol_ins_id').value = data.id;
    document.getElementById('viol_app_id').value = data.application_id;

    const myModal = new bootstrap.Modal(document.getElementById('viewModal'));
    myModal.show();
}

function saveChecklist() {
    const form = document.getElementById('checklistForm');
    if(!form) return;

    const checkboxes = form.querySelectorAll('input[type="checkbox"]');
    let allChecked = true;
    checkboxes.forEach(cb => { if(!cb.checked) allChecked = false; });

    if(!allChecked) {
        Swal.fire({
            icon: 'error',
            title: 'Zoning Non-Compliance',
            text: 'Cannot complete inspection. One or more requirements are NOT compliant.',
            confirmButtonColor: '#d33'
        });
        return; 
    }

    Swal.fire({
        title: 'Submit Zoning Report?',
        text: "Confirming this will mark the project as COMPLIANT and notify the applicant.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        confirmButtonText: 'Yes, Submit Result'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData(form);
            const insID = document.getElementById('checklist_ins_id').value;

            // 1. I-save ang Checklist status
            fetch(ACTION_PATH + '?action=save_checklist', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    // 2. Ipakita ang Success Alert muna
                    Swal.fire({
                        icon: 'success',
                        title: 'Zoning Validated',
                        text: 'Compliance report has been filed and official notice is being sent.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        // 3. I-trigger ang pag-send ng Professional LGU Message
                        const msgData = new FormData();
                        msgData.append('inspection_id', insID);
                        
                        fetch(ACTION_PATH + '?action=send_approval_message', { 
                            method: 'POST', 
                            body: msgData 
                        })
                        .then(() => {
                            location.reload(); // Reload pagkatapos ma-send ang message
                        });
                    });
                } else {
                    Swal.fire('Error', 'Failed to update record.', 'error');
                }
            });
        }
    });
}

function filterTable(status) {
    const tabs = document.querySelectorAll('#monitoringTabs .nav-link');
    tabs.forEach(tab => {
        tab.classList.remove('active', 'bg-primary', 'text-white');
        tab.classList.add('text-secondary');
    });
    
    if(event && event.target) {
        event.target.classList.add('active', 'bg-primary', 'text-white');
        event.target.classList.remove('text-secondary');
    }

    const rows = document.querySelectorAll('#inspectionTableBody tr:not(.no-records)');
    let hasVisibleRow = false;

    rows.forEach(row => {
        const rowStatus = row.getAttribute('data-status').trim().toLowerCase();
        if (status === 'all' || rowStatus === status.toLowerCase()) {
            row.style.setProperty('display', '', 'important');
            hasVisibleRow = true;
        } else {
            row.style.setProperty('display', 'none', 'important');
        }
    });

    const noRecordsRow = document.querySelector('.no-records');
    if (!hasVisibleRow) {
        if (!noRecordsRow) {
            const tbody = document.getElementById('inspectionTableBody');
            if(tbody) tbody.innerHTML += '<tr class="no-records"><td colspan="5" class="text-center py-5 text-muted italic">No records found for this category.</td></tr>';
        } else {
            noRecordsRow.style.display = '';
        }
    } else if (noRecordsRow) {
        noRecordsRow.style.display = 'none';
    }
}
</script>
<?php include __DIR__ . '/../admin/footer.php'; ?>
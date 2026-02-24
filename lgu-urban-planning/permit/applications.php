<?php
/**
 * Applications List (Staff View) - Integrated Manual Add & Overdue Filter
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helper.php';
require_once __DIR__ . '/../modules/PermitProcessing/PermitController.php';

$auth = new Auth();
$auth->requireRole(['admin', 'zoning_officer', 'building_official', 'assessor', 'inspector']);

$db = Database::getInstance();
$permitController = new PermitController();

// --- START: MANUAL ADD LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'manual_add') {
    try {
        $dbConn = $db->getConnection();
        $dbConn->beginTransaction();

        $appNumber = Helper::generateApplicationNumber();
        
        // Dito natin kukunin ang buong string (e.g. 114-05-002-01-001)
        $fullParcelId = $_POST['parcel_id'] ?? ''; 

        $sql = "INSERT INTO applications 
                (application_number, applicant_id, parcel_id, project_name, project_type, project_description, 
                 lot_number, barangay, street, block, latitude, longitude, status, record_type, submitted_at, created_at) 
                VALUES 
                (:application_number, :applicant_id, :parcel_id, :project_name, :project_type, :project_description, 
                 :lot_number, :barangay, :street, :block, :latitude, :longitude, :status, :record_type, :submitted_at, :created_at)";
        
        $params = [
            ':application_number'  => $appNumber,
            ':applicant_id'        => $_POST['applicant_id'],
            ':parcel_id'           => $fullParcelId, 
            ':project_name'        => $_POST['project_name'],
            ':project_type'        => $_POST['project_type'],
            ':project_description' => $_POST['project_description'],
            ':lot_number'          => $_POST['lot_number'],
            ':barangay'            => $_POST['barangay'],
            ':street'              => $_POST['street'],
            ':block'               => $_POST['block'],
            ':latitude'            => $_POST['latitude'],
            ':longitude'           => $_POST['longitude'],
            ':status'              => 'submitted',
            ':record_type'         => 'walk-in',
            ':submitted_at'        => date('Y-m-d H:i:s'),
            ':created_at'          => date('Y-m-d H:i:s')
        ];

        $stmt = $dbConn->prepare($sql);
        $stmt->execute($params);

        $dbConn->commit();
        header("Location: applications.php?success=1");
        exit();

    } catch (Exception $e) {
        if (isset($dbConn)) $dbConn->rollBack();
        $error_msg = "Error creating application: " . $e->getMessage();
    }
}
// --- END: MANUAL ADD LOGIC ---

$allApplicants = $db->fetchAll("SELECT id, first_name, last_name FROM users WHERE role = 'applicant' ORDER BY last_name ASC");

$filters = [
    'status' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? '',
    'filter' => $_GET['filter'] ?? '' 
];

if ($filters['status'] === 'overdue') {
    $filters['filter'] = 'overdue';
}

$applications = $permitController->getApplications($filters);

$pageTitle = 'Applications';
include __DIR__ . '/../admin/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<style>
    #map-container { height: 300px; width: 100%; border-radius: 8px; margin-top: 10px; border: 1px solid #ddd; }
    .select2-container--bootstrap-5 .select2-selection { border-radius: 0.375rem; }
    .table-danger:hover td { background-color: #f8d7da !important; }
    
    /* FIX: Force both headers and data cells to stay in 1 layer */
    .table thead th, 
    .table tbody td { 
        white-space: nowrap; 
        vertical-align: middle; 
    }

    /* Optional: Allow Project Name to wrap if it's very long, but keep ID on one line */
    .project-name-cell {
        white-space: normal !important;
        min-width: 200px;
    }
</style>

<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Development Permit Applications</h2>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#manualAddModal">
            <i class="bi bi-plus-lg me-1"></i> Manual Add Application
        </button>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle me-2"></i> Application successfully created and linked!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger shadow-sm">
            <i class="bi bi-exclamation-octagon me-2"></i> <?php echo htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>

    <?php if ($filters['filter'] === 'overdue'): ?>
        <div class="alert alert-danger d-flex justify-content-between align-items-center mb-4 shadow-sm" style="border-radius: 10px; border-left: 5px solid #dc3545;">
            <div>
                <i class="bi bi-clock-history me-2"></i>
                <strong>Filtered View:</strong> Showing applications pending for more than 3 days.
            </div>
            <a href="applications.php" class="btn btn-sm btn-outline-danger">Clear Filter</a>
        </div>
    <?php endif; ?>
    
    <div class="card mb-3 shadow-sm border-0">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" name="search" placeholder="Search project or applicant..." value="<?php echo htmlspecialchars($filters['search']); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="submitted" <?php echo $filters['status'] === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                        <option value="under_review" <?php echo $filters['status'] === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                        <option value="for_revision" <?php echo $filters['status'] === 'for_revision' ? 'selected' : ''; ?>>For Revision</option>
                        <option value="approved" <?php echo $filters['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $filters['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="overdue" <?php echo ($filters['status'] === 'overdue') ? 'selected' : ''; ?> style="color: #dc3545; font-weight: bold;">Overdue (3+ Days)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 shadow-sm">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="applications.php" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Application #</th>
                            <th>Project Name</th>
                            <th>Applicant</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>Date</th>
                            <th class="text-center pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($applications)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">No applications found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($applications as $app): 
                                $isOverdue = false;
                                $createdDate = strtotime($app['created_at']);
                                $threeDaysAgo = strtotime('-3 days');
                                if ($createdDate <= $threeDaysAgo && !in_array($app['status'], ['approved', 'rejected', 'cancelled'])) {
                                    $isOverdue = true;
                                }
                            ?>
                            <tr class="<?php echo $isOverdue ? 'table-danger' : ''; ?>">
                                <td class="ps-4 fw-bold">
                                    <span class="d-inline-flex align-items-center">
                                        <?php echo htmlspecialchars($app['application_number']); ?>
                                        <?php if($isOverdue): ?>
                                            <i class="bi bi-exclamation-triangle-fill text-danger ms-2" title="Overdue"></i>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td class="project-name-cell"><?php echo htmlspecialchars($app['project_name']); ?></td>
                                <td><?php echo htmlspecialchars($app['applicant_first_name'] . ' ' . $app['applicant_last_name']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo Helper::getStatusBadge($app['status']); ?> px-2 py-1">
                                        <?php echo strtoupper(str_replace('_', ' ', $app['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($app['officer_first_name'])): ?>
                                        <div class="small fw-bold"><?php echo htmlspecialchars($app['officer_first_name'] . ' ' . $app['officer_last_name']); ?></div>
                                    <?php else: ?>
                                        <span class="text-muted small italic">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo Helper::formatDate($app['created_at']); ?></td>
                                <td class="text-center pe-4">
                                    <a href="view.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-primary px-3 shadow-sm rounded-pill">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="manualAddModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="applications.php" method="POST" class="modal-content border-0 shadow-lg">
            <input type="hidden" name="action" value="manual_add">
            <div class="modal-header bg-success text-white py-3">
                <h5 class="modal-title d-flex align-items-center"><i class="bi bi-pencil-square me-2"></i>Manual Application Entry</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label fw-bold small text-uppercase">Select Registered Applicant</label>
                        <select name="applicant_id" class="form-select select2-search" data-placeholder="Search applicant name..." required>
                            <option value=""></option>
                            <?php foreach($allApplicants as $applicant): ?>
                                <option value="<?php echo $applicant['id']; ?>"><?php echo htmlspecialchars($applicant['last_name'] . ', ' . $applicant['first_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-bold small text-uppercase">Project Name</label>
                        <input type="text" name="project_name" class="form-control shadow-sm" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-uppercase">Project Type</label>
                        <select name="project_type" class="form-select shadow-sm" required>
                            <option value="Residential">Residential</option>
                            <option value="Commercial">Commercial</option>
                            <option value="Industrial">Industrial</option>
                            <option value="Institutional">Institutional</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-uppercase">Barangay</label>
                        <input type="text" name="barangay" class="form-control shadow-sm" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-uppercase">Street</label>
                        <input type="text" name="street" class="form-control shadow-sm">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-uppercase">Lot Number</label>
                        <input type="text" name="lot_number" class="form-control shadow-sm">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-uppercase">Block Number</label>
                        <input type="text" name="block" class="form-control shadow-sm">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-uppercase">Parcel ID (PIN)</label>
                        <input type="text" name="parcel_id" class="form-control shadow-sm" placeholder="e.g. 123-45-678">
                    </div>
                    
                    <div class="col-md-12 mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold small text-uppercase mb-0">Project Location (Coordinates)</label>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-select-map">
                                <i class="bi bi-geo-alt me-1"></i> Pick On Map
                            </button>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <input type="number" step="any" name="latitude" id="inp-lat" class="form-control coord-input" placeholder="Latitude" required>
                            </div>
                            <div class="col-md-6">
                                <input type="number" step="any" name="longitude" id="inp-lng" class="form-control coord-input" placeholder="Longitude" required>
                            </div>
                        </div>
                        <div id="map-container" style="display:none; margin-top:10px;"></div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold small text-uppercase">Project Description</label>
                        <textarea name="project_description" class="form-control shadow-sm" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0 py-3">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success px-5 shadow">Create Application</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    let map;
    let marker;
    const defaultLat = 14.7566;
    const defaultLng = 121.0450;

    /**
     * Function para i-update ang marker at ang inputs
     * @param {number} lat 
     * @param {number} lng 
     * @param {boolean} moveMap - kung ise-center ang mapa (true para sa manual type)
     */
    function updateMarker(lat, lng, moveMap = false) {
        if (!lat || !lng || isNaN(lat) || isNaN(lng)) return;
        
        const pos = [parseFloat(lat), parseFloat(lng)];
        
        if (marker) {
            marker.setLatLng(pos);
        } else if (map) {
            // Gagawa ng draggable marker kung wala pa
            marker = L.marker(pos, {draggable: true}).addTo(map);
            
            // Sync: Kapag d-in-rag ang pin, update ang text inputs
            marker.on('dragend', function() {
                const newPos = marker.getLatLng();
                $('#inp-lat').val(newPos.lat.toFixed(6));
                $('#inp-lng').val(newPos.lng.toFixed(6));
            });
        }

        // Kung galing sa manual typing, dalhin ang view ng mapa sa location
        if (moveMap && map) {
            map.setView(pos, 16);
        }
    }

    $(document).ready(function() {
        // 1. EVENT: Kapag nag-type manual sa Latitude/Longitude fields
        $('#inp-lat, #inp-lng').on('input change', function() {
            const lat = $('#inp-lat').val();
            const lng = $('#inp-lng').val();
            
            // I-update ang marker at i-center ang map
            if(lat && lng) {
                updateMarker(lat, lng, true);
            }
        });

        // 2. EVENT: Toggle Map Container
        $('#btn-select-map').on('click', function() {
            const container = $('#map-container');
            const btn = $(this);
            
            container.slideToggle(400, function() {
                if (container.is(':visible')) {
                    btn.html('<i class="bi bi-map-fill"></i> Hide Map');
                    
                    // Initialize Map kung first time bubuksan
                    if (!map) {
                        map = L.map('map-container').setView([defaultLat, defaultLng], 13);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© OpenStreetMap contributors'
                        }).addTo(map);

                        // Sync: Kapag clinick ang MAPA, update inputs at marker
                        map.on('click', function(e) {
                            const lat = e.latlng.lat.toFixed(6);
                            const lng = e.latlng.lng.toFixed(6);
                            
                            $('#inp-lat').val(lat);
                            $('#inp-lng').val(lng);
                            updateMarker(lat, lng);
                        });
                    }
                    
                    // Importante: I-refresh ang size ng Leaflet para hindi putol ang tiles
                    setTimeout(() => { 
                        map.invalidateSize(); 
                        // Kung may laman na ang inputs, ipakita na agad ang pin
                        const existingLat = $('#inp-lat').val();
                        const existingLng = $('#inp-lng').val();
                        if(existingLat && existingLng) updateMarker(existingLat, existingLng, true);
                    }, 200);

                } else {
                    btn.html('<i class="bi bi-map"></i> Select on Map');
                }
            });
        });

        // 3. Auto-format Parcel ID (Optional helper)
        $('#parcel_id').on('input', function() {
            let val = $(this).val().replace(/[^0-9]/g, '');
            // Dito mo pwedeng dagdagan ng auto-dash logic kung gusto mo
        });
    });
</script>

<?php include __DIR__ . '/../admin/footer.php'; ?>
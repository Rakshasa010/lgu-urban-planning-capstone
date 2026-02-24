<?php
/**
 * Submit Development Permit Application
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helper.php';
require_once __DIR__ . '/../modules/ApplicantSelfService/ApplicantController.php';

$auth = new Auth();
$auth->requireRole('applicant');

$applicantController = new ApplicantController();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'project_name' => $_POST['project_name'] ?? '',
        'project_type' => $_POST['project_type'] ?? '',
        'project_description' => $_POST['project_description'] ?? '',
        'lot_number' => $_POST['lot_number'] ?? '',
        'block' => $_POST['block'] ?? '',
        'street' => $_POST['street'] ?? '',
        'barangay' => $_POST['barangay'] ?? '',
        'parcel_id' => $_POST['parcel_id'] ?? '',
        'latitude' => $_POST['latitude'] ?? null,
        'longitude' => $_POST['longitude'] ?? null
    ];
    
    if (empty($data['project_name'])) {
        $error = 'Project name is required';
    } else {
        $applicationId = $applicantController->submitApplication($data);
        
        // Handle file uploads
        if (isset($_FILES['documents']) && !empty($_FILES['documents']['name'][0])) {
            foreach ($_FILES['documents']['name'] as $key => $name) {
                if (!empty($name)) {
                    $file = [
                        'name' => $name,
                        'type' => $_FILES['documents']['type'][$key],
                        'tmp_name' => $_FILES['documents']['tmp_name'][$key],
                        'error' => $_FILES['documents']['error'][$key],
                        'size' => $_FILES['documents']['size'][$key]
                    ];
                    
                    $documentType = $_POST['document_types'][$key] ?? 'other';
                    $applicantController->uploadDocument($applicationId, $file, $documentType);
                }
            }
        }
        
        header('Location: /lgu-urban-planning/applicant/view.php?id=' . $applicationId);
        exit;
    }
}

$pageTitle = 'Submit Application';
include __DIR__ . '/../user/header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    #map-container { 
        height: 350px; 
        width: 100%; 
        border-radius: 8px; 
        margin-top: 10px; 
        border: 1px solid #ddd; 
        display: none; 
    }
    .coord-input { background-color: #f8f9fa; }
</style>

<div class="p-4">
    <h2 class="mb-4">Submit Development Permit Application</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="card mb-3">
            <div class="card-header">
                <h5>Project Information</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="project_name" class="form-label">Project Name *</label>
                    <input type="text" class="form-control" id="project_name" name="project_name" required>
                </div>
                <div class="mb-3">
                    <label for="project_type" class="form-label">Project Type</label>
                    <select class="form-select" id="project_type" name="project_type">
                        <option value="">Select project type</option>
                        <option value="Residential">Residential</option>
                        <option value="Commercial">Commercial</option>
                        <option value="Industrial">Industrial</option>
                        <option value="Institutional">Institutional</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="project_description" class="form-label">Project Description</label>
                    <textarea class="form-control" id="project_description" name="project_description" rows="3"></textarea>
                </div>
            </div>
        </div>
        
<div class="card mb-3 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Location Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="lot_number" class="form-label">Lot Number</label>
                        <input type="text" class="form-control" id="lot_number" name="lot_number">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="block" class="form-label">Block Number</label>
                        <input type="text" class="form-control" id="block" name="block">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="street" class="form-label">Street</label>
                        <input type="text" class="form-control" id="street" name="street">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="barangay" class="form-label">Barangay</label>
                        <input type="text" class="form-control" id="barangay" name="barangay">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="parcel_id" class="form-label">Parcel ID (PIN)</label>
                    <input type="text" class="form-control" id="parcel_id" name="parcel_id" placeholder="e.g. 123-45-678">
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
            </div>
        </div>

<div class="card mb-3 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Required Documents</h5>
            </div>
            <div class="card-body">
                <div id="document-uploads">
                    <div class="mb-3 document-upload-item">
                        <label class="form-label">Document</label>
                        <div class="row">
                            <div class="col-md-4">
                                <select class="form-select" name="document_types[]">
                                    <option value="site_plan">Site Plan</option>
                                    <option value="lot_plan">Lot Plan</option>
                                    <option value="ownership_proof">Ownership Proof</option>
                                    <option value="building_plan">Building Plan</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <input type="file" class="form-control" name="documents[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addDocumentUpload()">
                    <i class="bi bi-plus"></i> Add Another Document
                </button>
            </div>
        </div>
        
        <div class="mt-4">
            <button type="submit" class="btn btn-primary px-4">Submit Application</button>
            <a href="/lgu-urban-planning/user/index.php" class="btn btn-light border px-4">Cancel</a>
        </div>
    </form>
</div>

<script>
function addDocumentUpload() {
    const container = document.getElementById('document-uploads');
    const newItem = document.createElement('div');
    newItem.className = 'mb-3 document-upload-item';
    newItem.innerHTML = `
        <label class="form-label">Document</label>
        <div class="row">
            <div class="col-md-4">
                <select class="form-select" name="document_types[]">
                    <option value="site_plan">Site Plan</option>
                    <option value="lot_plan">Lot Plan</option>
                    <option value="ownership_proof">Ownership Proof</option>
                    <option value="building_plan">Building Plan</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="col-md-7">
                <input type="file" class="form-control" name="documents[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.document-upload-item').remove()">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(newItem);
}

document.addEventListener('DOMContentLoaded', function() {
    let map, marker;
    const defaultLat = 14.6760; // Adjust to your LGU
    const defaultLng = 121.0437;

    const btnMap = document.getElementById('btn-select-map');
    const mapContainer = document.getElementById('map-container');
    const latInput = document.getElementById('inp-lat');
    const lngInput = document.getElementById('inp-lng');

    function updateMarker(lat, lng, moveMap = false) {
        if (!lat || !lng) return;
        const pos = [parseFloat(lat), parseFloat(lng)];
        
        if (marker) {
            marker.setLatLng(pos);
        } else if (map) {
            marker = L.marker(pos, {draggable: true}).addTo(map);
            marker.on('dragend', function() {
                const newPos = marker.getLatLng();
                latInput.value = newPos.lat.toFixed(6);
                lngInput.value = newPos.lng.toFixed(6);
            });
        }
        if (moveMap && map) map.setView(pos, 16);
    }

    btnMap.addEventListener('click', function() {
        if (mapContainer.style.display === 'none' || mapContainer.style.display === '') {
            mapContainer.style.display = 'block';
            if (!map) {
                map = L.map('map-container').setView([defaultLat, defaultLng], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                map.on('click', function(e) {
                    latInput.value = e.latlng.lat.toFixed(6);
                    lngInput.value = e.latlng.lng.toFixed(6);
                    updateMarker(e.latlng.lat, e.latlng.lng);
                });
            }
            setTimeout(() => { map.invalidateSize(); }, 200);
        } else {
            mapContainer.style.display = 'none';
        }
    });

    [latInput, lngInput].forEach(input => {
        input.addEventListener('change', () => updateMarker(latInput.value, lngInput.value, true));
    });
});
</script>

<?php include __DIR__ . '/../user/footer.php'; ?>
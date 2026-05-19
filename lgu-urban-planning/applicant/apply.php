<?php
/**
 * Submit Development Permit Application
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helper.php';
require_once __DIR__ . '/../modules/ApplicantSelfService/ApplicantController.php';

$auth = new Auth();
$auth->requireLogin();          // redirect to login if not authenticated
$auth->requireRole('applicant');



$applicantController = new ApplicantController();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'project_name'        => $_POST['project_name'] ?? '',
        'project_type'        => $_POST['project_type'] ?? '',
        'project_description' => $_POST['project_description'] ?? '',
        'lot_number'          => $_POST['lot_number'] ?? '',
        'block'               => $_POST['block'] ?? '',
        'street'              => $_POST['street'] ?? '',
        'barangay'            => $_POST['barangay'] ?? '',
        'parcel_id'           => $_POST['parcel_id'] ?? '',
        'latitude'            => $_POST['latitude'] ?? null,
        'longitude'           => $_POST['longitude'] ?? null
    ];

    if (empty($data['project_name'])) {
        $error = _apt('err_project_name');
    } else {
        $applicationId = $applicantController->submitApplication($data);

        if (isset($_FILES['documents']) && !empty($_FILES['documents']['name'][0])) {
            foreach ($_FILES['documents']['name'] as $key => $name) {
                if (!empty($name)) {
                    $file = [
                        'name'     => $name,
                        'type'     => $_FILES['documents']['type'][$key],
                        'tmp_name' => $_FILES['documents']['tmp_name'][$key],
                        'error'    => $_FILES['documents']['error'][$key],
                        'size'     => $_FILES['documents']['size'][$key]
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

// ── i18n — reads language saved by settings.php ──────────────────────────────
$_apLang = $_SESSION['locale_language'] ?? 'en_PH';

$_apT = [
    'en_PH' => [
        'page_title'          => 'Submit Application',
        'heading'             => 'Submit Development Permit Application',
        'err_project_name'    => 'Project name is required',
        'sec_project'         => 'Project Information',
        'sec_location'        => 'Location Information',
        'sec_documents'       => 'Required Documents',
        'lbl_project_name'    => 'Project Name',
        'lbl_project_type'    => 'Project Type',
        'lbl_project_desc'    => 'Project Description',
        'opt_select_type'     => 'Select project type',
        'opt_residential'     => 'Residential',
        'opt_commercial'      => 'Commercial',
        'opt_industrial'      => 'Industrial',
        'opt_institutional'   => 'Institutional',
        'lbl_lot_number'      => 'Lot Number',
        'lbl_block'           => 'Block Number',
        'lbl_street'          => 'Street',
        'lbl_barangay'        => 'Barangay',
        'lbl_parcel_id'       => 'Parcel ID (PIN)',
        'lbl_coordinates'     => 'Project Location (Coordinates)',
        'btn_pick_map'        => 'Pick On Map',
        'ph_latitude'         => 'Latitude',
        'ph_longitude'        => 'Longitude',
        'lbl_document'        => 'Document',
        'opt_site_plan'       => 'Site Plan',
        'opt_lot_plan'        => 'Lot Plan',
        'opt_ownership_proof' => 'Ownership Proof',
        'opt_building_plan'   => 'Building Plan',
        'opt_other'           => 'Other',
        'btn_add_doc'         => 'Add Another Document',
        'btn_submit'          => 'Submit Application',
        'btn_cancel'          => 'Cancel',
    ],
    'fil' => [
        'page_title'          => 'Magsumite ng Aplikasyon',
        'heading'             => 'Magsumite ng Aplikasyon para sa Development Permit',
        'err_project_name'    => 'Kinakailangan ang pangalan ng proyekto',
        'sec_project'         => 'Impormasyon ng Proyekto',
        'sec_location'        => 'Impormasyon ng Lokasyon',
        'sec_documents'       => 'Mga Kinakailangang Dokumento',
        'lbl_project_name'    => 'Pangalan ng Proyekto',
        'lbl_project_type'    => 'Uri ng Proyekto',
        'lbl_project_desc'    => 'Paglalarawan ng Proyekto',
        'opt_select_type'     => 'Pumili ng uri ng proyekto',
        'opt_residential'     => 'Residential',
        'opt_commercial'      => 'Komersyal',
        'opt_industrial'      => 'Industrial',
        'opt_institutional'   => 'Institusyonal',
        'lbl_lot_number'      => 'Numero ng Lote',
        'lbl_block'           => 'Numero ng Bloke',
        'lbl_street'          => 'Kalye',
        'lbl_barangay'        => 'Barangay',
        'lbl_parcel_id'       => 'ID ng Parsela (PIN)',
        'lbl_coordinates'     => 'Lokasyon ng Proyekto (Koordinada)',
        'btn_pick_map'        => 'Pumili sa Mapa',
        'ph_latitude'         => 'Latitude',
        'ph_longitude'        => 'Longitude',
        'lbl_document'        => 'Dokumento',
        'opt_site_plan'       => 'Plano ng Site',
        'opt_lot_plan'        => 'Plano ng Lote',
        'opt_ownership_proof' => 'Patunay ng Pagmamay-ari',
        'opt_building_plan'   => 'Plano ng Gusali',
        'opt_other'           => 'Iba pa',
        'btn_add_doc'         => 'Magdagdag ng Isa pang Dokumento',
        'btn_submit'          => 'Isumite ang Aplikasyon',
        'btn_cancel'          => 'Kanselahin',
    ],
];

function _apt(string $key): string {
    global $_apT, $_apLang;
    return $_apT[$_apLang][$key] ?? $_apT['en_PH'][$key] ?? $key;
}


$pageTitle = _apt('page_title');
$isAuthPage = true;
include __DIR__ . '/../user/header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
/* =============================================
   APPLY PAGE — FULLY RESPONSIVE
   Breakpoints: 768px | 480px | 320px
   ============================================= */

/* --- Page wrapper ---
   No extra padding — main-content in header.php
   already provides outer spacing.               */
.apply-page {
    width: 100%;
    box-sizing: border-box;
    overflow-x: hidden;
}

.apply-page *,
.apply-page *::before,
.apply-page *::after {
    box-sizing: border-box;
    max-width: 100%;
}

/* --- Page title --- */
.apply-page h2 {
    font-size: 1.5rem;
    margin-bottom: 1.25rem;
    font-weight: 700;
}

/* --- Cards --- */
.apply-page .card {
    width: 100%;
    border: 1px solid rgba(0,0,0,.1);
}

.apply-page .card-header {
    padding: 0.875rem 1.25rem;
}

.apply-page .card-header h5 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
}

.apply-page .card-body {
    padding: 1.25rem;
}

/* --- Form elements --- */
.apply-page .form-label {
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.35rem;
}

.apply-page .form-control,
.apply-page .form-select {
    font-size: 0.875rem;
    padding: 0.45rem 0.75rem;
    width: 100%;
}

.apply-page textarea.form-control {
    resize: vertical;
    min-height: 90px;
}

/* --- Map --- */
#map-container {
    height: 350px;
    width: 100%;
    border-radius: 8px;
    margin-top: 10px;
    border: 1px solid #ddd;
    display: none;
}

.coord-input { background-color: #f8f9fa; }

/* --- Coordinates row --- */
.coord-section-label {
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 0;
}

/* --- Document upload rows --- */
.document-upload-item .row {
    align-items: center;
}

/* --- Submit buttons --- */
.apply-form-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 1.25rem;
}

.apply-form-actions .btn {
    font-size: 0.9rem;
    padding: 0.5rem 1.5rem;
}

/* =============================================
   768px — Tablet
   ============================================= */
@media (max-width: 768px) {

    .apply-page h2 {
        font-size: 1.3rem;
        margin-bottom: 1rem;
    }

    .apply-page .card-header {
        padding: 0.75rem 1rem;
    }

    .apply-page .card-body {
        padding: 1rem;
    }

    /* Bootstrap .row negative margins can cause overflow — neutralise */
    .apply-page .row {
        margin-left: 0;
        margin-right: 0;
    }

    .apply-page .row > [class*="col-"] {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    /* Stack col-md-6 fields to full width */
    .apply-page .col-md-6 {
        width: 100%;
        flex: 0 0 100%;
        max-width: 100%;
    }

    /* Map height */
    #map-container { height: 280px; }

    /* Coord label + button — keep side by side */
    .coord-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.4rem;
    }

    /* Coord inputs stay side-by-side on tablet */
    .coord-inputs .col-md-6 {
        width: 50%;
        flex: 0 0 50%;
        max-width: 50%;
    }

    /* Document upload: stack type select above file input */
    .document-upload-item .col-md-4,
    .document-upload-item .col-md-7,
    .document-upload-item .col-md-8 {
        width: 100%;
        flex: 0 0 100%;
        max-width: 100%;
        margin-bottom: 0.4rem;
    }

    .document-upload-item .col-md-1 {
        width: auto;
        flex: none;
    }

    .apply-form-actions .btn {
        flex: 1 1 auto;
        text-align: center;
    }
}

/* =============================================
   480px — Large Mobile
   ============================================= */
@media (max-width: 480px) {

    .apply-page h2 {
        font-size: 1.15rem;
        margin-bottom: 0.875rem;
    }

    .apply-page .card-header {
        padding: 0.65rem 0.875rem;
    }

    .apply-page .card-header h5 {
        font-size: 0.9rem;
    }

    .apply-page .card-body {
        padding: 0.875rem;
    }

    .apply-page .form-label {
        font-size: 0.82rem;
    }

    .apply-page .form-control,
    .apply-page .form-select {
        font-size: 0.82rem;
        padding: 0.4rem 0.65rem;
    }

    .apply-page textarea.form-control {
        min-height: 80px;
    }

    /* Coord inputs: stack on large mobile */
    .coord-inputs .col-md-6 {
        width: 100% !important;
        flex: 0 0 100% !important;
        max-width: 100% !important;
    }

    /* Second coord input needs top gap when stacked */
    .coord-inputs .col-md-6:last-child {
        margin-top: 0.4rem;
    }

    /* Map height */
    #map-container { height: 240px; }

    /* Pick on map button: full width */
    #btn-select-map {
        width: 100%;
        margin-top: 0.4rem;
        font-size: 0.8rem;
    }

    /* Coord section: stack label above button */
    .coord-header {
        flex-direction: column;
        align-items: flex-start;
    }

    /* Document upload items */
    .document-upload-item {
        background: rgba(0,0,0,0.02);
        border: 1px solid rgba(0,0,0,0.07);
        border-radius: 6px;
        padding: 0.65rem;
        margin-bottom: 0.65rem !important;
    }

    .document-upload-item .form-label {
        font-size: 0.78rem;
        font-weight: 600;
        margin-bottom: 0.4rem;
    }

    /* "Add Another Document" button */
    #document-uploads + button,
    .apply-page .btn-outline-secondary {
        width: 100%;
        font-size: 0.82rem;
        margin-top: 0.25rem;
    }

    .apply-form-actions {
        flex-direction: column;
        gap: 0.4rem;
    }

    .apply-form-actions .btn {
        width: 100%;
        padding: 0.55rem;
        font-size: 0.875rem;
    }

    .apply-page .mb-3 {
        margin-bottom: 0.75rem !important;
    }
}

/* =============================================
   320px — Small Mobile
   ============================================= */
@media (max-width: 320px) {

    .apply-page h2 {
        font-size: 1rem;
        margin-bottom: 0.75rem;
    }

    /* Cards: minimal padding */
    .apply-page .card-header {
        padding: 0.55rem 0.7rem;
    }

    .apply-page .card-header h5 {
        font-size: 0.82rem;
    }

    .apply-page .card-body {
        padding: 0.7rem;
    }

    /* Tighten row gutters to near-zero */
    .apply-page .row {
        --bs-gutter-x: 0.4rem;
        margin-left: 0;
        margin-right: 0;
    }

    .apply-page .form-label {
        font-size: 0.75rem;
        margin-bottom: 0.25rem;
    }

    .apply-page .form-control,
    .apply-page .form-select {
        font-size: 0.78rem;
        padding: 0.35rem 0.55rem;
    }

    .apply-page textarea.form-control {
        min-height: 70px;
        rows: 2;
    }

    /* Map */
    #map-container { height: 200px; border-radius: 6px; }

    /* Coord section label */
    .coord-section-label { font-size: 0.7rem; }

    #btn-select-map { font-size: 0.72rem; padding: 0.28rem 0.6rem; }

    /* Document upload items */
    .document-upload-item {
        padding: 0.55rem;
        margin-bottom: 0.55rem !important;
    }

    .document-upload-item .form-label {
        font-size: 0.72rem;
    }

    /* Delete button on doc rows */
    .document-upload-item .btn-danger {
        font-size: 0.72rem;
        padding: 0.28rem 0.5rem;
    }

    /* Add doc button */
    .apply-page .btn-outline-secondary {
        font-size: 0.75rem;
        padding: 0.35rem 0.6rem;
    }

    /* Alert */
    .apply-page .alert {
        font-size: 0.8rem;
        padding: 0.6rem 0.75rem;
    }

    .apply-form-actions .btn {
        font-size: 0.82rem;
        padding: 0.5rem;
    }

    .apply-page .mb-3 {
        margin-bottom: 0.6rem !important;
    }

    /* Card spacing */
    .apply-page .card.mb-3 {
        margin-bottom: 0.75rem !important;
    }
}
</style>

<div class="apply-page">
    <h2 class="mb-4"><?php echo _apt('heading'); ?></h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <!-- ── Project Information ── -->
        <div class="card mb-3 shadow-sm">
            <div class="card-header bg-white">
                <h5><?php echo _apt('sec_project'); ?></h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="project_name" class="form-label"><?php echo _apt('lbl_project_name'); ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="project_name" name="project_name" required>
                </div>
                <div class="mb-3">
                    <label for="project_type" class="form-label"><?php echo _apt('lbl_project_type'); ?></label>
                    <select class="form-select" id="project_type" name="project_type">
                        <option value=""><?php echo _apt('opt_select_type'); ?></option>
                        <option value="Residential"><?php echo _apt('opt_residential'); ?></option>
                        <option value="Commercial"><?php echo _apt('opt_commercial'); ?></option>
                        <option value="Industrial"><?php echo _apt('opt_industrial'); ?></option>
                        <option value="Institutional"><?php echo _apt('opt_institutional'); ?></option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="project_description" class="form-label"><?php echo _apt('lbl_project_desc'); ?></label>
                    <textarea class="form-control" id="project_description" name="project_description" rows="3"></textarea>
                </div>
            </div>
        </div>

        <!-- ── Location Information ── -->
        <div class="card mb-3 shadow-sm">
            <div class="card-header bg-white">
                <h5><?php echo _apt('sec_location'); ?></h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6 mb-3">
                        <label for="lot_number" class="form-label"><?php echo _apt('lbl_lot_number'); ?></label>
                        <input type="text" class="form-control" id="lot_number" name="lot_number">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="block" class="form-label"><?php echo _apt('lbl_block'); ?></label>
                        <input type="text" class="form-control" id="block" name="block">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="street" class="form-label"><?php echo _apt('lbl_street'); ?></label>
                        <input type="text" class="form-control" id="street" name="street">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="barangay" class="form-label"><?php echo _apt('lbl_barangay'); ?></label>
                        <input type="text" class="form-control" id="barangay" name="barangay">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="parcel_id" class="form-label"><?php echo _apt('lbl_parcel_id'); ?></label>
                    <input type="text" class="form-control" id="parcel_id" name="parcel_id" placeholder="e.g. 123-45-678">
                </div>

                <!-- Coordinates + Map -->
                <div class="mb-3 mt-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2 coord-header">
                        <label class="form-label coord-section-label fw-bold small text-uppercase mb-0">
                            <?php echo _apt('lbl_coordinates'); ?>
                        </label>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-select-map">
                            <i class="bi bi-geo-alt me-1"></i> <?php echo _apt('btn_pick_map'); ?>
                        </button>
                    </div>
                    <div class="row g-2 coord-inputs">
                        <div class="col-md-6">
                            <input type="number" step="any" name="latitude" id="inp-lat"
                                   class="form-control coord-input" placeholder="<?php echo _apt('ph_latitude'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <input type="number" step="any" name="longitude" id="inp-lng"
                                   class="form-control coord-input" placeholder="<?php echo _apt('ph_longitude'); ?>" required>
                        </div>
                    </div>
                    <div id="map-container"></div>
                </div>
            </div>
        </div>

        <!-- ── Required Documents ── -->
        <div class="card mb-3 shadow-sm">
            <div class="card-header bg-white">
                <h5><?php echo _apt('sec_documents'); ?></h5>
            </div>
            <div class="card-body">
                <div id="document-uploads">
                    <div class="mb-3 document-upload-item">
                        <label class="form-label"><?php echo _apt('lbl_document'); ?></label>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <select class="form-select" name="document_types[]">
                                    <option value="site_plan"><?php echo _apt('opt_site_plan'); ?></option>
                                    <option value="lot_plan"><?php echo _apt('opt_lot_plan'); ?></option>
                                    <option value="ownership_proof"><?php echo _apt('opt_ownership_proof'); ?></option>
                                    <option value="building_plan"><?php echo _apt('opt_building_plan'); ?></option>
                                    <option value="other"><?php echo _apt('opt_other'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <input type="file" class="form-control" name="documents[]"
                                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addDocumentUpload()">
                    <i class="bi bi-plus"></i> <?php echo _apt('btn_add_doc'); ?>
                </button>
            </div>
        </div>

        <!-- ── Actions ── -->
        <div class="apply-form-actions">
            <button type="submit" class="btn btn-primary"><?php echo _apt('btn_submit'); ?></button>
            <a href="/lgu-urban-planning/user/index.php" class="btn btn-light border"><?php echo _apt('btn_cancel'); ?></a>
        </div>

    </form>
</div>

<script>
// Translations passed from PHP for dynamic JS elements
const _apJS = <?php echo json_encode([
    'lbl_document'        => _apt('lbl_document'),
    'opt_site_plan'       => _apt('opt_site_plan'),
    'opt_lot_plan'        => _apt('opt_lot_plan'),
    'opt_ownership_proof' => _apt('opt_ownership_proof'),
    'opt_building_plan'   => _apt('opt_building_plan'),
    'opt_other'           => _apt('opt_other'),
], JSON_UNESCAPED_UNICODE); ?>;

function addDocumentUpload() {
    const container = document.getElementById('document-uploads');
    const newItem = document.createElement('div');
    newItem.className = 'mb-3 document-upload-item';
    newItem.innerHTML = `
        <label class="form-label">${_apJS.lbl_document}</label>
        <div class="row g-2">
            <div class="col-md-4">
                <select class="form-select" name="document_types[]">
                    <option value="site_plan">${_apJS.opt_site_plan}</option>
                    <option value="lot_plan">${_apJS.opt_lot_plan}</option>
                    <option value="ownership_proof">${_apJS.opt_ownership_proof}</option>
                    <option value="building_plan">${_apJS.opt_building_plan}</option>
                    <option value="other">${_apJS.opt_other}</option>
                </select>
            </div>
            <div class="col-md-7">
                <input type="file" class="form-control" name="documents[]"
                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
            </div>
            <div class="col-md-1 d-flex align-items-center">
                <button type="button" class="btn btn-sm btn-danger w-100"
                        onclick="this.closest('.document-upload-item').remove()">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(newItem);
}

document.addEventListener('DOMContentLoaded', function () {
    let map, marker;
    const defaultLat = 14.6760;
    const defaultLng = 121.0437;

    const btnMap      = document.getElementById('btn-select-map');
    const mapContainer = document.getElementById('map-container');
    const latInput    = document.getElementById('inp-lat');
    const lngInput    = document.getElementById('inp-lng');

    function updateMarker(lat, lng, moveMap = false) {
        if (!lat || !lng) return;
        const pos = [parseFloat(lat), parseFloat(lng)];
        if (marker) {
            marker.setLatLng(pos);
        } else if (map) {
            marker = L.marker(pos, { draggable: true }).addTo(map);
            marker.on('dragend', function () {
                const p = marker.getLatLng();
                latInput.value = p.lat.toFixed(6);
                lngInput.value = p.lng.toFixed(6);
            });
        }
        if (moveMap && map) map.setView(pos, 16);
    }

    btnMap.addEventListener('click', function () {
        const isHidden = mapContainer.style.display === 'none' || mapContainer.style.display === '';
        if (isHidden) {
            mapContainer.style.display = 'block';
            if (!map) {
                map = L.map('map-container').setView([defaultLat, defaultLng], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                map.on('click', function (e) {
                    latInput.value  = e.latlng.lat.toFixed(6);
                    lngInput.value  = e.latlng.lng.toFixed(6);
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
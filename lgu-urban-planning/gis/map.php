<?php
// GIS Mapping & Zoning Analysis
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../modules/GISMapping/GISController.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireRole(['admin', 'super_admin', 'zoning_officer', 'building_official', 'assessor']);


$gisController = new GISController();
$searchResults = [];
$selectedParcel = null;

// Capture Application Data from GET
$targetAppId = $_GET['app_id'] ?? null;
$appLat = $_GET['lat'] ?? null;
$appLng = $_GET['lng'] ?? null;
$urlBarangay = $_GET['brgy'] ?? '';
$urlStreet = $_GET['street'] ?? '';
$urlBlock = $_GET['block'] ?? '';
$urlLot = $_GET['lot'] ?? '';

// Search Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    // Override Lat/Lng if user manually enters them in the search boxes
    if (!empty($_POST['search_lat']) && !empty($_POST['search_lng'])) {
        $appLat = $_POST['search_lat'];
        $appLng = $_POST['search_lng'];
    }

    $criteria = [
        'lot_number' => $_POST['lot_number'] ?? '',
        'block' => $_POST['block'] ?? '',
        'street' => $_POST['street'] ?? '',
        'barangay' => $_POST['barangay'] ?? '',
        'parcel_id' => $_POST['parcel_id'] ?? ''
    ];
    $searchResults = $gisController->searchParcel($criteria);
    if (count($searchResults) === 1) { 
        $selectedParcel = $searchResults[0]; 
    }
}

// Support for direct Parcel ID
if (isset($_GET['parcel_id'])) { 
    $selectedParcel = $gisController->getParcelById($_GET['parcel_id']); 
}

$zoningClassifications = $gisController->getZoningClassifications();
$allParcels = $gisController->getAllParcels();

// ── Language & locale ─────────────────────────────────────────────────────────
$lang = $_SESSION['locale_language'] ?? 'en_PH';

$translations = [
    'en_PH' => [
        'page_heading'          => 'GIS Map',
        'page_subheading'       => 'Interactive zoning map and spatial analysis for urban planning.',
        'search_panel_title'    => 'Location Locator',
        'search_panel_sub'      => 'Spatial Coordinate Search',
        'lbl_geo_coords'        => 'Geographic Coordinates',
        'ph_latitude'           => 'Latitude',
        'ph_longitude'          => 'Longitude',
        'lbl_admin_info'        => 'Administrative Info',
        'ph_barangay'           => 'Barangay',
        'ph_street'             => 'Street Name',
        'ph_block'              => 'Block No.',
        'ph_lot'                => 'Lot No.',
        'btn_locate'            => 'LOCATE COORDINATES',
        'analysis_title'        => 'Technical Analysis',
        'analysis_placeholder'  => 'Select a point on the map to analyze zoning.',
        'overlay_title'         => 'Zoning Overlay',
        'overlay_show_all'      => 'Show All Zoning Types',
        'overlay_boundary'      => 'Boundary Lines',
        'map_card_title'        => 'GEOSPATIAL INTERFACE',
        'map_card_badge'        => 'Active GIS Node',
        'compliance_title'      => 'Spatial Zoning Compliance',
        'compliance_placeholder'=> 'Select a point to evaluate...',
        'js_zoning_record'      => 'Zoning Record: ',
        'js_custom_area'        => 'Custom Area',
        'js_unknown_zone'       => 'Unknown/Outside Boundary',
        'js_analysis_lat'       => 'Latitude',
        'js_analysis_lng'       => 'Longitude',
        'js_analysis_zone'      => 'Zoning Type',
        'js_buffer_btn'         => 'Show 20m Buffer',
        'js_confirm_send'       => 'CONFIRM & SEND TO APPLICATION',
        'js_no_app_id'          => 'No Application ID Linked',
        'js_coords'             => 'Coordinates',
        'js_zoning_zone'        => 'Zoning Zone',
        'js_land_record'        => 'Land Record',
        'js_status_check'       => 'Status Check: Consistent with LGU Land Use Mapping.',
        'js_point_is'           => 'Point is',
    ],
    'fil' => [
        'page_heading'          => 'GIS Mapa',
        'page_subheading'       => 'Interaktibong mapa ng zoning at spatial analysis para sa urban planning.',
        'search_panel_title'    => 'Tagahanap ng Lokasyon',
        'search_panel_sub'      => 'Paghahanap ng Spatial Coordinates',
        'lbl_geo_coords'        => 'Mga Heograpikong Koordinada',
        'ph_latitude'           => 'Latitude',
        'ph_longitude'          => 'Longitude',
        'lbl_admin_info'        => 'Impormasyon sa Administratibo',
        'ph_barangay'           => 'Barangay',
        'ph_street'             => 'Pangalan ng Kalye',
        'ph_block'              => 'Block Blg.',
        'ph_lot'                => 'Lot Blg.',
        'btn_locate'            => 'HANAPIN ANG KOORDINADA',
        'analysis_title'        => 'Teknikal na Pagsusuri',
        'analysis_placeholder'  => 'Pumili ng punto sa mapa para suriin ang zoning.',
        'overlay_title'         => 'Zoning Overlay',
        'overlay_show_all'      => 'Ipakita ang Lahat ng Uri ng Zoning',
        'overlay_boundary'      => 'Mga Hangganan',
        'map_card_title'        => 'GEOSPATIAL INTERFACE',
        'map_card_badge'        => 'Aktibong GIS Node',
        'compliance_title'      => 'Spatial na Pagsunod sa Zoning',
        'compliance_placeholder'=> 'Pumili ng punto para suriin...',
        'js_zoning_record'      => 'Rekord ng Zoning: ',
        'js_custom_area'        => 'Pasadyang Lugar',
        'js_unknown_zone'       => 'Hindi Kilala/Labas ng Hangganan',
        'js_analysis_lat'       => 'Latitude',
        'js_analysis_lng'       => 'Longitude',
        'js_analysis_zone'      => 'Uri ng Zoning',
        'js_buffer_btn'         => 'Ipakita ang 20m Buffer',
        'js_confirm_send'       => 'KUMPIRMAHIN AT IPADALA SA APLIKASYON',
        'js_no_app_id'          => 'Walang Naka-link na Application ID',
        'js_coords'             => 'Mga Koordinada',
        'js_zoning_zone'        => 'Zone ng Zoning',
        'js_land_record'        => 'Rekord ng Lupa',
        'js_status_check'       => 'Pagsusuri ng Katayuan: Naaayon sa LGU Land Use Mapping.',
        'js_point_is'           => 'Ang punto ay',
    ],
];

function t_map(string $key, array $translations, string $lang): string {
    return $translations[$lang][$key] ?? $translations['en_PH'][$key] ?? $key;
}

$isAuthPage = true;
include __DIR__ . '/../admin/header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css"/>

<style>
    :root { --lgu-blue: #1a237e; --lgu-accent: #ffd600; --bg-light: #f8f9fc; }

    /* ── BASE ── */
    #map { height: 750px !important; width: 100%; border-radius: 0 0 15px 15px; z-index: 1; border: 1px solid #dee2e6; }
    .search-panel { border-radius: 12px; border: none; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1); background: #fff; overflow: hidden; }
    .search-header { background: var(--lgu-blue); color: white; padding: 15px; }
    .section-label { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #5c6bc0; letter-spacing: 0.5px; margin-bottom: 5px; display: block; }
    .form-control-lgu { border: 1px solid #ced4da; border-radius: 6px; padding: 8px 12px; font-size: 0.85rem; background-color: var(--bg-light); }
    .form-control-lgu:focus { background-color: #fff; border-color: var(--lgu-blue); box-shadow: none; }
    .btn-lgu-search { background: var(--lgu-blue); color: white; font-weight: 600; border: none; padding: 10px; transition: 0.3s; }
    .btn-lgu-search:hover { background: #0d1442; color: #fff; }
    .analysis-inner { background: var(--bg-light); border-radius: 10px; border-left: 4px solid #4e73df; padding: 15px; }
    .table-analysis td { padding: 8px 0; font-size: 0.85rem; border-bottom: 1px solid #eef0f7; }
    .table-analysis tr:last-child td { border-bottom: none; }
    #zoningComplianceCard { display: none; margin-top: 20px; }

    /* ================================================
       MOBILE RESPONSIVE
       768px (Tablet) | 480px (Large Mobile) | 320px (Small Mobile)
       ================================================ */

    /* --- 768px: Tablet --- */
    @media (max-width: 768px) {

        .p-4 { padding: 1rem !important; }

        /* Page header */
        .d-flex.justify-content-between.align-items-center.mb-4 h2 { font-size: 1.3rem; }
        .d-flex.justify-content-between.align-items-center.mb-4 h2 span { width: 36px !important; height: 36px !important; }
        .d-flex.justify-content-between.align-items-center.mb-4 h2 i { font-size: 1.1rem !important; }
        .d-flex.justify-content-between.align-items-center.mb-4 p { font-size: 0.8rem; }

        /* Stack: left panel full width above map */
        .row > .col-md-4,
        .row > .col-md-8 { width: 100%; flex: 0 0 100%; }

        /* Map: shorter on tablet */
        #map { height: 420px !important; border-radius: 10px !important; }

        /* Search header */
        .search-header { padding: 12px 15px; }
        .search-header h6 { font-size: 0.9rem; }

        /* Search panel body */
        .card-body.p-4 { padding: 1rem !important; }

        /* Form inputs */
        .form-control-lgu { font-size: 0.82rem; padding: 7px 10px; }
        .section-label { font-size: 0.68rem; }

        /* Search button */
        .btn-lgu-search { padding: 9px; font-size: 0.875rem; }

        /* Analysis / Zoning cards */
        .analysis-inner { padding: 12px; }
        .table-analysis td { font-size: 0.8rem; padding: 6px 0; }

        /* Zoning overlay select */
        .form-select.form-control-lgu { font-size: 0.82rem; }

        /* Compliance card */
        #zoningComplianceCard .card-body { padding: 1rem !important; }
        #zoningComplianceCard h6 { font-size: 0.82rem; }

        /* Gap between legend and map card */
        .search-panel.mt-3 { margin-bottom: 1rem !important; }
    }

    /* --- 480px: Large Mobile --- */
    @media (max-width: 480px) {

        .p-4 { padding: 0.75rem !important; }

        /* Page header */
        .d-flex.justify-content-between.align-items-center.mb-4 h2 { font-size: 1.1rem; }
        .d-flex.justify-content-between.align-items-center.mb-4 h2 span { width: 32px !important; height: 32px !important; }
        .d-flex.justify-content-between.align-items-center.mb-4 h2 i { font-size: 1rem !important; }
        .d-flex.justify-content-between.align-items-center.mb-4 p { font-size: 0.75rem; }

        /* Map: compact height */
        #map { height: 320px !important; border-radius: 8px !important; }

        /* Search header */
        .search-header { padding: 10px 12px; }
        .search-header h6 { font-size: 0.82rem; }
        .search-header small { font-size: 0.6rem !important; }

        /* Form inputs */
        .card-body.p-4 { padding: 0.75rem !important; }
        .form-control-lgu { font-size: 0.78rem; padding: 6px 9px; border-radius: 5px; }
        .section-label { font-size: 0.63rem; margin-bottom: 3px; }
        .mb-2 { margin-bottom: 0.35rem !important; }
        .mb-3 { margin-bottom: 0.5rem !important; }

        /* Lat/Lng + Block/Lot row: keep 2-col */
        .row.g-2 { --bs-gutter-x: 0.35rem; --bs-gutter-y: 0.35rem; }

        /* Search button */
        .btn-lgu-search { padding: 8px; font-size: 0.82rem; }

        /* Analysis card */
        .analysis-inner { padding: 10px; }
        .table-analysis td { font-size: 0.75rem; padding: 5px 0; }
        #analysisResults .text-center.py-4 { padding: 0.75rem !important; }
        #analysisResults .text-center.py-4 p { font-size: 0.75rem; }
        #analysisResults .fs-3 { font-size: 1.25rem !important; }

        /* Zoning overlay */
        .form-select.form-control-lgu { font-size: 0.78rem; padding: 5px 8px; }
        .form-check-label.small { font-size: 0.75rem; }
        .overlay-item { padding: 0.35rem 0.5rem !important; }

        /* Compliance card */
        #zoningComplianceCard { margin-top: 12px; }
        #zoningComplianceCard .card-body { padding: 0.75rem !important; }
        #zoningComplianceCard h6 { font-size: 0.78rem; }
        #zoningComplianceCard .btn { font-size: 0.78rem; padding: 7px 14px; }
        #zoningComplianceCard .badge { font-size: 0.65rem; }

        /* Search panels: reduce spacing */
        .search-panel.mb-4 { margin-bottom: 0.75rem !important; }
        .search-panel.mt-3 { margin-bottom: 0.75rem !important; }

        /* Legend */
        .search-panel .card-body.py-3 { padding: 0.4rem 0.6rem !important; }
        .search-panel .d-flex.flex-wrap.justify-content-center { gap: 8px !important; }
        .search-panel .d-flex.align-items-center > div { width: 22px !important; height: 9px !important; margin-right: 5px !important; }
        .search-panel .d-flex.align-items-center span { font-size: 0.65rem !important; }

        /* Map card header */
        .col-md-8 .card-header {
            flex-direction: row !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 8px 10px !important;
            gap: 6px;
        }
        .col-md-8 .card-header span.fw-bold { font-size: 0.72rem; white-space: nowrap; flex-shrink: 1; }
        .col-md-8 .card-header span.fw-bold i { margin-left: 0 !important; }
        .col-md-8 .card-header .badge { font-size: 0.62rem !important; padding: 3px 7px !important; margin-right: 0 !important; flex-shrink: 0; white-space: nowrap; }
    }

    /* --- 320px: Small Mobile --- */
    @media (max-width: 320px) {

        .p-4 { padding: 0.5rem !important; }

        /* Page header */
        .d-flex.justify-content-between.align-items-center.mb-4 h2 { font-size: 0.95rem; }
        .d-flex.justify-content-between.align-items-center.mb-4 h2 span { width: 28px !important; height: 28px !important; }
        .d-flex.justify-content-between.align-items-center.mb-4 h2 i { font-size: 0.85rem !important; }
        .d-flex.justify-content-between.align-items-center.mb-4 p { font-size: 0.68rem; }

        /* Map: minimal height */
        #map { height: 240px !important; border-radius: 6px !important; }

        /* Map card header: stack title above badge */
        .col-md-8 .card-header { flex-direction: column !important; align-items: flex-start !important; gap: 4px; padding: 8px 10px !important; }
        .col-md-8 .card-header span.fw-bold { font-size: 0.7rem; }
        .col-md-8 .card-header span.fw-bold i { margin-left: 0 !important; }
        .col-md-8 .card-header .badge { font-size: 0.6rem !important; padding: 3px 8px !important; margin-right: 0 !important; }

        /* Legend */
        .search-panel .card-body.py-3 { padding: 0.4rem 0.5rem !important; }
        .search-panel .d-flex.flex-wrap.justify-content-center { gap: 5px !important; }
        .search-panel .d-flex.align-items-center > div { width: 16px !important; height: 7px !important; margin-right: 3px !important; }
        .search-panel .d-flex.align-items-center span { font-size: 0.58rem !important; }

        /* Search header */
        .search-header { padding: 8px 10px; }
        .search-header h6 { font-size: 0.75rem; }
        .search-header small { font-size: 0.55rem !important; }

        /* Form */
        .card-body.p-4 { padding: 0.6rem !important; }
        .form-control-lgu { font-size: 0.72rem; padding: 5px 8px; border-radius: 4px; }
        .section-label { font-size: 0.58rem; margin-bottom: 2px; letter-spacing: 0.3px; }
        .mb-2 { margin-bottom: 0.25rem !important; }
        .mb-3 { margin-bottom: 0.4rem !important; }
        .row.g-2 { --bs-gutter-x: 0.25rem; --bs-gutter-y: 0.25rem; }

        /* Search button */
        .btn-lgu-search { padding: 7px; font-size: 0.75rem; }
        .btn-lgu-search i { margin-right: 4px !important; }

        /* Analysis */
        .analysis-inner { padding: 8px; }
        .table-analysis td { font-size: 0.68rem; padding: 4px 0; }
        #analysisResults .text-center.py-4 { padding: 0.5rem !important; }
        #analysisResults .text-center.py-4 p { font-size: 0.68rem; }
        #analysisResults .fs-3 { font-size: 1rem !important; }

        /* Zoning overlay */
        .form-select.form-control-lgu { font-size: 0.72rem; padding: 4px 7px; }
        .form-check-label.small { font-size: 0.68rem; }
        .overlay-item { padding: 0.25rem 0.4rem !important; }

        /* Compliance card: stack info + button */
        #zoningComplianceCard { margin-top: 8px; }
        #zoningComplianceCard .card-body { padding: 0.6rem !important; }
        #zoningComplianceCard .d-flex.justify-content-between { flex-direction: column; gap: 8px; }
        #zoningComplianceCard h6 { font-size: 0.72rem; }
        #zoningComplianceCard #complianceStatusText { font-size: 0.68rem; }
        #zoningComplianceCard #complianceActionBtn .btn { font-size: 0.72rem; padding: 6px 10px; width: 100%; text-align: center; }
        #zoningComplianceCard .badge { font-size: 0.6rem; padding: 2px 5px; }

        /* Card headers */
        .card-header h6 { font-size: 0.72rem; }

        /* Search panels */
        .search-panel.mb-4 { margin-bottom: 0.6rem !important; }
        .search-panel.mt-3 { margin-top: 0.6rem !important; margin-bottom: 0.75rem !important; }
    }
</style>

<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0 d-flex align-items-center gap-2" style="color: #1e293b;">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle">
                    <i class="bi bi-map" style="color:#14b8a6;font-size:1.9rem;"></i>
                </span>
                GIS Map
            </h2>
            <p class="text-muted mb-0"><?php echo t_map('page_subheading', $translations, $lang); ?></p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="search-panel mb-4">
                <div class="search-header">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-search me-2"></i><?php echo t_map('search_panel_title', $translations, $lang); ?></h6>
                    <small class="opacity-75" style="font-size: 0.65rem;"><?php echo t_map('search_panel_sub', $translations, $lang); ?></small>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <span class="section-label"><?php echo t_map('lbl_geo_coords', $translations, $lang); ?></span>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <input type="text" class="form-control form-control-lgu" name="search_lat" placeholder="<?php echo t_map('ph_latitude', $translations, $lang); ?>" value="<?= htmlspecialchars($appLat ?? ''); ?>">
                            </div>
                            <div class="col-6">
                                <input type="text" class="form-control form-control-lgu" name="search_lng" placeholder="<?php echo t_map('ph_longitude', $translations, $lang); ?>" value="<?= htmlspecialchars($appLng ?? ''); ?>">
                            </div>
                        </div>

                        <span class="section-label"><?php echo t_map('lbl_admin_info', $translations, $lang); ?></span>
                        <div class="mb-2">
                            <input type="text" class="form-control form-control-lgu" name="barangay" placeholder="<?php echo t_map('ph_barangay', $translations, $lang); ?>" value="<?= htmlspecialchars($_POST['barangay'] ?? $urlBarangay); ?>">
                        </div>
                        <div class="mb-2">
                            <input type="text" class="form-control form-control-lgu" name="street" placeholder="<?php echo t_map('ph_street', $translations, $lang); ?>" value="<?= htmlspecialchars($_POST['street'] ?? $urlStreet); ?>">
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6"><input type="text" class="form-control form-control-lgu" name="block" placeholder="<?php echo t_map('ph_block', $translations, $lang); ?>" value="<?= htmlspecialchars($_POST['block'] ?? $urlBlock); ?>"></div>
                            <div class="col-6"><input type="text" class="form-control form-control-lgu" name="lot_number" placeholder="<?php echo t_map('ph_lot', $translations, $lang); ?>" value="<?= htmlspecialchars($_POST['lot_number'] ?? $urlLot); ?>"></div>
                        </div>

                        <button type="submit" name="search" class="btn btn-lgu-search w-100 rounded-3 mt-2 shadow-sm">
                            <i class="bi bi-geo-alt-fill me-2"></i><?php echo t_map('btn_locate', $translations, $lang); ?>
                        </button>
                    </form>
                </div>
            </div>

            <div class="search-panel mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="m-0 font-weight-bold text-primary small text-uppercase fw-bold ms-3">
                        <i class="bi bi-graph-up-arrow me-2"></i><?php echo t_map('analysis_title', $translations, $lang); ?>
                    </h6>
                </div>
                <div id="analysisResults" class="card-body pt-0">
                    <div class="text-center py-4 text-muted border rounded-3 bg-light">
                        <i class="bi bi-mouse2 fs-3 d-block mb-2 opacity-50"></i>
                        <p class="small mb-0 px-3"><?php echo t_map('analysis_placeholder', $translations, $lang); ?></p>
                    </div>
                </div>
            </div>

            <div class="search-panel">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="m-0 font-weight-bold text-dark small text-uppercase fw-bold ms-3">
                        <i class="bi bi-layers-half me-2"></i><?php echo t_map('overlay_title', $translations, $lang); ?>
                    </h6>
                </div>
                <div class="card-body pt-0">
                    <div class="mb-3">
                        <select id="zoningFilter" class="form-select form-select-sm form-control-lgu">
                            <option value=""><?php echo t_map('overlay_show_all', $translations, $lang); ?></option>
                            <?php foreach ($zoningClassifications as $z): ?>
                                <option value="<?= $z['id'] ?>"><?= htmlspecialchars($z['code']) ?> (<?= htmlspecialchars($z['name']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check form-switch overlay-item d-flex align-items-center justify-content-between p-2 mb-1 rounded hover-bg">
                        <label class="form-check-label small fw-bold text-muted mb-0 cursor-pointer" for="toggleParcels">
                            <?php echo t_map('overlay_boundary', $translations, $lang); ?>
                        </label>
                        <input class="form-check-input cursor-pointer" type="checkbox" id="toggleParcels" checked>
                    </div>
                </div>
            </div>

            <?php
// In-update nating function para kasama ang Color Name at Hex
function getZoneDetails($code) {
    $code = strtoupper($code);
    if (in_array($code, ['R1', 'R2', 'R-3'])) 
        return ['color' => '#ffff00', 'label' => 'Yellow - Residential'];
    if (in_array($code, ['C1', 'C2', 'C-3'])) 
        return ['color' => '#ff0000', 'label' => 'Red - Commercial'];
    if (in_array($code, ['I1', 'I-2'])) 
        return ['color' => '#9c27b0', 'label' => 'Purple - Industrial'];
    if ($code === 'INST') 
        return ['color' => '#0000ff', 'label' => 'Blue - Institutional'];
    if ($code === 'PRK') 
        return ['color' => '#4caf50', 'label' => 'Green - Parks'];
    if ($code === 'S-CZ') 
        return ['color' => '#795548', 'label' => 'Brown - Special Control'];
    
    return ['color' => '#6c757d', 'label' => 'Gray - Other'];
}
?>

<div class="search-panel mt-3">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <?php foreach ($zoningClassifications as $z): 
                // Kunin ang details (hex at color name) base sa code
                $details = getZoneDetails($z['code']); 
            ?>
                <div class="d-flex align-items-center" title="<?= $details['label'] ?>">
                    <div style="width: 25px; height: 10px; background-color: <?= $details['color'] ?>; border-radius: 2px; margin-right: 8px; border: 1px solid rgba(0,0,0,0.1);"></div>
                    <span class="small text-secondary fw-bold" style="font-size: 0.7rem;">
                        <?= htmlspecialchars($z['code']) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
    
        </div>

        <div class="col-md-8">
            <div class="search-panel border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                    <span class="fw-bold text-dark small"><i class="bi bi-map-fill me-2 text-primary ms-3"></i><?php echo t_map('map_card_title', $translations, $lang); ?></span>
                    <span class="badge bg-light text-primary border border-primary-subtle px-3 py-2 me-3"><?php echo t_map('map_card_badge', $translations, $lang); ?></span>
                </div>
                <div class="card-body p-0">
                    <div id="map"></div>
                </div>
            </div>

            <div id="zoningComplianceCard" class="card border-0 shadow-sm mt-3">
                <div class="card-body p-4 bg-white rounded-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div id="complianceInfo">
                            <h6 class="fw-bold mb-1 text-uppercase small text-primary"><i class="bi bi-shield-check me-2"></i><?php echo t_map('compliance_title', $translations, $lang); ?></h6>
                            <p id="complianceStatusText" class="text-muted small mb-0"><?php echo t_map('compliance_placeholder', $translations, $lang); ?></p>
                        </div>
                        <div id="complianceActionBtn"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
<script src="https://unpkg.com/@turf/turf@6/turf.min.js"></script>

<script>
    // Translated strings for JavaScript
    const LANG = {
        zoningRecord:   <?php echo json_encode(t_map('js_zoning_record', $translations, $lang)); ?>,
        customArea:     <?php echo json_encode(t_map('js_custom_area', $translations, $lang)); ?>,
        unknownZone:    <?php echo json_encode(t_map('js_unknown_zone', $translations, $lang)); ?>,
        analysisLat:    <?php echo json_encode(t_map('js_analysis_lat', $translations, $lang)); ?>,
        analysisLng:    <?php echo json_encode(t_map('js_analysis_lng', $translations, $lang)); ?>,
        analysisZone:   <?php echo json_encode(t_map('js_analysis_zone', $translations, $lang)); ?>,
        bufferBtn:      <?php echo json_encode(t_map('js_buffer_btn', $translations, $lang)); ?>,
        confirmSend:    <?php echo json_encode(t_map('js_confirm_send', $translations, $lang)); ?>,
        noAppId:        <?php echo json_encode(t_map('js_no_app_id', $translations, $lang)); ?>,
        coords:         <?php echo json_encode(t_map('js_coords', $translations, $lang)); ?>,
        zoningZone:     <?php echo json_encode(t_map('js_zoning_zone', $translations, $lang)); ?>,
        landRecord:     <?php echo json_encode(t_map('js_land_record', $translations, $lang)); ?>,
        statusCheck:    <?php echo json_encode(t_map('js_status_check', $translations, $lang)); ?>,
        pointIs:        <?php echo json_encode(t_map('js_point_is', $translations, $lang)); ?>,
    };
    // Role-based access control
    const USER_ROLE = <?php echo json_encode($_SESSION['role'] ?? ''); ?>;
    const CAN_SUBMIT_COMPLIANCE = !['assessor', 'inspector', 'applicant'].includes(USER_ROLE);
    const qcBounds = L.latLngBounds(
        L.latLng(14.5800, 120.9800), // SW
        L.latLng(14.7700, 121.1500)  // NE
    );

    const map = L.map('map', {
        zoomControl: false,
        maxBounds: qcBounds,         // Lock view to QC
        maxBoundsViscosity: 1.0      // Prevent panning outside
    }).setView([14.6760, 121.0437], 13);

    L.control.zoom({position: 'topright'}).addTo(map);

    // ── BASE TILE LAYERS ──────────────────────────────────────────────────────
    const tileRoad = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: 'LGU GIS Unit',
        maxZoom: 19
    });

    const tileSatellite = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'LGU GIS Unit',
        maxZoom: 19
    });

    // Hybrid = Esri satellite + OSM road labels stacked on top
    const tileHybridBase = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'LGU GIS Unit',
        maxZoom: 19
    });
    const tileHybridLabels = L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: 'LGU GIS Unit',
        maxZoom: 19,
        opacity: 0.6
    });
    const tileHybrid = L.layerGroup([tileHybridBase, tileHybridLabels]);

    const tileTerrain = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
        attribution: 'LGU GIS Unit',
        maxZoom: 17
    });

    const baseLayers = {
        '<i class="bi bi-map" style="margin-right:6px;"></i> Road':      tileRoad,
        '<i class="bi bi-globe" style="margin-right:6px;"></i> Satellite': tileSatellite,
        '<i class="bi bi-layers" style="margin-right:6px;"></i> Hybrid':   tileHybrid,
        '<i class="bi bi-triangle" style="margin-right:6px;"></i> Terrain': tileTerrain,
    };

    // Add Road as the default base layer
    tileRoad.addTo(map);

    // Layer control — base switcher only (overlays handled separately below)
    L.control.layers(baseLayers, {}, {
        position: 'bottomright',
        collapsed: false
    }).addTo(map);

    // Data from PHP - Ensure numeric conversion for coordinates
    const allParcelsData = <?= json_encode($allParcels) ?>;
    const targetAppId = "<?= $targetAppId ?>";
    const appLat = parseFloat("<?= $appLat ?>");
    const appLng = parseFloat("<?= $appLng ?>");
    const dbLot = "<?= htmlspecialchars($urlLot) ?>";
    const dbBlock = "<?= htmlspecialchars($urlBlock) ?>";
    let complianceMarker = null;
    const activeOverlays = {}; 

    // Layer Styles
    const layerStyles = {
        'flood_map':   { color: '#007bff', fillColor: '#007bff', fillOpacity: 0.4, weight: 1 },
        'fault_lines': { color: '#dc3545', weight: 4, dashArray: '10, 10' },
        'drainage':    { color: '#17a2b8', weight: 3 },
        'land_use':    { color: '#28a745', fillOpacity: 0.2, weight: 1 },
        'roads':       { color: '#fd7e14', weight: 3 }
    };

    // Main Parcel Layer with Dynamic Zoning Colors
    var parcelLayer = L.geoJSON(null, {
        style: function(feature) {
            const code = feature.properties.zone_code;
            let color = '#4e73df'; // Default Blue

            if (code === 'R1' || code === 'R2' || code === 'R-3') color = '#ffff00'; // Yellows for Residential
            else if (code === 'C1' || code === 'C2' || code === 'C-3') color = '#ff0000'; // Reds for Commercial
            else if (code === 'I1' || code === 'I-2') color = '#9c27b0'; // Purple for Industrial
            else if (code === 'INST') color = '#0000ff'; // Blue for Institutional
            else if (code === 'PRK') color = '#4caf50'; // Green for Parks
            else if (code === 'S-CZ') color = '#795548'; // Brown for Special Control

            return { 
                fillColor: color, 
                weight: 1.5, 
                color: '#fff', 
                fillOpacity: 0.5 
            };
        },
        onEachFeature: function(feature, layer) {
            layer.on('click', function(e) { 
                L.DomEvent.stopPropagation(e);
                showAnalysis(feature, LANG.zoningRecord + (feature.properties.zone_code || 'N/A'));
                checkSpatialCompliance(e.latlng.lat, e.latlng.lng, feature);
            });
        }
    }).addTo(map);

    // Drawing Controls
    var drawnItems = new L.FeatureGroup().addTo(map);
    var drawControl = new L.Control.Draw({
        edit: { featureGroup: drawnItems },
        draw: { polyline: false, circlemarker: false, circle: false }
    });
    map.addControl(drawControl);

    map.on(L.Draw.Event.CREATED, function (e) {
        drawnItems.clearLayers();
        var layer = e.layer;
        drawnItems.addLayer(layer);
        showAnalysis(layer.toGeoJSON(), LANG.customArea);
    });

    map.on('click', function(e) {
        checkSpatialCompliance(e.latlng.lat, e.latlng.lng, null);
    });

    // --- CORE FUNCTIONS ---

function checkSpatialCompliance(lat, lng, clickedFeature) {
    if (complianceMarker) map.removeLayer(complianceMarker);
    complianceMarker = L.marker([lat, lng]).addTo(map);

    let foundParcel = clickedFeature;
    
    // 1. Point-in-Polygon detection
    if (!foundParcel) {
        const point = turf.point([lng, lat]);
        parcelLayer.eachLayer(layer => {
            try {
                if (turf.booleanPointInPolygon(point, layer.toGeoJSON())) {
                    foundParcel = layer.feature;
                }
            } catch(err) { console.error("Turf Error:", err); }
        });
    }

    const card = document.getElementById('zoningComplianceCard');
    const text = document.getElementById('complianceStatusText');
    const btnArea = document.getElementById('complianceActionBtn');
    if(card) card.style.display = 'block';

    // 2. Zone Name Extraction
    let zoneName = LANG.unknownZone;
    if (foundParcel) {
        const props = foundParcel.properties;
        zoneName = props.zone || props.zone_code || props.ZONE_CODE || props.zoning_name || props.classification || props.NAME || LANG.unknownZone;
    }

    const isCompliant = (zoneName !== LANG.unknownZone) ? "compliant" : "non_compliant";
    const badgeClass = (isCompliant === "compliant") ? "bg-success" : "bg-danger";
    
    // 3. PROFESSIONAL DATA POINTS (Line-by-line format)
    const finalLot = dbLot || (foundParcel && foundParcel.properties.lot ? foundParcel.properties.lot : 'N/A');
    const finalBlock = dbBlock || (foundParcel && foundParcel.properties.block ? foundParcel.properties.block : 'N/A');
    
    let analysisText = `${LANG.coords}: [${lat.toFixed(6)}, ${lng.toFixed(6)}]\n`;
    analysisText += `${LANG.zoningZone}: ${zoneName}\n`;
    analysisText += `${LANG.landRecord}: Lot ${finalLot}, Block ${finalBlock}\n`;
    analysisText += LANG.statusCheck;

    // 4. Update UI Badge
    if(text) text.innerHTML = `<span class="badge ${badgeClass} mb-1">${isCompliant.toUpperCase()}</span><br>${LANG.pointIs} <b>${zoneName}</b>.`;

    // 5. Form Submission Logic
    if (targetAppId && targetAppId !== "" && targetAppId !== "null" && btnArea) {
        const parcelDatabaseId = foundParcel ? (foundParcel.properties.id || "") : ""; 

        if (CAN_SUBMIT_COMPLIANCE) {
            btnArea.innerHTML = `
                <form action="../permit/view.php?id=${targetAppId}" method="POST">
                    <input type="hidden" name="action" value="update_compliance">
                    <input type="hidden" name="compliance_status" value="${isCompliant.toLowerCase()}">
                    <input type="hidden" name="zoning_type" value="${zoneName}">
                    <input type="hidden" name="parcel_id" value="${parcelDatabaseId}"> 
                    <input type="hidden" name="technical_analysis" value="${analysisText}">
                    <button type="submit" class="btn ${isCompliant === 'compliant' ? 'btn-success' : 'btn-danger'} fw-bold px-4 shadow-sm">
                        ${LANG.confirmSend}
                    </button>
                </form>`;
        } else {
            btnArea.innerHTML = `<span class="text-muted small"><i class="bi bi-eye me-1"></i> View only — compliance submission not permitted for your role.</span>`;
        }
    } else if(btnArea) {
        btnArea.innerHTML = `<span class="text-danger small"><i class="bi bi-exclamation-triangle"></i> ${LANG.noAppId}</span>`;
    }
}

    window.generateBuffer = function(lat, lng, meters) {
        if (window.currentBufferLayer) map.removeLayer(window.currentBufferLayer);
        try {
            const point = turf.point([lng, lat]);
            const buffered = turf.buffer(point, meters, { units: 'meters' });
            window.currentBufferLayer = L.geoJSON(buffered, {
                style: { color: '#dc3545', weight: 2, fillOpacity: 0.2, dashArray: '5, 10' }
            }).addTo(map);
            map.fitBounds(window.currentBufferLayer.getBounds());
        } catch (e) {
            console.error("Buffer error:", e);
        }
    };

    function showAnalysis(geojson, title) {
        const props = geojson.properties || {};
        const container = document.getElementById('analysisResults');
        if(!container) return;

        let html = `<div class="analysis-inner shadow-sm border-0">
                        <span class="badge bg-primary mb-2">${title.toUpperCase()}</span>
                        <table class="table table-sm table-borderless table-analysis mb-0"><tbody>`;
        
        if (geojson.geometry && geojson.geometry.type === 'Point') {
             html += `<tr><td class="text-muted">${LANG.analysisLat}</td><td class="text-end fw-bold">${geojson.geometry.coordinates[1].toFixed(6)}</td></tr>
                      <tr><td class="text-muted">${LANG.analysisLng}</td><td class="text-end fw-bold">${geojson.geometry.coordinates[0].toFixed(6)}</td></tr>`;
        } else {
            const zName = props.zone || props.zone_code || props.zoning_name || props.classification || 'N/A';
            html += `<tr><td class="text-muted">${LANG.analysisZone}</td><td class="text-end text-success fw-bold">${zName}</td></tr>`;
            if(props.latitude && props.longitude) {
                html += `<tr><td colspan="2">
                    <button onclick="generateBuffer(${props.latitude}, ${props.longitude}, 20)" class="btn btn-xs btn-outline-danger w-100 mt-2">${LANG.bufferBtn}</button>
                </td></tr>`;
            }
        }
        container.innerHTML = html + `</tbody></table></div>`;
    }

    // --- INITIAL DATA LOAD ---
    if(Array.isArray(allParcelsData)) {
        allParcelsData.forEach(p => {
            if (p.geom_json) {
                try {
                    let geo = JSON.parse(p.geom_json);
                    geo.properties = { 
                        id: p.id, 
                        lot: p.lot_number, 
                        block: p.block, 
                        zone: p.zoning_name, 
                        zone_code: p.zoning_code || p.zoning_id, 
                        brgy: p.barangay, 
                        street: p.street_name
                    };
                    parcelLayer.addData(geo);
                } catch (e) { console.error("Error parsing parcel:", e); }
            }
        });
    }

    // --- EVENT LISTENERS ---
    document.getElementById('zoningFilter').addEventListener('change', function(e) {
        const val = e.target.value;
        parcelLayer.clearLayers();
        allParcelsData.forEach(p => {
            if (p.geom_json && (val === "" || p.zoning_id == val)) {
                let geo = JSON.parse(p.geom_json);
                geo.properties = { 
                    id: p.id, lot: p.lot_number, block: p.block, 
                    zone: p.zoning_name, zone_code: (geo.properties && geo.properties.zone_code) ? geo.properties.zone_code : p.zoning_id,
                    brgy: p.barangay, street: p.street_name 
                };
                parcelLayer.addData(geo);
            }
        });
    });

    document.querySelectorAll('.spatial-layer-toggle').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const key = this.value;
            if (this.checked) {
                fetch(`../modules/GISMapping/gis_action.php?action=get_layer&id=${key}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.features) {
                            activeOverlays[key] = L.geoJSON(data, { 
                                style: layerStyles[key] || { color: 'gray' }
                            }).addTo(map);
                            map.fitBounds(activeOverlays[key].getBounds());
                        }
                    });
            } else {
                if (activeOverlays[key]) {
                    map.removeLayer(activeOverlays[key]);
                    delete activeOverlays[key];
                }
            }
        });
    });

    // Auto-locate logic
    <?php if ($selectedParcel): ?>
        const sGeo = JSON.parse(<?= json_encode($selectedParcel['geom_json']) ?>);
        L.geoJSON(sGeo, { style: { color: '#ffd600', weight: 5, fillOpacity: 0.6 } }).addTo(map);
        map.fitBounds(L.geoJSON(sGeo).getBounds());
        checkSpatialCompliance(turf.center(sGeo).geometry.coordinates[1], turf.center(sGeo).geometry.coordinates[0], {properties: {id: '<?= $selectedParcel['id'] ?>', zone: '<?= $selectedParcel['zoning_name'] ?>', lot: '<?= $selectedParcel['lot_number'] ?>', block: '<?= $selectedParcel['block'] ?>'}});
    <?php elseif ($appLat && $appLng): ?>
        map.setView([appLat, appLng], 18);
        checkSpatialCompliance(appLat, appLng, null);
    <?php endif; ?>
</script>

<?php include __DIR__ . '/../admin/footer.php'; ?>
<?php
// GIS Mapping & Zoning Analysis
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../modules/GISMapping/GISController.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireRole(['admin', 'zoning_officer', 'building_official', 'assessor', 'inspector']);


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

include __DIR__ . '/../admin/header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css"/>

<style>
    :root { --lgu-blue: #1a237e; --lgu-accent: #ffd600; --bg-light: #f8f9fc; }
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
</style>

<div class="p-4">
    <div class="row">
        <div class="col-md-4">
            <div class="search-panel mb-4">
                <div class="search-header">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-search me-2"></i>Location Locator</h6>
                    <small class="opacity-75" style="font-size: 0.65rem;">Spatial Coordinate Search</small>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <span class="section-label">Geographic Coordinates</span>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <input type="text" class="form-control form-control-lgu" name="search_lat" placeholder="Latitude" value="<?= htmlspecialchars($appLat ?? ''); ?>">
                            </div>
                            <div class="col-6">
                                <input type="text" class="form-control form-control-lgu" name="search_lng" placeholder="Longitude" value="<?= htmlspecialchars($appLng ?? ''); ?>">
                            </div>
                        </div>

                        <span class="section-label">Administrative Info</span>
                        <div class="mb-2">
                            <input type="text" class="form-control form-control-lgu" name="barangay" placeholder="Barangay" value="<?= htmlspecialchars($_POST['barangay'] ?? $urlBarangay); ?>">
                        </div>
                        <div class="mb-2">
                            <input type="text" class="form-control form-control-lgu" name="street" placeholder="Street Name" value="<?= htmlspecialchars($_POST['street'] ?? $urlStreet); ?>">
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6"><input type="text" class="form-control form-control-lgu" name="block" placeholder="Block No." value="<?= htmlspecialchars($_POST['block'] ?? $urlBlock); ?>"></div>
                            <div class="col-6"><input type="text" class="form-control form-control-lgu" name="lot_number" placeholder="Lot No." value="<?= htmlspecialchars($_POST['lot_number'] ?? $urlLot); ?>"></div>
                        </div>

                        <button type="submit" name="search" class="btn btn-lgu-search w-100 rounded-3 mt-2 shadow-sm">
                            <i class="bi bi-geo-alt-fill me-2"></i>LOCATE COORDINATES
                        </button>
                    </form>
                </div>
            </div>

            <div class="search-panel mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="m-0 font-weight-bold text-primary small text-uppercase fw-bold ms-3">
                        <i class="bi bi-graph-up-arrow me-2"></i>Technical Analysis
                    </h6>
                </div>
                <div id="analysisResults" class="card-body pt-0">
                    <div class="text-center py-4 text-muted border rounded-3 bg-light">
                        <i class="bi bi-mouse2 fs-3 d-block mb-2 opacity-50"></i>
                        <p class="small mb-0 px-3">Select a point on the map to analyze zoning.</p>
                    </div>
                </div>
            </div>

            <div class="search-panel">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="m-0 font-weight-bold text-dark small text-uppercase fw-bold ms-3">
                        <i class="bi bi-layers-half me-2"></i>Zoning Overlay
                    </h6>
                </div>
                <div class="card-body pt-0">
                    <div class="mb-3">
                        <select id="zoningFilter" class="form-select form-select-sm form-control-lgu">
                            <option value="">Show All Zoning Types</option>
                            <?php foreach ($zoningClassifications as $z): ?>
                                <option value="<?= $z['id'] ?>"><?= htmlspecialchars($z['code']) ?> (<?= htmlspecialchars($z['name']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check form-switch overlay-item d-flex align-items-center justify-content-between p-2 mb-1 rounded hover-bg">
                        <label class="form-check-label small fw-bold text-muted mb-0 cursor-pointer" for="toggleParcels">
                            Boundary Lines
                        </label>
                        <input class="form-check-input cursor-pointer" type="checkbox" id="toggleParcels" checked>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="search-panel border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                    <span class="fw-bold text-dark small"><i class="bi bi-map-fill me-2 text-primary ms-3"></i>GEOSPATIAL INTERFACE</span>
                    <span class="badge bg-light text-primary border border-primary-subtle px-3 py-2 me-3">Active GIS Node</span>
                </div>
                <div class="card-body p-0">
                    <div id="map"></div>
                </div>
            </div>

            <div id="zoningComplianceCard" class="card border-0 shadow-sm mt-3">
                <div class="card-body p-4 bg-white rounded-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div id="complianceInfo">
                            <h6 class="fw-bold mb-1 text-uppercase small text-primary"><i class="bi bi-shield-check me-2"></i>Spatial Zoning Compliance</h6>
                            <p id="complianceStatusText" class="text-muted small mb-0">Select a point to evaluate...</p>
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
    // 1. Initialize Map with QC Boundary Lock (Primary Initialization)
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
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { 
        attribution: 'LGU GIS Unit' 
    }).addTo(map);

    // Data from PHP - Ensure numeric conversion for coordinates
    const allParcelsData = <?= json_encode($allParcels) ?>;
    const targetAppId = "<?= $targetAppId ?>";
    const appLat = parseFloat("<?= $appLat ?>");
    const appLng = parseFloat("<?= $appLng ?>");
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
                showAnalysis(feature, "Zoning Record: " + (feature.properties.zone_code || 'N/A'));
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
        showAnalysis(layer.toGeoJSON(), "Custom Area");
    });

    map.on('click', function(e) {
        checkSpatialCompliance(e.latlng.lat, e.latlng.lng, null);
    });

    // --- CORE FUNCTIONS ---

    function checkSpatialCompliance(lat, lng, clickedFeature) {
        if (complianceMarker) map.removeLayer(complianceMarker);
        complianceMarker = L.marker([lat, lng]).addTo(map);

        let foundParcel = clickedFeature;
        
        // Point-in-Polygon detection
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

        let zoneName = "Unknown/Outside Boundary";
        if (foundParcel) {
            const props = foundParcel.properties;
            zoneName = props.zone || props.zone_code || props.ZONE_CODE || props.zoning_name || props.classification || props.NAME || "Unknown/Outside Boundary";
        }

        const isCompliant = (zoneName !== "Unknown/Outside Boundary") ? "compliant" : "non_compliant";
        const badgeClass = (isCompliant === "compliant") ? "bg-success" : "bg-danger";
        
        let analysisText = `Spatial verification performed on coordinates [${lat.toFixed(6)}, ${lng.toFixed(6)}]. `;
        if (zoneName !== "Unknown/Outside Boundary") {
            analysisText += `The project site is verified to be within the ${zoneName} zone. `;
            if (foundParcel && foundParcel.properties.lot) {
                analysisText += `Matched cadastral record Lot ${foundParcel.properties.lot}, Block ${foundParcel.properties.blk}. `;
            }
            analysisText += `Automated spatial check indicates the location is consistent with LGU land use mapping.`;
        } else {
            analysisText += `CRITICAL: The project coordinates fall outside the established zoning map or parcels. Manual verification of boundaries is required.`;
        }

        if(text) text.innerHTML = `<span class="badge ${badgeClass} mb-1">${isCompliant.toUpperCase()}</span><br>Point is <b>${zoneName}</b>.`;

        if (targetAppId && targetAppId !== "" && targetAppId !== "null" && btnArea) {
            const parcelDatabaseId = foundParcel ? (foundParcel.properties.id || "") : ""; 

            btnArea.innerHTML = `
                <form action="../permit/view.php?id=${targetAppId}" method="POST">
                    <input type="hidden" name="action" value="update_compliance">
                    <input type="hidden" name="compliance_status" value="${isCompliant.toLowerCase()}">
                    <input type="hidden" name="zoning_type" value="${zoneName}">
                    <input type="hidden" name="parcel_id" value="${parcelDatabaseId}"> 
                    <input type="hidden" name="technical_analysis" value="${analysisText}">
                    <button type="submit" class="btn ${isCompliant === 'compliant' ? 'btn-success' : 'btn-danger'} fw-bold px-4 shadow-sm">
                        CONFIRM & SEND TO APPLICATION
                    </button>
                </form>`;
        } else if(btnArea) {
            btnArea.innerHTML = `<span class="text-danger small"><i class="bi bi-exclamation-triangle"></i> No Application ID Linked</span>`;
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
             html += `<tr><td class="text-muted">Latitude</td><td class="text-end fw-bold">${geojson.geometry.coordinates[1].toFixed(6)}</td></tr>
                      <tr><td class="text-muted">Longitude</td><td class="text-end fw-bold">${geojson.geometry.coordinates[0].toFixed(6)}</td></tr>`;
        } else {
            const zName = props.zone || props.zone_code || props.zoning_name || props.classification || 'N/A';
            html += `<tr><td class="text-muted">Zoning Type</td><td class="text-end text-success fw-bold">${zName}</td></tr>`;
            if(props.latitude && props.longitude) {
                html += `<tr><td colspan="2">
                    <button onclick="generateBuffer(${props.latitude}, ${props.longitude}, 20)" class="btn btn-xs btn-outline-danger w-100 mt-2">Show 20m Buffer</button>
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
                        blk: p.block, 
                        zone: p.zoning_name, 
                        // Ensure zone_code uses the short code (R1, C1) for the DB lookup
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
                    id: p.id, lot: p.lot_number, blk: p.block, 
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
        checkSpatialCompliance(turf.center(sGeo).geometry.coordinates[1], turf.center(sGeo).geometry.coordinates[0], {properties: {id: '<?= $selectedParcel['id'] ?>', zone: '<?= $selectedParcel['zoning_name'] ?>', lot: '<?= $selectedParcel['lot_number'] ?>', blk: '<?= $selectedParcel['block'] ?>'}});
    <?php elseif ($appLat && $appLng): ?>
        map.setView([appLat, appLng], 18);
        checkSpatialCompliance(appLat, appLng, null);
    <?php endif; ?>
</script>

<?php include __DIR__ . '/../admin/footer.php'; ?>
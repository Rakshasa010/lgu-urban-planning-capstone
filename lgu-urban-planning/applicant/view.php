<?php
/**
 * View Application Details (Applicant)
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helper.php';
require_once __DIR__ . '/../modules/ApplicantSelfService/ApplicantController.php';

$auth = new Auth();
$auth->requireRole('applicant');

$applicantController = new ApplicantController();
$applicationId = $_GET['id'] ?? 0;
$application = $applicantController->getApplicationDetails($applicationId);

if (!$application) {
    header('Location: /lgu-urban-planning/applicant/applications.php');
    exit;
}

// ── i18n — reads language saved by settings.php ──────────────────────────────
$_vwLang = $_SESSION['locale_language'] ?? 'en_PH';

$_vwT = [
    'en_PH' => [
        'page_title'        => 'Application Details',
        'heading'           => 'Application Details',
        // Project info
        'sec_project'       => 'Project Information',
        'lbl_project_name'  => 'Project Name',
        'lbl_project_type'  => 'Project Type',
        'lbl_description'   => 'Description',
        // Location info
        'sec_location'      => 'Location Information',
        'lbl_lot_number'    => 'Lot Number',
        'lbl_block'         => 'Block Number',
        'lbl_street'        => 'Street',
        'lbl_barangay'      => 'Barangay',
        'lbl_parcel_id'     => 'Parcel ID',
        'lbl_coordinates'   => 'Coordinates',
        // Zoning compliance
        'sec_zoning'        => 'Zoning Compliance',
        'lbl_status'        => 'Status',
        // Documents card
        'card_documents'    => 'Documents',
        'no_documents'      => 'No documents uploaded yet.',
        'col_doc_type'      => 'Document Type',
        'col_file_name'     => 'File Name',
        'col_uploaded'      => 'Uploaded',
        'col_action'        => 'Action',
        'btn_download'      => 'Download',
        'btn_view'          => 'View',
        // Status history card
        'card_history'      => 'Status History',
        'no_remarks'        => 'No remarks',
        'lbl_by'            => 'by',
        // Back button
        'btn_back'          => 'Back to Applications',
    ],
    'fil' => [
        'page_title'        => 'Mga Detalye ng Aplikasyon',
        'heading'           => 'Mga Detalye ng Aplikasyon',
        // Project info
        'sec_project'       => 'Impormasyon ng Proyekto',
        'lbl_project_name'  => 'Pangalan ng Proyekto',
        'lbl_project_type'  => 'Uri ng Proyekto',
        'lbl_description'   => 'Paglalarawan',
        // Location info
        'sec_location'      => 'Impormasyon ng Lokasyon',
        'lbl_lot_number'    => 'Numero ng Lote',
        'lbl_block'         => 'Numero ng Bloke',
        'lbl_street'        => 'Kalye',
        'lbl_barangay'      => 'Barangay',
        'lbl_parcel_id'     => 'ID ng Parsela',
        'lbl_coordinates'   => 'Koordinada',
        // Zoning compliance
        'sec_zoning'        => 'Pagsunod sa Zoning',
        'lbl_status'        => 'Katayuan',
        // Documents card
        'card_documents'    => 'Mga Dokumento',
        'no_documents'      => 'Wala pang mga dokumentong na-upload.',
        'col_doc_type'      => 'Uri ng Dokumento',
        'col_file_name'     => 'Pangalan ng File',
        'col_uploaded'      => 'Ini-upload',
        'col_action'        => 'Aksyon',
        'btn_download'      => 'I-download',
        'btn_view'          => 'Tingnan',
        // Status history card
        'card_history'      => 'Kasaysayan ng Katayuan',
        'no_remarks'        => 'Walang mga komento',
        'lbl_by'            => 'ni',
        // Back button
        'btn_back'          => 'Bumalik sa mga Aplikasyon',
    ],
];

function _vwt(string $key): string {
    global $_vwT, $_vwLang;
    return $_vwT[$_vwLang][$key] ?? $_vwT['en_PH'][$key] ?? $key;
}

$pageTitle = _vwt('page_title');
$isAuthPage = true;
include __DIR__ . '/../user/header.php';
?>

<style>
/* ── Status History Pagination ── */
#historyPagination {
    border-top: 1px solid #dee2e6;
    padding-top: 0.75rem;
}



/* ── Base layout ── */
.app-details-wrapper {
    padding: 1.5rem;
    max-width: 960px;
    margin: 0 auto;
}

.app-details-wrapper h2 {
    font-size: 1.5rem;
}

/* Card header: keep badge inline with title */
.card-header.d-flex {
    flex-wrap: wrap;
    gap: 0.5rem;
}

/* Documents table: allow horizontal scroll on narrow viewports */
.table-responsive-wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.table-responsive-wrapper .table {
    min-width: 480px;
}

/* Timeline items */
.timeline .border-start {
    word-break: break-word;
}

/* ── 768px – Tablets ── */
@media (max-width: 768px) {
    .app-details-wrapper {
        padding: 1rem;
    }

    .app-details-wrapper h2 {
        font-size: 1.3rem;
        margin-bottom: 1rem !important;
    }

    .card-header h5 {
        font-size: 1rem;
    }

    .card-body h6 {
        font-size: 0.95rem;
    }

    .card-body p,
    .card-body pre,
    .card-body small {
        font-size: 0.9rem;
    }

    /* Stack badge below title if needed */
    .card-header.d-flex {
        flex-direction: column;
        align-items: flex-start !important;
    }

    .badge {
        align-self: flex-start;
    }

    /* Tighten table cells */
    .table th,
    .table td {
        font-size: 0.85rem;
        padding: 0.4rem 0.5rem;
    }

    .btn-sm {
        font-size: 0.78rem;
        padding: 0.25rem 0.5rem;
    }

    .btn-secondary {
        width: 100%;
        text-align: center;
    }
}

/* ── 480px – Large Mobile ── */
@media (max-width: 480px) {
    .app-details-wrapper {
        padding: 0.75rem;
    }

    .app-details-wrapper h2 {
        font-size: 1.15rem;
    }

    .card {
        border-radius: 0.4rem;
    }

    .card-header {
        padding: 0.6rem 0.75rem;
    }

    .card-header h5 {
        font-size: 0.95rem;
        margin-bottom: 0;
    }

    .card-body {
        padding: 0.75rem;
    }

    .card-body h6 {
        font-size: 0.88rem;
        margin-top: 0.5rem;
    }

    .card-body p {
        font-size: 0.85rem;
        margin-bottom: 0.4rem;
    }

    .card-body pre {
        font-size: 0.78rem;
        white-space: pre-wrap;
        word-break: break-word;
    }

    /* Let label/value stack on very small rows */
    .card-body p strong {
        display: block;
        margin-bottom: 0.1rem;
        color: #555;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .table th,
    .table td {
        font-size: 0.78rem;
        padding: 0.35rem 0.4rem;
    }

    /* Timeline */
    .timeline .border-start {
        padding-left: 0.6rem !important;
        border-left-width: 2px !important;
    }

    .timeline strong {
        font-size: 0.85rem;
    }

    .timeline p {
        font-size: 0.82rem;
    }

    .timeline small {
        font-size: 0.75rem;
    }

    .btn-secondary {
        font-size: 0.85rem;
        padding: 0.45rem 1rem;
    }
}

/* ── 320px – Small Mobile ── */
@media (max-width: 320px) {
    .app-details-wrapper {
        padding: 0.5rem;
    }

    .app-details-wrapper h2 {
        font-size: 1rem;
    }

    .card-header {
        padding: 0.5rem 0.6rem;
    }

    .card-header h5 {
        font-size: 0.85rem;
    }

    .card-body {
        padding: 0.6rem;
    }

    .card-body h6 {
        font-size: 0.82rem;
    }

    .card-body p,
    .card-body p strong {
        font-size: 0.78rem;
    }

    .card-body pre {
        font-size: 0.72rem;
        padding: 0.5rem !important;
    }

    .badge {
        font-size: 0.7rem;
        padding: 0.2em 0.5em;
    }

    /* Fully wrap table text */
    .table-responsive-wrapper .table {
        min-width: 300px;
    }

    .table th,
    .table td {
        font-size: 0.72rem;
        padding: 0.28rem 0.3rem;
        white-space: normal;
        word-break: break-word;
    }

    .btn-sm {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
    }

    .btn-secondary {
        font-size: 0.8rem;
        padding: 0.4rem 0.75rem;
    }

    .timeline .border-start {
        padding-left: 0.5rem !important;
    }

    .timeline strong {
        font-size: 0.8rem;
    }

    .timeline p,
    .timeline small {
        font-size: 0.72rem;
    }
}
</style>

<div class="app-details-wrapper">
    <h2 class="mb-4"><?php echo _vwt('heading'); ?></h2>
    
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Application #<?php echo htmlspecialchars($application['application_number']); ?></h5>
            <span class="badge bg-<?php echo Helper::getStatusBadge($application['status']); ?>">
                <?php echo ucfirst(str_replace('_', ' ', $application['status'])); ?>
            </span>
        </div>
        <div class="card-body">
            <h6><?php echo _vwt('sec_project'); ?></h6>
            <p><strong><?php echo _vwt('lbl_project_name'); ?>:</strong> <?php echo htmlspecialchars($application['project_name']); ?></p>
            <p><strong><?php echo _vwt('lbl_project_type'); ?>:</strong> <?php echo htmlspecialchars($application['project_type'] ?? 'N/A'); ?></p>
            <p><strong><?php echo _vwt('lbl_description'); ?>:</strong> <?php echo htmlspecialchars($application['project_description'] ?? 'N/A'); ?></p>
            
            <hr>
            
            <h6><?php echo _vwt('sec_location'); ?></h6>
            <p><strong><?php echo _vwt('lbl_lot_number'); ?>:</strong> <?php echo htmlspecialchars($application['lot_number'] ?? 'N/A'); ?></p>
            <p><strong><?php echo _vwt('lbl_block'); ?>:</strong> <?php echo htmlspecialchars($application['block'] ?? 'N/A'); ?></p>
            <p><strong><?php echo _vwt('lbl_street'); ?>:</strong> <?php echo htmlspecialchars($application['street'] ?? 'N/A'); ?></p>
            <p><strong><?php echo _vwt('lbl_barangay'); ?>:</strong> <?php echo htmlspecialchars($application['barangay'] ?? 'N/A'); ?></p>
            <p><strong><?php echo _vwt('lbl_parcel_id'); ?>:</strong> <?php echo htmlspecialchars($application['parcel_id'] ?? 'N/A'); ?></p>
            <?php if (!empty($application['latitude']) && !empty($application['longitude'])): ?>
                <p><strong><?php echo _vwt('lbl_coordinates'); ?>:</strong> <?php echo htmlspecialchars($application['latitude']); ?>, <?php echo htmlspecialchars($application['longitude']); ?></p>
            <?php endif; ?>
            
            <?php 
            // Fix: Added null coalescing to prevent "Undefined array key" warning
            $zoningStatus = $application['zoning_compliance_status'] ?? 'pending';
            if ($zoningStatus !== 'pending'): ?>
                <hr>
                <h6><?php echo _vwt('sec_zoning'); ?></h6>
                <p><strong><?php echo _vwt('lbl_status'); ?>:</strong> 
                    <span class="badge bg-<?php echo $zoningStatus === 'compliant' ? 'success' : 'danger'; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $zoningStatus)); ?>
                    </span>
                </p>
                <?php 
                // Fix: Added safety check for the report content
                $report = $application['zoning_compliance_report'] ?? null;
                if ($report): ?>
                    <pre class="bg-light p-3"><?php echo htmlspecialchars($report); ?></pre>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card mb-3">
        <div class="card-header">
            <h5><?php echo _vwt('card_documents'); ?></h5>
        </div>
        <div class="card-body">
            <?php if (empty($application['documents'])): ?>
                <p class="text-muted"><?php echo _vwt('no_documents'); ?></p>
            <?php else: ?>
                <div class="table-responsive-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th><?php echo _vwt('col_doc_type'); ?></th>
                            <th><?php echo _vwt('col_file_name'); ?></th>
                            <th><?php echo _vwt('col_uploaded'); ?></th>
                            <th><?php echo _vwt('col_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($application['documents'] as $doc): ?>
                        <tr>
                            <td><?php echo ucfirst(str_replace('_', ' ', $doc['document_type'])); ?></td>
                            <td><?php echo htmlspecialchars($doc['file_name']); ?></td>
                            <td><?php echo Helper::formatDateTime($doc['created_at']); ?></td>
                            <td>
                                <?php
                                    $docId   = (int)$doc['id'];
                                    $docName = htmlspecialchars(addslashes($doc['file_name']));
                                    $docExt  = strtolower(pathinfo($doc['file_name'], PATHINFO_EXTENSION));
                                    $encodedName = urlencode($doc['file_name']);
                                    $encodedPath = urlencode($doc['file_path'] ?? $doc['file_name']);
                                    $viewUrl = '/lgu-urban-planning/documents/user_download.php?file=' . $encodedPath . '&amp;name=' . $encodedName . '&amp;view=1';
                                    $dlUrl   = '/lgu-urban-planning/documents/user_download.php?file=' . $encodedPath . '&name=' . $encodedName;
                                ?>
                                <button type="button"
                                    class="btn btn-sm btn-outline-primary me-1"
                                    onclick="openDocModal('<?php echo '/lgu-urban-planning/documents/user_download.php?file=' . $encodedPath . '&view=1&name=' . $encodedName; ?>','<?php echo $docName; ?>','<?php echo $docExt; ?>')">
                                    <i class="bi bi-eye"></i> <?php echo _vwt('btn_view'); ?>
                                </button>
                                <a href="<?php echo $dlUrl; ?>"
                                   class="btn btn-sm btn-primary"
                                   download="<?php echo htmlspecialchars($doc['file_name']); ?>">
                                    <i class="bi bi-download"></i> <?php echo _vwt('btn_download'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div><!-- /.table-responsive-wrapper -->
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><?php echo _vwt('card_history'); ?></h5>
            <small class="text-muted" id="historyPageInfo"></small>
        </div>
        <div class="card-body">
            <div class="timeline" id="historyTimeline">
                <?php foreach ($application['status_history'] as $history): ?>
                <div class="mb-3 border-start border-3 ps-3 history-item">
                    <strong><?php echo ucfirst(str_replace('_', ' ', $history['status'])); ?></strong>
                    <p class="mb-1"><?php echo htmlspecialchars($history['remarks'] ?? _vwt('no_remarks')); ?></p>
                    <small class="text-muted">
                        <?php echo Helper::formatDateTime($history['created_at']); ?>
                        <?php if (!empty($history['first_name'])): ?>
                            <?php echo _vwt('lbl_by'); ?> <?php echo htmlspecialchars($history['first_name'] . ' ' . $history['last_name']); ?>
                        <?php endif; ?>
                    </small>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination Controls -->
            <div class="d-flex justify-content-center align-items-center gap-2 mt-3" id="historyPagination" style="display:none!important;">
                <button class="btn btn-sm btn-outline-secondary" id="historyPrevBtn" onclick="changeHistoryPage(-1)" disabled>
                    <i class="bi bi-chevron-left"></i> Prev
                </button>
                <span class="text-muted small px-1" id="historyPaginationLabel"></span>
                <button class="btn btn-sm btn-outline-secondary" id="historyNextBtn" onclick="changeHistoryPage(1)">
                    Next <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
    
    <a href="/lgu-urban-planning/applicant/applications.php" class="btn btn-secondary"><?php echo _vwt('btn_back'); ?></a>
</div><!-- /.app-details-wrapper -->

<!-- ── Document Viewer Modal ─────────────────────────────────────────────── -->
<div class="modal fade" id="docViewerModal" tabindex="-1" aria-labelledby="docViewerLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="height: 90vh;">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-semibold" id="docViewerLabel">
                    <i class="bi bi-file-earmark me-2"></i><span id="docViewerTitle">Document</span>
                </h6>
                <div class="ms-auto d-flex gap-2 align-items-center">
                    <a id="docViewerDownload" href="#" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-download"></i> Download
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-0 d-flex flex-column align-items-center justify-content-center bg-secondary bg-opacity-10" style="overflow:hidden; flex:1;">
                <!-- PDF -->
                <iframe id="docViewerFrame"
                        src=""
                        style="width:100%; height:100%; border:none; display:none;"
                        title="Document Viewer">
                </iframe>
                <!-- Image -->
                <img id="docViewerImg"
                     src=""
                     alt="Document"
                     style="max-width:100%; max-height:100%; object-fit:contain; display:none; padding:1rem;" />
                <!-- Unsupported -->
                <div id="docViewerUnsupported" style="display:none;" class="text-center p-4">
                    <i class="bi bi-file-earmark-x fs-1 text-muted"></i>
                    <p class="mt-2 text-muted">This file type cannot be previewed.<br>Please download it to view.</p>
                    <a id="docViewerUnsupportedLink" href="#" class="btn btn-primary mt-2">
                        <i class="bi bi-download me-1"></i> Download File
                    </a>
                </div>
                <!-- Loading spinner -->
                <div id="docViewerSpinner" class="text-center p-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted small">Loading document...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openDocModal(viewUrl, fileName, ext) {
    // Reset
    document.getElementById('docViewerFrame').style.display    = 'none';
    document.getElementById('docViewerImg').style.display      = 'none';
    document.getElementById('docViewerUnsupported').style.display = 'none';
    document.getElementById('docViewerSpinner').style.display  = 'block';
    document.getElementById('docViewerFrame').src              = '';
    document.getElementById('docViewerImg').src                = '';

    document.getElementById('docViewerTitle').textContent = fileName;

    // Download link (no &view=1)
    const downloadUrl = viewUrl.replace('&view=1', '');
    document.getElementById('docViewerDownload').href = downloadUrl;
    document.getElementById('docViewerUnsupportedLink').href = downloadUrl;

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('docViewerModal'));
    modal.show();

    const imageExts = ['png', 'jpg', 'jpeg', 'gif', 'webp'];

    if (ext === 'pdf') {
        const frame = document.getElementById('docViewerFrame');
        frame.onload = () => {
            document.getElementById('docViewerSpinner').style.display = 'none';
            frame.style.display = 'block';
        };
        frame.src = viewUrl;

    } else if (imageExts.includes(ext)) {
        const img = document.getElementById('docViewerImg');
        img.onload = () => {
            document.getElementById('docViewerSpinner').style.display = 'none';
            img.style.display = 'block';
        };
        img.src = viewUrl;

    } else {
        // Unsupported type — show download prompt
        document.getElementById('docViewerSpinner').style.display    = 'none';
        document.getElementById('docViewerUnsupported').style.display = 'block';
    }
}

// ── Status History Pagination ────────────────────────────────────────────────
(function () {
    const ITEMS_PER_PAGE = 5;
    let currentPage = 1;

    const items      = Array.from(document.querySelectorAll('.history-item'));
    const pagination = document.getElementById('historyPagination');
    const prevBtn    = document.getElementById('historyPrevBtn');
    const nextBtn    = document.getElementById('historyNextBtn');
    const pageLabel  = document.getElementById('historyPaginationLabel');
    const pageInfo   = document.getElementById('historyPageInfo');

    const totalPages = Math.ceil(items.length / ITEMS_PER_PAGE);

    function renderPage(page) {
        const start = (page - 1) * ITEMS_PER_PAGE;
        const end   = start + ITEMS_PER_PAGE;

        items.forEach(function (item, idx) {
            item.style.display = (idx >= start && idx < end) ? '' : 'none';
        });

        prevBtn.disabled = (page <= 1);
        nextBtn.disabled = (page >= totalPages);

        const label = 'Page ' + page + ' of ' + totalPages;
        pageLabel.textContent = label;
        pageInfo.textContent  = items.length + ' entr' + (items.length === 1 ? 'y' : 'ies');
    }

    if (items.length > ITEMS_PER_PAGE) {
        pagination.style.removeProperty('display'); // show the nav
        renderPage(currentPage);
    } else {
        // Still show info label even when no pagination needed
        pageInfo.textContent = items.length + ' entr' + (items.length === 1 ? 'y' : 'ies');
    }

    window.changeHistoryPage = function (direction) {
        const next = currentPage + direction;
        if (next < 1 || next > totalPages) return;
        currentPage = next;
        renderPage(currentPage);

        // Scroll to top of the history card smoothly
        document.getElementById('historyTimeline')
                .closest('.card')
                .scrollIntoView({ behavior: 'smooth', block: 'start' });
    };
})();

// Clear iframe/img src when modal closes to stop loading
document.getElementById('docViewerModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('docViewerFrame').src = '';
    document.getElementById('docViewerImg').src   = '';
});
</script>

<?php include __DIR__ . '/../user/footer.php'; ?>
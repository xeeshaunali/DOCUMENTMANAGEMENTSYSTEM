<?php
session_start();
include 'dbconn.php';  // <-- THIS LINE CREATES $con !!

// Check login
if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit();
}

// Fetch logged-in user
$uid = $_SESSION['uid'];
$qry = "SELECT `role`, `username`, `courtname` FROM `users` WHERE `id` = ?";
$stmt = $con->prepare($qry);
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || !in_array($user['role'], ['admin', 'user', 'guest'])) {
    header('Location: unauthorized.php');
    exit();
}

$hasFullCourtAccess = true;
include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Search Results | Court DMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    body { background: linear-gradient(135deg, #f0f7f4 0%, #e6f3ed 100%); font-family: 'Segoe UI', sans-serif; }
    .container-fluid { padding: 2rem; }
    h2 { font-weight: 800; color: #198754; letter-spacing: 1px; }
    .result-header { background: white; padding: 1.5rem; border-radius: 1rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
    .table { font-size: 0.9rem; }
    .doc-count-link { color: #198754; font-weight: 600; text-decoration: underline; cursor: pointer; }
    .doc-count-link:hover { color: #146c43; }
    .doc-list-item {
        background: #f8fff9; border: 1px solid #d4edda; border-radius: 0.5rem;
        padding: 0.8rem; margin-bottom: 0.6rem;
    }
    @media print {
        .no-print, .dt-buttons, .dataTables_filter, .dataTables_length, .dataTables_info { display: none !important; }
        body { background: white; }
    }
</style>
</head>
<body>
<div class="container-fluid">
    <div class="result-header text-center mb-4">
        <h2>Search Results</h2>
        <p class="text-muted">Found records based on your filters</p>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <?php
            $display_court = 'ALL COURTS';
            if (isset($_GET['courtname'])) {
                $court_val = trim($_GET['courtname']);
                if ($court_val !== '' && $court_val !== 'all') {
                    $display_court = htmlspecialchars($court_val);
                }
            }
            ?>
            <h5>Court: <strong class="text-success"><?= $display_court ?></strong></h5>
        </div>
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-success btn-sm">Print</button>
            <button id="excelExport" class="btn btn-primary btn-sm">Export Excel</button>
        </div>
    </div>

<?php
// === Collect and sanitize filters ===
$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$conditions = [];
$params = [];
$types = '';

$filters = [
    'caseno' => ['type' => 'i', 'field' => 'c.caseno'],
    'year' => ['type' => 's', 'field' => 'c.year'],
    'cfms_dc_casecode' => ['type' => 's', 'field' => 'c.cfms_dc_casecode'],
    'partyone' => ['type' => 's', 'field' => 'c.partyone'],
    'partytwo' => ['type' => 's', 'field' => 'c.partytwo'],
    'courtname' => ['type' => 's', 'field' => 'c.courtname'],
    'status' => ['type' => 's', 'field' => 'c.status'],
    'qc_status' => ['type' => 's', 'field' => 'c.qc_status'],
    'confidentiality' => ['type' => 's', 'field' => 'c.confidentiality'],
    'ocr_complete' => ['type' => 's', 'field' => 'c.ocr_complete'],
    'from_date' => ['type' => 's', 'field' => 'DATE(c.last_updated) >= ?'],
    'to_date' => ['type' => 's', 'field' => 'DATE(c.last_updated) <= ?']
];

if ($id > 0) {
    $conditions[] = "c.id = ?";
    $params[] = $id;
    $types .= 'i';
} else {
    foreach ($filters as $key => $config) {
        if (isset($_GET[$key]) && trim($_GET[$key]) !== '') {
            $value = trim($_GET[$key]);
            if ($key === 'caseno' && !is_numeric($value)) continue;

            if (in_array($key, ['partyone', 'partytwo', 'cfms_dc_casecode'])) {
                $conditions[] = $config['field'] . " LIKE ?";
                $params[] = "%$value%";
                $types .= 's';
            } elseif ($key === 'courtname' && $value !== 'all') {
                $conditions[] = "c.courtname = ?";
                $params[] = $value;
                $types .= 's';
            } elseif ($key === 'status' && $value !== 'all') {
                $conditions[] = "c.status = ?";
                $params[] = $value;
                $types .= 's';
            } elseif (in_array($key, ['qc_status', 'confidentiality', 'ocr_complete']) && $value !== 'all') {
                $conditions[] = $config['field'] . " = ?";
                $params[] = $value;
                $types .= 's';
            } elseif ($key === 'from_date') {
                $conditions[] = "DATE(c.last_updated) >= ?";
                $params[] = $value;
                $types .= 's';
            } elseif ($key === 'to_date') {
                $conditions[] = "DATE(c.last_updated) <= ?";
                $params[] = $value;
                $types .= 's';
            } elseif ($key === 'year') {
                $conditions[] = "c.year = ?";
                $params[] = $value;
                $types .= 's';
            } elseif ($key === 'caseno') {
                $conditions[] = "c.caseno = ?";
                $params[] = (int)$value;
                $types .= 'i';
            }
        }
    }
}

$where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

$sql = "
SELECT
    c.id AS case_id, c.courtname, c.casecateg, c.caseno, c.year, c.cfms_dc_casecode,
    c.partyone, c.partytwo, c.status, c.qc_status, c.confidentiality, c.ocr_complete, c.last_updated,
    cd.id AS doc_id, cd.file_name, cd.file_path, cd.uploaded_by, cd.uploaded_at,
    dt.type_name AS document_type
FROM ctccc c
LEFT JOIN case_documents cd ON c.id = cd.case_id
LEFT JOIN document_types dt ON cd.type_id = dt.id
$where
ORDER BY c.id DESC, cd.uploaded_at DESC
";

$stmt = $con->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Group results by case
$cases = [];
while ($row = $result->fetch_assoc()) {
    $cid = $row['case_id'];
    if (!isset($cases[$cid])) {
        $cases[$cid] = [
            'info' => [
                'id' => $row['case_id'],
                'courtname' => $row['courtname'],
                'casecateg' => $row['casecateg'],
                'caseno' => $row['caseno'],
                'year' => $row['year'],
                'cfms_dc_casecode' => $row['cfms_dc_casecode'],
                'partyone' => $row['partyone'],
                'partytwo' => $row['partytwo'],
                'status' => $row['status'],
                'qc_status' => $row['qc_status'],
                'confidentiality' => $row['confidentiality'],
                'ocr_complete' => $row['ocr_complete'],
                'last_updated' => $row['last_updated'] ?? '—'
            ],
            'docs' => []
        ];
    }
    if (!empty($row['file_name'])) {
        $cases[$cid]['docs'][] = [
            'id' => $row['doc_id'],
            'type' => $row['document_type'] ?? 'Document',
            'name' => $row['file_name'],
            'path' => $row['file_path'],
            'by' => $row['uploaded_by'] ?? 'Unknown',
            'at' => $row['uploaded_at'] ? date('d-m-Y H:i', strtotime($row['uploaded_at'])) : '—'
        ];
    }
}
?>

<?php if (!empty($cases)): ?>
<div class="table-responsive">
<table id="caseTable" class="table table-bordered table-hover">
<thead class="table-success">
<tr>
    <th>ID</th>
    <th>Court</th>
    <th>Category</th>
    <th>Case No</th>
    <th>Year</th>
    <th>CFMS Code</th>
    <th>Party One</th>
    <th>Party Two</th>
    <th>Status</th>
    <th>QC</th>
    <th>Conf.</th>
    <th>OCR</th>
    <th>Last Updated</th>
    <th>Documents</th>
</tr>
</thead>
<tbody>
<?php foreach ($cases as $case): $c = $case['info']; $docCount = count($case['docs']); ?>
<tr>
    <td><strong><?= $c['id'] ?></strong></td>
    <td><?= htmlspecialchars($c['courtname']) ?></td>
    <td><?= htmlspecialchars($c['casecateg']) ?></td>
    <td><?= htmlspecialchars($c['caseno']) ?></td>
    <td><?= htmlspecialchars($c['year']) ?></td>
    <td><?= htmlspecialchars($c['cfms_dc_casecode'] ?: '—') ?></td>
    <td><?= htmlspecialchars($c['partyone']) ?></td>
    <td><?= htmlspecialchars($c['partytwo']) ?></td>
    <td><span class="badge bg-info"><?= htmlspecialchars($c['status']) ?></span></td>
    <td><span class="badge <?= $c['qc_status'] === 'Approved' ? 'bg-success' : 'bg-warning' ?>"><?= $c['qc_status'] ?></span></td>
    <td><span class="badge <?= $c['confidentiality'] === 'Restricted' ? 'bg-danger' : 'bg-success' ?>"><?= $c['confidentiality'] ?></span></td>
    <td><span class="badge <?= $c['ocr_complete'] === 'Yes' ? 'bg-success' : 'bg-secondary' ?>"><?= $c['ocr_complete'] ?></span></td>
    <td><?= htmlspecialchars($c['last_updated']) ?></td>
    <td>
        <?php if ($docCount > 0): ?>
            <a href="#" class="doc-count-link" data-case-id="<?= $c['id'] ?>">
                <?= $docCount ?> Document<?= $docCount > 1 ? 's' : '' ?>
            </a>
        <?php else: ?>
            <span class="text-muted">No documents</span>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php else: ?>
<div class="alert alert-info text-center fs-4">No records found matching your search criteria.</div>
<?php endif; ?>

<div class="text-center mt-4 text-muted small">
    Search performed on: <?= date('d-m-Y H:i:s') ?> | User: <?= htmlspecialchars($user['username']) ?>
</div>
</div>

<!-- Documents List Modal -->
<div class="modal fade" id="documentsModal" tabindex="-1" aria-labelledby="documentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="documentsModalLabel">Case Documents</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="documentsList"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-body text-center" id="previewContent"></div>
        </div>
    </div>
</div>

<!-- PIN Modal -->
<div class="modal fade" id="pinModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enter PIN</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="password" id="pinInput" class="form-control text-center" maxlength="6" placeholder="6-digit PIN" inputmode="numeric">
                <div id="pinError" class="text-danger mt-2" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmPin">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

<script>
$(document).ready(function() {
    $('#caseTable').DataTable({
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100, 500],
        dom: 'Blfrtip',
        buttons: [{ extend: 'excel', text: 'Export Excel', className: 'btn btn-primary btn-sm' }],
        order: [[0, 'desc']]
    });
    $('#excelExport').on('click', function() {
        $('.buttons-excel').trigger('click');
    });

    const casesData = <?php echo json_encode(array_values($cases)); ?>;
    let pendingAction = null;

    $(document).on('click', '.doc-count-link', function(e) {
        e.preventDefault();
        const caseId = $(this).data('case-id');
        const caseData = casesData.find(c => c.info.id == caseId);

        let html = `<h6 class="mb-3"><strong>Case ID:</strong> ${caseData.info.id} | ${caseData.info.partyone} vs ${caseData.info.partytwo}</h6>`;
        if (caseData.docs.length === 0) {
            html += '<p class="text-muted">No documents uploaded.</p>';
        } else {
            caseData.docs.forEach(doc => {
                html += `
                <div class="doc-list-item">
                    <strong>${doc.type}:</strong> ${doc.name}<br>
                    <small class="text-muted">Uploaded by: <strong>${doc.by}</strong> on ${doc.at}</small><br><br>
                    <button class="btn btn-outline-success btn-sm pin-action me-2" data-action="view" data-doc-id="${doc.id}">View</button>
                    <button class="btn btn-outline-primary btn-sm pin-action" data-action="download" data-doc-id="${doc.id}">Download</button>
                </div>`;
            });
        }
        $('#documentsList').html(html);

        const docsModal = new bootstrap.Modal(document.getElementById('documentsModal'));
        docsModal.show();
    });

    $(document).on('click', '.pin-action', function() {
        pendingAction = { action: $(this).data('action'), docId: $(this).data('doc-id') };

        bootstrap.Modal.getInstance(document.getElementById('documentsModal'))?.hide();
        bootstrap.Modal.getInstance(document.getElementById('previewModal'))?.hide();

        $('#pinError').hide();
        $('#pinInput').val('');

        const pinModalEl = document.getElementById('pinModal');
        const pinModal = new bootstrap.Modal(pinModalEl);
        pinModal.show();

        pinModalEl.addEventListener('shown.bs.modal', function () {
            $('#pinInput').trigger('focus');
        }, { once: true });
    });

    $('#confirmPin').on('click', function() {
        const pin = $('#pinInput').val().trim();
        const errorEl = $('#pinError');

        if (pin.length !== 6 || !/^\d+$/.test(pin)) {
            errorEl.text('Enter a valid 6-digit PIN').show();
            return;
        }

        errorEl.hide();

        fetch('verify_pin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `pin=${encodeURIComponent(pin)}&action=${pendingAction.action}&doc_id=${pendingAction.docId}`
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('pinModal')).hide();

                if (res.action === 'view') {
                    const ext = res.path.split('.').pop().toLowerCase();
                    let content = ext === 'pdf'
                        ? `<iframe src="${res.path}" width="100%" height="800px" style="border:none;"></iframe>`
                        : `<img src="${res.path}" class="img-fluid" alt="Document">`;
                    $('#previewContent').html(content);
                    new bootstrap.Modal(document.getElementById('previewModal')).show();
                } else {
                    const a = document.createElement('a');
                    a.href = res.path; a.download = ''; document.body.appendChild(a); a.click(); a.remove();
                }
            } else {
                errorEl.text(res.message || 'Invalid PIN').show();
            }
        })
        .catch(() => errorEl.text('Network error').show());
    });

    $('#previewModal').on('hidden.bs.modal', function () {
        $('#previewContent').html('');
    });
});
</script>

<?php include "footer.php"; ?>
</body>
</html>
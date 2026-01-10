<?php
session_start();
include 'dbconn.php';

// === Authentication ===
if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit();
}

// Fetch user info
$uid = $_SESSION['uid'];
$q = $con->prepare("SELECT `role`, `username`, `courtname` FROM `users` WHERE `id` = ?");
$q->bind_param("i", $uid);
$q->execute();
$user = $q->get_result()->fetch_assoc();
$q->close();

if (!$user || $user['role'] !== 'user') {
    header('Location: unauthorized.php');
    exit();
}

$courtname = $user['courtname'] ?? '';

// === Determine if user has FULL access (ALL courts) ===
$is_full_access = (strtoupper(trim($courtname)) === 'ALL');

// Ensure document_types table exists
$con->query("CREATE TABLE IF NOT EXISTS `document_types` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `type_name` VARCHAR(150) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1");

// === Helper: Safe Count ===
function safe_count($con, $query, $types = null, $params = []) {
    $stmt = $con->prepare($query);
    if (!$stmt) return 0;
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return (int)($row['total'] ?? 0);
}

// === Counts based on access level ===
if ($is_full_access) {
    // User with "ALL" courts → show ALL data
    $total_records = safe_count($con, "SELECT COUNT(*) AS total FROM ctccc");
    $docs_count = safe_count($con, "SELECT COUNT(*) AS total FROM case_documents");
} else {
    // Regular user → show only their court
    $total_records = safe_count($con, "SELECT COUNT(*) AS total FROM ctccc WHERE courtname = ?", "s", [$courtname]);
    $docs_count = safe_count($con, "SELECT COUNT(*) AS total FROM case_documents WHERE courtname = ?", "s", [$courtname]);
}

// === Status Breakdown ===
$status_data = [];
$status_res = $con->query("SELECT status_name FROM case_status ORDER BY status_name ASC");
if ($status_res) {
    while ($s = $status_res->fetch_assoc()) {
        $status_name = trim($s['status_name']);
        if ($is_full_access) {
            $count = safe_count($con, "SELECT COUNT(*) AS total FROM ctccc WHERE status = ?", "s", [$status_name]);
        } else {
            $count = safe_count($con, "SELECT COUNT(*) AS total FROM ctccc WHERE status = ? AND courtname = ?", "ss", [$status_name, $courtname]);
        }
        if ($count > 0) {
            $status_data[] = ['status' => $status_name, 'count' => $count];
        }
    }
}

// === Recent Documents ===
$recent_docs = [];
if ($is_full_access) {
    $stmt = $con->prepare("
        SELECT cd.*, c.caseno, c.casecateg, c.courtname 
        FROM case_documents cd 
        LEFT JOIN ctccc c ON cd.case_id = c.id 
        ORDER BY cd.uploaded_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
} else {
    $stmt = $con->prepare("
        SELECT cd.*, c.caseno, c.casecateg 
        FROM case_documents cd 
        LEFT JOIN ctccc c ON cd.case_id = c.id 
        WHERE cd.courtname = ? 
        ORDER BY cd.uploaded_at DESC 
        LIMIT 5
    ");
    $stmt->bind_param("s", $courtname);
    $stmt->execute();
}
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_docs[] = $row;
}
$stmt->close();

// === Document Types ===
$document_types = [];
$dt = $con->query("SELECT id, type_name FROM document_types ORDER BY type_name ASC");
while ($r = $dt->fetch_assoc()) $document_types[] = $r;

include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>User Dashboard | Court DMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    body { background: linear-gradient(135deg, #f0f7f4 0%, #e6f3ed 100%); font-family: 'Segoe UI', sans-serif; min-height: 100vh; }
    .navbar-custom { background: linear-gradient(135deg, #198754, #20c997); color: white; }
    .dashboard-card {
        border-radius: 1.2rem; transition: 0.3s; box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        background: white; padding: 1.8rem; text-align: center; height: 160px; display: flex; flex-direction: column; justify-content: center;
    }
    .dashboard-card:hover { transform: translateY(-8px); box-shadow: 0 12px 30px rgba(25,135,84,0.2); }
    .dashboard-card h4 { color: #198754; font-weight: 700; font-size: 1rem; margin: 0; }
    .dashboard-card p { font-size: 2.2rem; color: #0d6efd; font-weight: 800; margin: 0.5rem 0 0; }
    .card { border-radius: 1.2rem; overflow: hidden; }
    .card-header { background: linear-gradient(135deg, #198754, #20c997); color: white; }
    .btn-action { border-radius: 50px; padding: 0.7rem 2rem; font-weight: 600; }
    .doc-item { background: #f8fff9; border: 1px solid #d4edda; border-radius: 0.8rem; padding: 1rem; margin-bottom: 1rem; }
    .doc-item:hover { transform: translateY(-3px); box-shadow: 0 6px 15px rgba(25,135,84,0.15); }
    .court-badge { background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.9rem; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom shadow-sm mb-4">
    <div class="container-fluid">
        <span class="navbar-text fs-5 fw-bold">
            User Dashboard — 
            <?php if ($is_full_access): ?>
                <span class="court-badge">ALL COURTS ACCESS</span>
            <?php else: ?>
                <?= htmlspecialchars($courtname ?: 'Not Assigned') ?>
            <?php endif; ?>
        </span>
        <div class="ms-auto text-white fw-bold">Welcome, <?= htmlspecialchars($user['username']) ?></div>
    </div>
</nav>

<div class="container">
    <!-- Action Buttons -->
    <div class="row g-3 mb-4">
        <div class="col-md-3"><a href="addrecord.php" class="btn btn-success btn-lg w-100 btn-action">Add Record</a></div>
        <div class="col-md-3"><a href="editrecord.php" class="btn btn-warning btn-lg w-100 btn-action">Edit Records</a></div>
        <div class="col-md-3"><a href="search.php" class="btn btn-primary btn-lg w-100 btn-action">Search Records</a></div>
        <div class="col-md-3"><a href="upload_existing_document.php" class="btn btn-info btn-lg w-100 btn-action">Upload Document</a></div>
    </div>

    <!-- Dashboard Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <a href="view_cases.php?status=all" class="text-decoration-none">
                <div class="dashboard-card">
                    <h4>Total Records</h4>
                    <p><?= number_format($total_records) ?></p>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="view_cases.php" class="text-decoration-none">
                <div class="dashboard-card">
                    <h4>Uploaded Documents</h4>
                    <p><?= number_format($docs_count) ?></p>
                </div>
            </a>
        </div>
        <?php foreach ($status_data as $s): ?>
        <div class="col-md-3">
            <a href="view_cases.php?status=<?= urlencode($s['status']) ?>" class="text-decoration-none">
                <div class="dashboard-card">
                    <h4><?= htmlspecialchars($s['status']) ?></h4>
                    <p><?= number_format($s['count']) ?></p>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="row">
        <!-- Quick Upload -->
        <div class="col-lg-12 mb-4">
            <div class="card shadow-lg">
                <div class="card-header text-center">
                    <h5 class="mb-0">Quick Upload to Existing Case</h5>
                </div>
                <div class="card-body">
                    <form id="uploadForm" action="upload_document.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Case ID</label>
                            <input type="number" name="case_id" class="form-control form-control-lg" required placeholder="Enter Case ID">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Document Type</label>
                            <select name="document_type_id" class="form-select form-select-lg" required>
                                <option value="">-- Select Type --</option>
                                <?php foreach ($document_types as $dt): ?>
                                    <option value="<?= $dt['id'] ?>"><?= htmlspecialchars($dt['type_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <input type="hidden" name="type_id" id="type_id">
                        <div class="mb-3">
                            <label class="form-label">Select File</label>
                            <input type="file" name="document" class="form-control form-control-lg" required>
                        </div>
                        <div class="progress mb-3" style="height: 30px; display:none;">
                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width:0%">0%</div>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg w-100">Upload Document</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recent Documents -->
        <!-- <div class="col-lg-6 mb-4">
            <div class="card shadow-lg">
                <div class="card-header text-center">
                    <h5 class="mb-0">
                        Recently Uploaded Documents 
                        <?= $is_full_access ? '(All Courts)' : '(Your Court)' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_docs)): ?>
                        <p class="text-center text-muted">No documents uploaded yet.</p>
                    <?php else: ?>
                        <?php foreach ($recent_docs as $doc): ?>
                        <div class="doc-item">
                            <strong>
                                Case <?= $doc['case_id'] ?> 
                                <?php if ($is_full_access && !empty($doc['courtname'])): ?>
                                    <small class="text-primary">[<?= htmlspecialchars($doc['courtname']) ?>]</small>
                                <?php endif; ?>
                            </strong><br>
                            <small>
                                <?= htmlspecialchars($doc['caseno'] ?? '—') ?> / <?= htmlspecialchars($doc['casecateg'] ?? '—') ?>
                            </small><br>
                            <small>File: <?= htmlspecialchars($doc['file_name']) ?> by <?= htmlspecialchars($doc['uploaded_by']) ?></small><br>
                            <small class="text-muted"><?= date('d-m-Y H:i', strtotime($doc['uploaded_at'])) ?></small>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-success pin-action" data-action="view" data-doc-id="<?= $doc['id'] ?>">View</button>
                                <button class="btn btn-sm btn-outline-primary pin-action" data-action="download" data-doc-id="<?= $doc['id'] ?>">Download</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div> -->

    <!-- Chart -->
    <!-- <div class="card shadow-lg mb-5">
        <div class="card-header text-center">
            <h5 class="mb-0">
                Case Status Overview 
                <?= $is_full_access ? '(All Courts)' : '(Your Court)' ?>
            </h5>
        </div>
        <div class="card-body">
            <canvas id="statusChart" height="120"></canvas>
        </div>
    </div> -->
</div>

<!-- Modals -->
<div class="modal fade" id="previewModal" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content"><div class="modal-body text-center" id="previewContent"></div></div></div></div>
<div class="modal fade" id="pinModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Enter PIN</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="password" id="pinInput" class="form-control text-center" maxlength="6" placeholder="6-digit PIN">
                <div id="pinError" class="text-danger mt-2" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmPin">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const labels = <?= json_encode(array_column($status_data, 'status')) ?>;
const dataCounts = <?= json_encode(array_column($status_data, 'count')) ?>;

new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: labels,
        datasets: [{
            data: dataCounts,
            backgroundColor: ['#198754', '#ffc107', '#dc3545', '#0d6efd', '#6c757d', '#17a2b8'],
            borderWidth: 3,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom', labels: { padding: 20, font: { size: 14 } } } }
    }
});

// Upload Progress + PIN Logic (same as before)
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    document.getElementById('type_id').value = document.querySelector('[name="document_type_id"]').value;
    const formData = new FormData(this);
    const progress = document.querySelector('.progress');
    const bar = document.getElementById('progressBar');
    progress.style.display = 'block';

    const xhr = new XMLHttpRequest();
    xhr.upload.onprogress = e => {
        if (e.lengthComputable) {
            const percent = Math.round((e.loaded / e.total) * 100);
            bar.style.width = percent + '%';
            bar.textContent = percent + '%';
        }
    };
    xhr.onload = () => location.reload();
    xhr.open('POST', 'upload_document.php');
    xhr.send(formData);
});

// PIN Modal Logic
let pendingAction = null;
document.querySelectorAll('.pin-action').forEach(btn => {
    btn.onclick = () => {
        pendingAction = { action: btn.dataset.action, id: btn.dataset.docId };
        document.getElementById('pinError').style.display = 'none';
        document.getElementById('pinInput').value = '';
        new bootstrap.Modal(document.getElementById('pinModal')).show();
    };
});

document.getElementById('confirmPin').onclick = () => {
    const pin = document.getElementById('pinInput').value.trim();
    const error = document.getElementById('pinError');
    if (pin.length !== 6 || !/^\d+$/.test(pin)) {
        error.textContent = 'Enter 6 digits'; error.style.display = 'block'; return;
    }
    fetch('verify_pin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `pin=${pin}&action=${pendingAction.action}&doc_id=${pendingAction.id}`
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            if (res.action === 'view') {
                const ext = res.path.split('.').pop().toLowerCase();
                const html = ext === 'pdf' 
                    ? `<iframe src="${res.path}" width="100%" height="700px" style="border:none;"></iframe>`
                    : `<img src="${res.path}" class="img-fluid">`;
                document.getElementById('previewContent').innerHTML = html;
                new bootstrap.Modal(document.getElementById('previewModal')).show();
            } else {
                const a = document.createElement('a');
                a.href = res.path; a.download = ''; document.body.appendChild(a); a.click(); a.remove();
            }
            bootstrap.Modal.getInstance(document.getElementById('pinModal')).hide();
        } else {
            error.textContent = res.message || 'Invalid PIN'; error.style.display = 'block';
        }
    });
};
</script>

<?php include 'footer.php'; ?>
</body>
</html>
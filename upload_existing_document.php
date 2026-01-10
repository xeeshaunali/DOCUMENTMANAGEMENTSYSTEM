<?php
session_start();
include 'dbconn.php';
include 'header.php';

if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit();
}

$uid = (int)$_SESSION['uid'];
$stmt = $con->prepare("SELECT username, role, courtname FROM users WHERE id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) die("User not found.");

$username       = $user['username'];
$userRole       = $user['role'];
$userCourtCode  = trim($user['courtname'] ?? '');

$isAdmin         = ($userRole === 'admin');
$isAllCourtsUser = (strtoupper($userCourtCode) === 'ALL');
$hasFullAccess   = $isAdmin || $isAllCourtsUser;
$canDeleteDocs   = $isAdmin;

// ========================
// DELETE (WORKS!)
// ========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'batch_delete') {
    if (!$canDeleteDocs) die("Access Denied.");

    $doc_ids = $_POST['doc_ids'] ?? [];
    $case_id = intval($_POST['case_id'] ?? 0);
    $deleted = 0;

    foreach ($doc_ids as $id) {
        $id = intval($id);
        if ($id <= 0) continue;

        $stmt = $con->prepare("SELECT file_path FROM case_documents WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($res && file_exists(__DIR__ . '/' . $res['file_path'])) {
            @unlink(__DIR__ . '/' . $res['file_path']);
        }

        $stmt = $con->prepare("DELETE FROM case_documents WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) $deleted++;
        $stmt->close();
    }

    header("Location: upload_existing_document.php?case_id=$case_id&deleted=$deleted");
    exit();
}

// ========================
// UPLOAD (NOW WORKS AGAIN!)
// ========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $case_id = intval($_POST['case_id'] ?? 0);
    $type_id = intval($_POST['document_type_id'] ?? 0);

    if ($case_id <= 0 || $type_id <= 0) {
        echo "<script>alert('Invalid case or document type.'); history.back();</script>";
        exit();
    }

    $stmt = $con->prepare("SELECT courtname, cfms_dc_casecode FROM ctccc WHERE id = ?");
    $stmt->bind_param("i", $case_id);
    $stmt->execute();
    $case = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$case) {
        echo "<script>alert('Case not found.'); history.back();</script>";
        exit();
    }

    if (!$hasFullAccess && $case['courtname'] !== $userCourtCode) {
        echo "<script>alert('Access Denied: You can only upload to your own court cases.'); history.back();</script>";
        exit();
    }

    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        echo "<script>alert('No file uploaded or upload failed.'); history.back();</script>";
        exit();
    }

    $case_code = preg_replace('/\s+/', '_', $case['cfms_dc_casecode'] ?: "Case{$case_id}");
    $court_folder = preg_replace('/\s+/', '_', $case['courtname']);

    $stmt = $con->prepare("SELECT type_name FROM document_types WHERE id = ?");
    $stmt->bind_param("i", $type_id);
    $stmt->execute();
    $type = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $type_name = preg_replace('/\s+/', '_', $type['type_name'] ?? 'Document');

    $fileExt = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','pdf','doc','docx','xls','xlsx','csv'];
    if (!in_array($fileExt, $allowed)) {
        echo "<script>alert('Invalid file type.'); history.back();</script>";
        exit();
    }

    $stmt = $con->prepare("SELECT COUNT(*) FROM case_documents WHERE case_id = ? AND type_id = ?");
    $stmt->bind_param("ii", $case_id, $type_id);
    $stmt->execute();
    $version = ($stmt->get_result()->fetch_row()[0] ?? 0) + 1;
    $stmt->close();

    $newFileName = "{$case_code}_{$type_name}_v{$version}.{$fileExt}";
    $uploadDir = __DIR__ . "/uploads/{$court_folder}/{$case_code}";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $destPath = "$uploadDir/$newFileName";
    if (!move_uploaded_file($_FILES['document']['tmp_name'], $destPath)) {
        echo "<script>alert('Failed to save file.'); history.back();</script>";
        exit();
    }

    $dbPath = "uploads/{$court_folder}/{$case_code}/{$newFileName}";

    $stmt = $con->prepare("INSERT INTO case_documents 
        (case_id, type_id, file_name, file_path, uploaded_by, courtname, uploaded_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iissss", $case_id, $type_id, $newFileName, $dbPath, $username, $case['courtname']);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Document uploaded successfully!'); window.location='upload_existing_document.php?case_id=$case_id';</script>";
    exit();
}

// ========================
// LOAD DOCUMENTS
// ========================
$case_id = intval($_GET['case_id'] ?? 0);
$documents = [];
$caseBelongsToUser = false;

if ($case_id > 0) {
    $stmt = $con->prepare("SELECT courtname FROM ctccc WHERE id = ?");
    $stmt->bind_param("i", $case_id);
    $stmt->execute();
    $caseCheck = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($caseCheck) {
        $caseBelongsToUser = $hasFullAccess || $caseCheck['courtname'] === $userCourtCode;
        if ($caseBelongsToUser) {
            $stmt = $con->prepare("SELECT cd.*, dt.type_name FROM case_documents cd LEFT JOIN document_types dt ON cd.type_id = dt.id WHERE cd.case_id = ? ORDER BY cd.id DESC");
            $stmt->bind_param("i", $case_id);
            $stmt->execute();
            $documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Document - Court DMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: linear-gradient(135deg, #f0f7f4 0%, #e6f3ed 100%); padding: 2rem 0; }
        .card { border-radius: 1.2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: none; }
        .card-header { background: linear-gradient(135deg, #198754, #20c997); color: white; }
        .btn-upload { background: linear-gradient(135deg, #198754, #20c997); border: none; color: white; }
        .doc-item { background: #f8fff9; border: 1px solid #d4edda; border-radius: 0.8rem; padding: 1rem; margin-bottom: 1rem; }
        .doc-item:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(25,135,84,0.15); }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="card-header text-center">
            <h3 class="mb-0 text-white">Upload Document to Existing Case</h3>
        </div>
        <div class="card-body p-5">

            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success text-center">Deleted <?= $_GET['deleted'] ?> document(s)!</div>
            <?php endif; ?>

            <?php if ($case_id > 0 && !$caseBelongsToUser): ?>
                <div class="alert alert-danger text-center fs-5">Access Denied</div>
            <?php else: ?>

            <!-- SEARCH + UPLOAD (FIXED - form is now complete) -->
            <div class="row g-4 mb-5">
                <div class="col-lg-6">
                    <form method="GET" class="input-group">
                        <input type="number" name="case_id" class="form-control form-control-lg" placeholder="Enter Case ID" value="<?= $case_id ?>" required>
                        <button type="submit" class="btn btn-info btn-lg">View Case</button>
                    </form>
                </div>
                <div class="col-lg-6">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="case_id" value="<?= $case_id ?>">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <select name="document_type_id" class="form-select" required>
                                    <option value="">Select Type</option>
                                    <?php
                                    $res = $con->query("SELECT id, type_name FROM document_types ORDER BY type_name");
                                    while ($r = $res->fetch_assoc()) {
                                        echo "<option value='{$r['id']}'>{$r['type_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="file" name="document" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-upload w-100 btn-lg">Upload</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($case_id > 0 && !empty($documents)): ?>
            <hr class="my-5">
            <h4 class="text-center text-success mb-4">Documents — Case ID: <strong><?= $case_id ?></strong></h4>

            <!-- DELETE FORM (ONE FORM, CONTAINS ALL CHECKBOXES + BUTTON) -->
            <?php if ($canDeleteDocs): ?>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="batch_delete">
                <input type="hidden" name="case_id" value="<?= $case_id ?>">
            <?php endif; ?>

            <div class="row g-4">
                <?php foreach ($documents as $doc): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="doc-item text-center">
                        <?php if ($canDeleteDocs): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input delete-chk" type="checkbox" name="doc_ids[]" value="<?= $doc['id'] ?>">
                                <label class="form-check-label fw-bold text-success">
                                    <?= htmlspecialchars($doc['type_name'] ?? 'Document') ?>
                                </label>
                            </div>
                        <?php else: ?>
                            <h6 class="text-success fw-bold"><?= htmlspecialchars($doc['type_name'] ?? 'Document') ?></h6>
                        <?php endif; ?>

                        <div class="small text-muted"><?= htmlspecialchars($doc['file_name']) ?></div>
                        <div class="small text-secondary">
                            By: <?= htmlspecialchars($doc['uploaded_by']) ?> 
                            | <?= date('d-m-Y H:i', strtotime($doc['uploaded_at'])) ?>
                        </div>

                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-success btn-sm pin-action me-2" data-action="view" data-doc-id="<?= $doc['id'] ?>">View</button>
                            <button type="button" class="btn btn-outline-primary btn-sm pin-action" data-action="download" data-doc-id="<?= $doc['id'] ?>">Download</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($canDeleteDocs): ?>
                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-danger fw-bold" id="batchDeleteBtn">Delete Selected</button>
                </div>
            </form>
            <?php endif; ?>

            <?php elseif ($case_id > 0): ?>
                <div class="alert alert-info text-center">No documents uploaded yet.</div>
            <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODALS & JS -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl"><div class="modal-content"><div class="modal-body text-center" id="previewContent"></div></div></div>
</div>

<div class="modal fade" id="pinModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Enter PIN</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
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
<script>
$(document).ready(function() {
    $('.delete-chk').on('change', function() {
        const count = $('.delete-chk:checked').length;
        $('#batchDeleteBtn').prop('disabled', count === 0)
                             .text(count > 0 ? 'Delete Selected ('+count+')' : 'Delete Selected');
    }).trigger('change');

    let pendingAction = null;
    $(document).on('click', '.pin-action', function() {
        pendingAction = { action: $(this).data('action'), docId: $(this).data('doc-id') };
        $('#pinError').hide(); $('#pinInput').val('');
        new bootstrap.Modal(document.getElementById('pinModal')).show();
    });

    $('#confirmPin').on('click', function() {
        const pin = $('#pinInput').val().trim();
        if (pin.length !== 6 || !/^\d+$/.test(pin)) {
            $('#pinError').text('Enter 6 digits').show(); return;
        }
        fetch('verify_pin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `pin=${pin}&action=${pendingAction.action}&doc_id=${pendingAction.docId}`
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                if (res.action === 'view') {
                    const ext = res.path.split('.').pop().toLowerCase();
                    const html = ext === 'pdf'
                        ? `<iframe src="${res.path}" width="100%" height="700px" style="border:none;"></iframe>`
                        : `<img src="${res.path}" class="img-fluid">`;
                    $('#previewContent').html(html);
                    new bootstrap.Modal(document.getElementById('previewModal')).show();
                } else {
                    const a = document.createElement('a');
                    a.href = res.path; a.download = ''; document.body.appendChild(a); a.click(); a.remove();
                }
                bootstrap.Modal.getInstance(document.getElementById('pinModal')).hide();
            } else {
                $('#pinError').text(res.message || 'Invalid PIN').show();
            }
        });
    });
});
</script>

<?php include "footer.php"; ?>
</body>
</html>
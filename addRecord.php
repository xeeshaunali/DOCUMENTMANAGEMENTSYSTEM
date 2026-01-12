<?php
session_start();
include 'dbconn.php';  

// === CONFIG ===
$DEBUG = false;
$ALLOWED_EXT = ['jpg','jpeg','png','pdf','doc','docx','xls','xlsx','csv'];
$BASE_UPLOAD_DIR = __DIR__ . "/uploads";

// === AUTH CHECK ===
if (!isset($_SESSION['uid'])) {
    header('Location: ../login.php');
    exit();
}

// Fetch logged-in user info
$uid = (int)$_SESSION['uid'];
$qry = "SELECT `role`, `username`, `courtname` FROM `users` WHERE `id` = ?";
$stmt = $con->prepare($qry);
if ($stmt === false) { die("Prepare failed: " . $con->error); }
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "Error fetching user data.";
    exit();
}

// === DETERMINE USER ACCESS LEVEL ===
$isAdmin = ($user['role'] === 'admin');
$isAllCourtsUser = (strtoupper(trim($user['courtname'])) === 'ALL');
$hasFullAccess = $isAdmin || $isAllCourtsUser;
$userCourtCode = $hasFullAccess ? null : trim($user['courtname']); // null = no restriction

// === HANDLE FORM SUBMIT ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_court = trim($_POST['courtname'] ?? '');

    // SECURITY: Block if user tries to submit a court they're not allowed to
    if (!$hasFullAccess && $submitted_court !== $userCourtCode) {
        echo "<script>alert('Access Denied: You can only add records for your assigned court.'); history.back();</script>";
        exit();
    }

    // Proceed with normal insertion (your original logic, just safer)
    $courtname        = $submitted_court;
    $casecateg        = trim($_POST['casecateg'] ?? '');
    $caseno           = trim($_POST['caseno'] ?? '');
    $year             = trim($_POST['year'] ?? '');
    $partyone         = trim($_POST['partyone'] ?? '');
    $partytwo         = trim($_POST['partytwo'] ?? '');
    $status           = trim($_POST['status'] ?? '');
    $remarks          = trim($_POST['remarks'] ?? '');
    $cfms_dc_casecode = trim($_POST['cfms_dc_casecode'] ?? '');
    $qc_status        = $_POST['qc_status'] ?? 'Pending';
    $confidentiality  = $_POST['confidentiality'] ?? 'Non-Restricted';
    $ocr_complete     = $_POST['ocr_complete'] ?? 'No';
    $created_by       = $user['username'];
    $created_court    = $courtname;

    // Validation
    if (empty($courtname) || empty($casecateg) || empty($caseno) || empty($year)) {
        echo "<script>alert('Please fill all required fields (Court, Category, Case No, Year).'); history.back();</script>";
        exit();
    }

    // File upload handling
    $hasFile = isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK;
    $document_type_id = !empty($_POST['document_type_id']) ? intval($_POST['document_type_id']) : 0;

    if ($hasFile && $document_type_id <= 0) {
        echo "<script>alert('Please select a document type when uploading a file.'); history.back();</script>";
        exit();
    }

    // Insert main case record
    $insertQry = "INSERT INTO `ctccc`
        (`courtname`, `casecateg`, `caseno`, `year`, `partyone`, `partytwo`, `status`, `remarks`, `cfms_dc_casecode`,
         `qc_status`, `confidentiality`, `ocr_complete`, `created_by`, `created_court`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $con->prepare($insertQry);
    $stmt->bind_param("ssssssssssssss",
        $courtname, $casecateg, $caseno, $year, $partyone, $partytwo,
        $status, $remarks, $cfms_dc_casecode,
        $qc_status, $confidentiality, $ocr_complete, $created_by, $created_court
    );

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        echo "<script>alert('Database error: " . addslashes($err) . "'); history.back();</script>";
        exit();
    }

    $case_id = $con->insert_id;
    $stmt->close();
    $uploadSuccess = true;

    // === FILE UPLOAD (same as yours, unchanged except safety) ===
    if ($hasFile && $document_type_id > 0) {
        $stmt2 = $con->prepare("SELECT type_name FROM document_types WHERE id = ?");
        $stmt2->bind_param("i", $document_type_id);
        $stmt2->execute();
        $typeRow = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        $type_name = preg_replace('/\s+/', '_', $typeRow['type_name'] ?? 'Document');
        $case_code_raw = $cfms_dc_casecode ?: "Case{$case_id}";
        $case_code = preg_replace('/\s+/', '_', $case_code_raw);
        $court_folder = preg_replace('/\s+/', '_', $courtname);

        $fileTmpPath = $_FILES['document']['tmp_name'];
        $fileName = basename($_FILES['document']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExt, $ALLOWED_EXT)) {
            $uploadSuccess = false;
            echo "<script>alert('Invalid file type.');</script>";
        } else {
            $stmt3 = $con->prepare("SELECT COUNT(*) AS total FROM case_documents WHERE case_id = ? AND type_id = ?");
            $stmt3->bind_param("ii", $case_id, $document_type_id);
            $stmt3->execute();
            $version = ($stmt3->get_result()->fetch_assoc()['total'] ?? 0) + 1;
            $stmt3->close();

            $courtDir = $BASE_UPLOAD_DIR . DIRECTORY_SEPARATOR . $court_folder;
            $caseDir  = $courtDir . DIRECTORY_SEPARATOR . $case_code;
            if (!is_dir($caseDir)) {
                mkdir($caseDir, 0777, true);
            }

            $newFileName = "{$case_code}_{$type_name}_v{$version}.{$fileExt}";
            $destPath = $caseDir . DIRECTORY_SEPARATOR . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $relPath = "uploads/{$court_folder}/{$case_code}/{$newFileName}";
                $stmt4 = $con->prepare("INSERT INTO case_documents
                    (case_id, type_id, file_name, file_path, uploaded_by, courtname, qc_status, confidentiality)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt4->bind_param("iissssss", $case_id, $document_type_id, $newFileName, $relPath, $created_by, $courtname, $qc_status, $confidentiality);
                $stmt4->execute();
                $stmt4->close();
            } else {
                $uploadSuccess = false;
                echo "<script>alert('Failed to save file. Check permissions.');</script>";
            }
        }
    }

    // Success
    echo "<script>
        alert('Record added successfully! Case ID: {$case_id}" . ($uploadSuccess ? " | File uploaded." : "") . "');
        window.location.href = 'addrecord.php';
    </script>";
    exit();
}

// === FETCH DATA FOR DROPDOWNS ===
if ($hasFullAccess) {
    // Admin or ALL user → show all courts
    $courts_result = $con->query("SELECT court_code, court_fullname FROM courts ORDER BY court_fullname ASC");
} else {
    // Regular user → show only their court
    $stmt = $con->prepare("SELECT court_code, court_fullname FROM courts WHERE court_code = ?");
    $stmt->bind_param("s", $userCourtCode);
    $stmt->execute();
    $courts_result = $stmt->get_result();
    $stmt->close();
}

$status_result = $con->query("SELECT status_name FROM case_status ORDER BY status_name ASC");
$category_result = $con->query("SELECT category_name FROM case_categories ORDER BY category_name ASC");
$document_types = $con->query("SELECT id, type_name FROM document_types ORDER BY type_name ASC");

include 'header.php';
?>

<style>
label { color: green; font-weight: bold; word-spacing: 0.5rem; letter-spacing: 0.1rem; }

* {
    font-family: "Inter", "Roboto", "Open Sans", sans-serif !important;

}
.btn-dash {
    background-color: #2FBF71 !important;
    border-radius: 10px !important;
    border: none !important;
    color:white !important;
    font-size: 1rem !important;      
}

.btn-dash:hover {
    /* background-color:  #27A862 !important;
    border-color: #27A862 !important;
    box-shadow:0px 0px 2px 2px #2FBF71;
    /* transition: 0.9s; */
    transition: 0.9s;
    font-weight: 700;
    background-color: rgb(149, 245, 181) !important;
    color: black !important;
    
}

.btn-success:hover {
    transition: 0.9s;
    font-weight: 700;
    background-color: rgb(149, 245, 181) !important;
    color: black !important;
    border: none !important;
}

</style>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <h2 class="text-center text-success mb-4">Add New Case Record</h2>

            <form class="row g-3 mx-auto shadow p-4 rounded bg-light" action="addrecord.php" method="POST" enctype="multipart/form-data" autocomplete="off">

                <!-- COURT SELECTION -->
                <div class="col-md-6" data-aos="fade-down">
                    <label for="courtname" class="form-label">Court Name <span class="text-danger">*</span></label>
                    <select id="courtname" class="form-select text-center shadow rounded" name="courtname" required>
                        <option value="">-- Select Court --</option>
                        <?php while ($court = $courts_result->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($court['court_code']) ?>">
                                <?= htmlspecialchars($court['court_fullname']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <?php if (!$hasFullAccess): ?>
                        <small class="text-muted">You can only add cases for your assigned court.</small>
                    <?php endif; ?>
                </div>

                <!-- Rest of your form (unchanged) -->
                <div class="col-md-6">
                    <label>Case / Appln Category <span class="text-danger">*</span></label>
                    <select class="form-select text-center shadow rounded" name="casecateg" required>
                        <option value="">-- Select Category --</option>
                        <?php while ($cat = $category_result->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($cat['category_name']) ?>">
                                <?= htmlspecialchars($cat['category_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-3"><label>Case Number <span class="text-danger">*</span></label><input type="number" class="form-control text-center shadow rounded" name="caseno" required></div>
                <div class="col-md-3"><label>Case Year <span class="text-danger">*</span></label><input type="number" class="form-control text-center shadow rounded" name="year" required></div>
                <div class="col-md-3"><label>Party One</label><input type="text" class="form-control text-center shadow rounded" name="partyone"></div>
                <div class="col-md-3"><label>Party Two</label><input type="text" class="form-control text-center shadow rounded" name="partytwo"></div>

                <div class="col-md-3">
                    <label>Case Status</label>
                    <select name="status" class="form-select text-center shadow rounded">
                        <option value="">-- Select Status --</option>
                        <?php $status_result->data_seek(0); while ($s = $status_result->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($s['status_name']) ?>"><?= htmlspecialchars($s['status_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-3"><label>QC Status</label>
                    <select name="qc_status" class="form-select text-center shadow rounded">
                        <option value="Pending">Pending</option><option value="Approved">Approved</option>
                    </select>
                </div>

                <div class="col-md-3"><label>Confidentiality</label>
                    <select name="confidentiality" class="form-select text-center shadow rounded">
                        <option value="Non-Restricted">Non-Restricted</option><option value="Restricted">Restricted</option>
                    </select>
                </div>

                <div class="col-md-3"><label>OCR Complete</label>
                    <select name="ocr_complete" class="form-select text-center shadow rounded">
                        <option value="No">No</option><option value="Yes">Yes</option>
                    </select>
                </div>

                <div class="col-md-6"><label>CFMS-DC Case Code</label><input type="text" class="form-control text-center shadow rounded" name="cfms_dc_casecode" placeholder="Mandatory" required></div>
                <div class="col-md-6"><label>Remarks</label><input type="text" class="form-control text-center shadow rounded" name="remarks"></div>

                <div class="col-md-6">
                    <label class="text-success">Document Type (if uploading)</label>
                    <select name="document_type_id" class="form-select text-center shadow rounded">
                        <option value="">-- Select Type --</option>
                        <?php while ($dt = $document_types->fetch_assoc()): ?>
                            <option value="<?= $dt['id'] ?>"><?= htmlspecialchars($dt['type_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="text-success">Upload Document (optional)</label>
                    <input type="file" name="document" class="form-control text-center shadow rounded" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx">
                </div>

                <div class="col-12 text-center mt-2 mb-1">
                    <button type="submit" class="btn btn-dash  w-50">Save Record & Upload File</button>
                </div>
            </form>
        </div>
    </div>

    <?php include "footer.php"; ?>
</div>

<script>
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
</script>
</body>
</html>
<?php
session_start();
include 'dbconn.php';

// === AUTH CHECK ===
if (!isset($_SESSION['uid'])) {
    header('Location: ../login.php');
    exit();
}

// Fetch logged-in user
$uid = (int)$_SESSION['uid'];
$stmt = $con->prepare("SELECT `role`, `username`, `courtname` FROM `users` WHERE `id` = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("Error fetching user data.");
}

// === DETERMINE ACCESS LEVEL ===
$isAdmin = ($user['role'] === 'admin');
$isAllCourtsUser = (strtoupper(trim($user['courtname'] ?? '')) === 'ALL');
$hasFullAccess = $isAdmin || $isAllCourtsUser;

// THIS WAS MISSING → Define the user's court code properly
$userCourtCode = $hasFullAccess ? null : trim($user['courtname']);   // e.g., 'DLH01' or null

// === FETCH DROPDOWNS ===
$courts_result = $con->query("SELECT court_code, court_fullname FROM courts ORDER BY court_fullname ASC");
$status_result = $con->query("SELECT status_name FROM case_status ORDER BY status_name ASC");
$category_result = $con->query("SELECT category_name FROM case_categories ORDER BY category_name ASC");

include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Record || DMS</title>
    <link rel="stylesheet" href="./css/bootstrap.css">
    <link rel="stylesheet" href="aos.css">
    <script src="aos.js"></script>
    <style>
        .edit-card { background: linear-gradient(145deg, #f8f9fa, #ffffff); border: 1px solid #28a745; }
        label { color: green; font-weight: bold; word-spacing: 0.5rem; letter-spacing: 0.1rem; }
        @media (max-width: 767px) {
            .edit-card { padding: 12px !important; }
            .btn-lg { width: 100% !important; font-size: 0.9rem; }
        }
    </style>
</head>
<body>

<div class="container-fluid">

    <!-- Search Box -->
    <div class="row justify-content-center mt-4 mb-4">
        <div class="col-12 col-md-5 col-lg-4 edit-card shadow rounded p-4" data-aos="zoom-in">
            <h3 class="text-success fw-bold mb-4 text-center">Edit Record</h3>
            <form action="editrecord.php" method="GET">
                <label class="form-label">Search by Record ID</label>
                <input type="number" name="id" required class="form-control text-center shadow rounded mb-3" placeholder="Enter Record ID">
                <button type="submit" class="btn btn-success btn-lg w-100 shadow">Search Record</button>
            </form>
            <?php if (!$hasFullAccess): ?>
                <small class="text-muted d-block text-center mt-3">
                    You can only edit records from your court: <strong><?php echo htmlspecialchars($userCourtCode); ?></strong>
                </small>
            <?php endif; ?>
        </div>
    </div>

<?php
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // === FETCH RECORD WITH COURT RESTRICTION ===
    if ($hasFullAccess) {
        $query = "SELECT * FROM ctccc WHERE id = ?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("i", $id);
    } else {
        $query = "SELECT * FROM ctccc WHERE id = ? AND courtname = ?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("is", $id, $userCourtCode);   // Fixed: now using $userCourtCode
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "<div class='text-center mt-4'><div class='alert alert-danger col-md-6 mx-auto'>
                Access Denied or Record Not Found.<br>
                <small>You can only edit records from your assigned court.</small>
              </div></div>";
        $stmt->close();
    } else {
        $row = $result->fetch_assoc();
        $stmt->close();
?>

    <div class="row justify-content-center mt-4 mb-5">
        <div class="col-12 col-md-10 col-lg-8 edit-card shadow rounded p-4" data-aos="zoom-in">
            <h4 class="text-success text-center mb-4">
                Editing Record ID: <?php echo $row['id']; ?>
                <span class="badge bg-primary"><?php echo htmlspecialchars($row['courtname']); ?></span>
            </h4>

            <form action="save.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">

                <div class="row g-3 mb-3">
                    <!-- Court Name Dropdown - Locked for single-court users -->
                    <div class="col-md-4">
                        <label>Court Name</label>
                        <select name="courtname" class="form-select text-center shadow rounded" required>
                            <option value="">-- Select Court --</option>
                            <?php
                            $courts_result->data_seek(0);
                            while ($court = $courts_result->fetch_assoc()) {
                                // Single-court users can only see their own court
                                if (!$hasFullAccess && $court['court_code'] !== $userCourtCode) continue;
                                $selected = ($row['courtname'] === $court['court_code']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($court['court_code']) . "' $selected>"
                                    . htmlspecialchars($court['court_fullname']) . "</option>";
                            }
                            ?>
                        </select>
                        <?php if (!$hasFullAccess): ?>
                            <small class="text-muted">Locked to your court</small>
                        <?php endif; ?>
                    </div>

                    <!-- Rest of your form fields (unchanged) -->
                    <div class="col-md-4">
                        <label>Case Category</label>
                        <select name="casecateg" class="form-select text-center shadow rounded" required>
                            <option value="">-- Select Category --</option>
                            <?php
                            $category_result->data_seek(0);
                            while ($cat = $category_result->fetch_assoc()) {
                                $selected = ($row['casecateg'] === $cat['category_name']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($cat['category_name']) . "' $selected>"
                                    . htmlspecialchars($cat['category_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label>Case Number</label>
                        <input type="number" name="caseno" value="<?php echo htmlspecialchars($row['caseno']); ?>" class="form-control text-center shadow rounded" required>
                    </div>
                </div>

                <!-- [All other form fields same as before - Year, Parties, Status, etc.] -->
                <!-- I'm keeping them exactly like your original for brevity -->
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label>Case Year</label>
                        <input type="number" name="year" value="<?php echo htmlspecialchars($row['year']); ?>" class="form-control text-center shadow rounded" required>
                    </div>
                    <div class="col-md-4">
                        <label>Party One</label>
                        <input type="text" name="partyone" value="<?php echo htmlspecialchars($row['partyone']); ?>" class="form-control text-center shadow rounded">
                    </div>
                    <div class="col-md-4">
                        <label>Party Two</label>
                        <input type="text" name="partytwo" value="<?php echo htmlspecialchars($row['partytwo']); ?>" class="form-control text-center shadow rounded">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label>Case Status</label>
                        <select name="status" class="form-select text-center shadow rounded">
                            <option value="">-- Select Status --</option>
                            <?php
                            $status_result->data_seek(0);
                            while ($s = $status_result->fetch_assoc()) {
                                $selected = ($row['status'] === $s['status_name']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($s['status_name']) . "' $selected>" . htmlspecialchars($s['status_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>QC Status</label>
                        <select name="qc_status" class="form-select text-center shadow rounded">
                            <option value="Pending" <?php echo ($row['qc_status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="Approved" <?php echo ($row['qc_status'] == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Confidentiality</label>
                        <select name="confidentiality" class="form-select text-center shadow rounded">
                            <option value="Non-Restricted" <?php echo ($row['confidentiality'] == 'Non-Restricted') ? 'selected' : ''; ?>>Non-Restricted</option>
                            <option value="Restricted" <?php echo ($row['confidentiality'] == 'Restricted') ? 'selected' : ''; ?>>Restricted</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label>OCR Complete</label>
                        <select name="ocr_complete" class="form-select text-center shadow rounded">
                            <option value="No" <?php echo ($row['ocr_complete'] == 'No') ? 'selected' : ''; ?>>No</option>
                            <option value="Yes" <?php echo ($row['ocr_complete'] == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label>CFMS-DC Case Code</label>
                        <input type="text" name="cfms_dc_casecode" value="<?php echo htmlspecialchars($row['cfms_dc_casecode']); ?>" class="form-control text-center shadow rounded">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label>Remarks</label>
                        <input type="text" name="remarks" value="<?php echo htmlspecialchars($row['remarks']); ?>" class="form-control text-center shadow rounded">
                    </div>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-success btn-lg px-5 shadow">Update Record</button>
                    <a href="editrecord.php" class="btn btn-secondary btn-lg px-4 ms-3">Cancel</a>
                </div>
            </form>
        </div>
    </div>

<?php
    }
}
?>

<div class="text-center mt-5 mb-5">
    <?php include 'footer.php'; ?>
</div>

<script src="js/bootstrap.js"></script>
<script>AOS.init({ duration: 800, once: true });</script>
</body>
</html>
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
if (!$user) {
    echo "Error fetching user data.";
    exit();
}
// Admin only
if ($user['role'] !== 'admin') {
    header('Location: unauthorized.php');
    exit();
}
// ------------------------------------------------------
// Ensure document_types table exists
$create_doc_types_sql = "
CREATE TABLE IF NOT EXISTS `document_types` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `type_name` VARCHAR(150) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
";
$con->query($create_doc_types_sql);
// ------------------------------------------------------
// Handle Document Types actions (add / edit / delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'add_doc_type') {
        $type_name = trim($_POST['type_name'] ?? '');
        if ($type_name === '') {
            echo "Type name cannot be empty.";
            exit();
        }
        $stmt = $con->prepare("INSERT INTO document_types (type_name) VALUES (?)");
        if ($stmt === false) {
            echo "Prepare failed: " . $con->error;
            exit();
        }
        $stmt->bind_param("s", $type_name);
        if ($stmt->execute()) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                echo "OK";
                exit();
            } else {
                header("Location: admindash.php");
                exit();
            }
        } else {
            echo "Database error: " . addslashes($stmt->error);
            exit();
        }
    }
    if ($action === 'edit_doc_type') {
        $id = intval($_POST['id'] ?? 0);
        $type_name = trim($_POST['type_name'] ?? '');
        if ($id <= 0 || $type_name === '') {
            echo "Invalid data.";
            exit();
        }
        $stmt = $con->prepare("UPDATE document_types SET type_name = ? WHERE id = ?");
        if ($stmt === false) {
            echo "Prepare failed: " . $con->error;
            exit();
        }
        $stmt->bind_param("si", $type_name, $id);
        if ($stmt->execute()) {
            echo "OK";
            exit();
        } else {
            echo "Database error: " . addslashes($stmt->error);
            exit();
        }
    }
    if ($action === 'delete_doc_type') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo "Invalid id.";
            exit();
        }
        $stmt = $con->prepare("DELETE FROM document_types WHERE id = ?");
        if ($stmt === false) {
            echo "Prepare failed: " . $con->error;
            exit();
        }
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo "OK";
            exit();
        } else {
            echo "Database error: " . addslashes($stmt->error);
            exit();
        }
    }
}
// === Helper: safe_count ===
function safe_count($con, $query, $types = null, $params = []) {
    if ($types === null) {
        $res = mysqli_query($con, $query);
        if (!$res) return 0;
        $row = mysqli_fetch_assoc($res);
        return $row ? (int)$row['total'] : 0;
    } else {
        $stmt = $con->prepare($query);
        if ($stmt === false) return 0;
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $r ? (int)$r['total'] : 0;
    }
}
// === Counts ===
$total_records = safe_count($con, "SELECT COUNT(*) AS total FROM ctccc");
$docs_count = safe_count($con, "SELECT COUNT(*) AS total FROM case_documents");
$total_doc_types = safe_count($con, "SELECT COUNT(*) AS total FROM document_types");

// === FIXED: Court-wise Case Count (using correct courtname field) ===
$court_case_counts = [];
$court_query = $con->query("
    SELECT
        COALESCE(c.courtname, 'Unknown') AS court_name,
        COUNT(*) AS case_count
    FROM ctccc c
    GROUP BY c.courtname
    ORDER BY case_count DESC
");
if ($court_query) {
    while ($row = $court_query->fetch_assoc()) {
        $court_case_counts[] = $row;
    }
}

// === FIXED: Court-wise Document Statistics (joined with ctccc for accurate courtname) ===
$court_doc_stats = [];
$doc_stats_query = $con->query("
    SELECT
        COALESCE(c.courtname, 'Unknown') AS court_name,
        COUNT(cd.id) AS doc_count
    FROM case_documents cd
    LEFT JOIN ctccc c ON cd.case_id = c.id
    GROUP BY c.courtname
    ORDER BY doc_count DESC
");
if ($doc_stats_query) {
    while ($row = $doc_stats_query->fetch_assoc()) {
        $court_doc_stats[] = $row;
    }
}

// === Document Types with Counts ===
$doc_types_with_count = [];
$dt_query = $con->query("
    SELECT dt.id, dt.type_name, COUNT(cd.id) AS doc_count
    FROM document_types dt
    LEFT JOIN case_documents cd ON dt.id = cd.type_id
    GROUP BY dt.id
    ORDER BY dt.type_name ASC
");
if ($dt_query) {
    while ($row = $dt_query->fetch_assoc()) {
        $doc_types_with_count[] = $row;
    }
}

// === Build status cards ===
$status_data = [];
$status_res = $con->query("SELECT status_name FROM case_status ORDER BY status_name ASC");
if ($status_res) {
    while ($s = $status_res->fetch_assoc()) {
        $status_name = trim($s['status_name']);
        $stmt = $con->prepare("SELECT COUNT(*) AS total FROM ctccc WHERE LOWER(TRIM(status)) = LOWER(?)");
        $stmt->bind_param("s", $status_name);
        $stmt->execute();
        $cnt = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt->close();
        $status_data[] = ['status' => $status_name, 'count' => (int)$cnt];
    }
}

// === Fetch document types for UI ===
$document_types = [];
$dt = $con->query("SELECT id, type_name FROM document_types ORDER BY type_name ASC");
if ($dt) {
    while ($r = $dt->fetch_assoc()) $document_types[] = $r;
}

// === Recent access logs ===
$recent_logs = [];
$rl = $con->query("
    SELECT dal.id, dal.doc_id, dal.user_id, dal.action, dal.accessed_at, dal.ip_address,
           cd.file_name, u.username
    FROM document_access_logs dal
    JOIN case_documents cd ON dal.doc_id = cd.id
    JOIN users u ON dal.user_id = u.id
    ORDER BY dal.accessed_at DESC LIMIT 10
");
if ($rl) {
    while ($row = $rl->fetch_assoc()) {
        $recent_logs[] = $row;
    }
}

// Status color/icon functions
function getStatusColor($status) {
    $status = strtolower($status);
    if (strpos($status, 'pending') !== false) return '#ffc107';
    if (strpos($status, 'disposed') !== false) return '#dc3545';
    if (strpos($status, 'active') !== false) return '#198754';
    return '#0d6efd';
}
function getStatusIcon($status) {
    $status = strtolower($status);
    if (strpos($status, 'pending') !== false) return 'bi-hourglass-split';
    if (strpos($status, 'disposed') !== false) return 'bi-check-circle-fill';
    if (strpos($status, 'active') !== false) return 'bi-play-circle-fill';
    return 'bi-info-circle-fill';
}

include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin Dashboard || RMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<!-- <link rel="stylesheet" href="admindash.css"> -->
<!-- <link rel="stylesheet" href="style.css"> -->
<!-- <link rel="stylesheet" href="styleNav.css"> -->
<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body { background: #f0f2f5; font-family: "Segoe UI", sans-serif; }
.navbar-custom { background: linear-gradient(135deg,#198754,#0d6efd); }
.dashboard-card { border-radius: 16px; transition: .25s; box-shadow: 0 4px 12px rgba(0,0,0,.06); background:#fff; padding:24px; text-align:center; display:flex; flex-direction:column; justify-content:center; align-items:center; border-left: 4px solid #0d6efd; }
.dashboard-card:hover { transform: translateY(-4px); box-shadow: 0 6px 16px rgba(0,0,0,0.1); }
.dashboard-card h4{ color:#6c757d; font-weight:700; font-size:1rem; margin-bottom: 0.5rem; }
.dashboard-card p{ font-size:1.8rem; color:#212529; font-weight:700; margin:0; }
.table-fixed { max-height:360px; overflow:auto; }
.upload-note { font-size: .9rem; color:#6c757d; }
.progress { height: 20px; display:none; margin-top:10px; }
.preview-img { max-width:100%; max-height:400px; object-fit:contain; }
a.card-link { text-decoration:none; color:inherit; }
.alert-area { position: fixed; top: 20px; right: 20px; z-index: 9999; width: auto; }

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

input {
    border:none !important; 
}

.badge-dash {
    
    border-radius: 10px !important;
    border: none !important;
    color:black !important;    
    font-weight: bolder;
    font-size: 1rem;
    padding: 6px 6px 6px 6px;
}

.heading-dash {
    background-color: #27A862 !important;
    color: white !important;
    border-radius: 4px !important;
}

/* delete.php */

.delete-card {
    background-color: #2FBF71 !important;
    align-items: center !important;
    color: white !important;
    
}

.delete-card:hover{
    letter-spacing: 1px !important;
    transition: 0.9s !important;
}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-custom shadow-sm mb-4">
  <div class="container-fluid">
    <span class="navbar-text text-white fs-5">DMS || </span>
    <div class="ms-auto text-white">Welcome, <?php echo htmlspecialchars($user['username']); ?></div>
  </div>
</nav>
<div class="container">
  <!-- Action Buttons -->
  <div class="row mb-3">
      <div class="col-md-3 col-6 mb-2 "><a href="addrecord.php" class="btn btn-success w-100 btn-dash">Add Record</a></div>
      <div class="col-md-3 col-6 mb-2"><a href="editrecord.php" class="btn btn-warning w-100 btn-dash" >Edit Records </a></div>
      <div class="col-md-3 col-6 mb-2"><a href="delete.php" class="btn btn-danger w-100 btn-dash">Print || Delete </a></div>
      <div class="col-md-3 col-6 mb-2"><a href="search.php" class="btn btn-primary w-100 btn-dash">Search Records</a></div>
      <div class="col-md-12 col-12 mb-2"><a href="upload_existing_document.php" class="btn btn-primary w-100 btn-dash">Upload Existing Record Document</a></div>
  </div>

  <!-- Management Links -->
  <div class="row mb-4">
      <div class="col-md-3 col-6 mb-2"><a href="manage_courts.php" class="btn btn-info w-100 text-white btn-dash">Add Courts</a></div>
      <div class="col-md-3 col-6 mb-2"><a href="status.php" class="btn btn-info w-100 text-white btn-dash">Case Status</a></div>
      <div class="col-md-3 col-6 mb-2"><a href="manage_categories.php" class="btn btn-info w-100 text-white btn-dash">Case Categories</a></div>
      <div class="col-md-3 col-6 mb-2"><a href="set_pin.php" class="btn btn-warning w-100 text-white btn-dash">Set User PIN</a></div>
      <div class="col-md-12 col-12 mb-2 text-center"><a href="access_logs.php" class="btn btn-info w-100 text-white btn-dash">View Access Logs</a></div>
  </div>

  <!-- Dashboard Cards -->
  <div class="row g-3 mb-4">
      <!-- Total Cases Updated -->
      <div class="col-md-3 col-sm-6">
          <a href="view_cases.php?status=all" class="card-link">
              <div class="dashboard-card" style="border-left-color: #0d6efd;">
                  <i class="bi bi-archive-fill mb-2" style="font-size: 2rem; color: #0d6efd;"></i>
                  <h4>Total Cases Updated</h4>
                  <p><?php echo (int)$total_records; ?></p>
              </div>
          </a>
      </div>
      <!-- Uploaded Documents -->
      <div class="col-md-3 col-sm-6">
          <a href="view_cases.php" class="card-link">
              <div class="dashboard-card" style="border-left-color: #198754;">
                  <i class="bi bi-cloud-arrow-up-fill mb-2" style="font-size: 2rem; color: #198754;"></i>
                  <h4>Uploaded Documents</h4>
                  <p><?php echo (int)$docs_count; ?></p>
              </div>
          </a>
      </div>
      <!-- Total Document Types -->
      <div class="col-md-3 col-sm-6">
          <div class="dashboard-card" style="border-left-color: #fd7e14;">
              <i class="bi bi-folder-symlink-fill mb-2" style="font-size: 2rem; color: #fd7e14;"></i>
              <h4>Total Document Types</h4>
              <p><?php echo (int)$total_doc_types; ?></p>
          </div>
      </div>
      <!-- Status Cards -->
      <?php foreach ($status_data as $s):
          $color = getStatusColor($s['status']);
          $icon = getStatusIcon($s['status']);
      ?>
      <div class="col-md-3 col-sm-6">
          <a href="view_cases.php?status=<?php echo urlencode($s['status']); ?>" class="card-link">
              <div class="dashboard-card" style="border-left-color: <?php echo $color; ?>;">
                  <i class="bi <?php echo $icon; ?> mb-2" style="font-size: 2rem; color: <?php echo $color; ?>;"></i>
                  <h4><?php echo htmlspecialchars($s['status']); ?></h4>
                  <p><?php echo (int)$s['count']; ?></p>
              </div>
          </a>
      </div>
      <?php endforeach; ?>
  </div>

  <!-- Court-wise Cases Overview Table -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h5 class="card-title text-success text-center"><i class="bi bi-building me-2"></i>Court-wise Cases Overview</h5>
      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
          <thead class="table-primary ">
            <tr>
              <th class="heading-dash">#</th>
              <th class="heading-dash">Court Name</th>
              <th class="text-center heading-dash">Number of Cases Updated</th>
            </tr>
          </thead>
          <tbody>

            <?php if (empty($court_case_counts)): ?>

              <tr><td colspan="3" class="text-center text-muted">No case records found.</td></tr>

            <?php else: $i = 1; foreach ($court_case_counts as $court): ?>

              <tr>

                <td><?php echo $i++; ?></td>                
                <td><strong><?php echo htmlspecialchars($court['court_name'] ?: 'Not Specified'); ?></strong></td>
                <td class="text-center"><span class="badge-dash">
                  <?php echo (int)$court['case_count']; ?>
                </span></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="row">
  <!-- Court-wise Document Statistics Table -->
  <div class="card shadow-sm mb-4 col-6">
    <div class="card-body">
      <h5 class="card-title text-success"><i class="bi bi-file-earmark-arrow-up me-2"></i>Court-wise Document Statistics</h5>
      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle text-center">
          <thead class="table-info">
            <tr>
              <th class="heading-dash">#</th>
              <th class="heading-dash">Court Name</th>
              <th class="text-center heading-dash ">Number of Documents Uploaded</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($court_doc_stats)): ?>
              <tr><td colspan="3" class="text-center text-muted">No documents uploaded yet.</td></tr>
            <?php else: $i = 1; foreach ($court_doc_stats as $stat): ?>
              <tr>
                <td><?php echo $i++; ?></td>
                <td><strong><?php echo htmlspecialchars($stat['court_name'] ?: 'Not Specified'); ?></strong></td>
                <td class="text-center"><span class="badge-dash"><?php echo (int)$stat['doc_count']; ?></span></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Document Types Breakdown -->
  <div class="card shadow-sm mb-4 col-6">
    <div class="card-body">
      <h5 class="card-title text-success">Document Types & Uploads</h5>
      <div class="table-responsive">
        <table class="table table-sm table-striped mb-0 text-center">
          <thead class="table-light ">
            <tr>
              <th  class="heading-dash ">#</th>
              <th  class="heading-dash">Document Type</th>
              <th  class="heading-dash">Documents Uploaded</th></tr>
          </thead>
          <tbody>
            <?php if (empty($doc_types_with_count)): ?>
              <tr><td colspan="3" class="text-center text-muted">No document types defined yet.</td></tr>
            <?php else: foreach ($doc_types_with_count as $dt): ?>
              <tr>
                <td><?php echo (int)$dt['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($dt['type_name']); ?></strong></td>
                <td><span class="badge-dash text-center"><?php echo (int)$dt['doc_count']; ?></span></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  </div>

  <!-- Manage Document Types -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h5 class="card-title text-success">Manage || Add Document Types</h5>
      <form id="addDocTypeForm" class="row g-2" method="POST" action="admindash.php">
        <input type="hidden" name="action" value="add_doc_type">
        <div class="col-md-8">
          <input type="text" name="type_name" id="type_name" class="form-control" placeholder="New document type (e.g. Judgment)" required>
        </div>
        <div class="col-md-4">
          <button class="btn btn-primary w-100 btn-dash" type="submit">Add Type</button>
        </div>
      </form>
      <hr>
      <div class="table-responsive table-fixed">
        <table class="table table-sm table-striped mb-0 text-center">
          <thead><tr><th>#</th><th>Type</th><th>Added</th><th>Action</th></tr></thead>
          <tbody id="docTypesList">
            <?php if (count($document_types) === 0): ?>
              <tr><td colspan="4" class="text-center text-muted">No document types yet.</td></tr>
            <?php else: foreach ($document_types as $dt): ?>
              <tr id="type-row-<?php echo (int)$dt['id']; ?>">
                <td><?php echo (int)$dt['id']; ?></td>
                <td class="type-name"><?php echo htmlspecialchars($dt['type_name']); ?></td>
                <td>Yes</td>
                <td>
                  <button class="btn btn-sm btn-success " onclick="openEditModal(<?php echo (int)$dt['id']; ?>, '<?php echo addslashes(htmlspecialchars($dt['type_name'])); ?>')">Edit</button>
                  <button class="btn btn-sm btn-outline-danger" onclick="deleteDocType(<?php echo (int)$dt['id']; ?>)">Delete</button>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Recent Access Logs -->
  <div class="card shadow-sm mb-5">
    <div class="card-body">
      <h5 class="card-title text-success">Recent Document Accesses</h5>
      <div class="table-responsive table-fixed">
        <table class="table table-sm table-striped mb-0 text-center responsive">
          <thead>
            <tr ><th>#</th><th>Doc ID</th><th>User</th><th>Action</th><th>Filename</th><th>Accessed At</th><th>IP</th></tr>
          </thead>
          <tbody>
            <?php if (count($recent_logs) === 0): ?>
              <tr><td colspan="7" class="text-center text-muted">No access logs yet.</td></tr>
            <?php else: foreach ($recent_logs as $log): ?>
              <tr>
                <td><?php echo (int)$log['id']; ?></td>
                <td><?php echo (int)$log['doc_id']; ?></td>
                <td><?php echo htmlspecialchars($log['username']); ?></td>
                <td><span class="badge-dash<?php echo $log['action'] === 'view' ? 'primary' : 'success'; ?>"><?php echo ucfirst($log['action']); ?></span></td>
                <td><?php echo htmlspecialchars($log['file_name']); ?></td>
                <td><?php echo htmlspecialchars($log['accessed_at']); ?></td>
                <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modals -->
<div class="modal fade" id="previewModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body text-center" id="previewContent"></div>
    </div>
  </div>
</div>
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
<div class="modal fade" id="editDocTypeModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Document Type</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="editDocTypeForm">
          <input type="hidden" id="edit_id" name="id">
          <div class="mb-3">
            <label for="edit_type_name" class="form-label">Type Name</label>
            <input type="text" class="form-control" id="edit_type_name" name="type_name" required>
          </div>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function deleteDocType(id) {
    if (confirm("Are you sure you want to delete this document type?")) {
        $.ajax({
            type: "POST",
            url: "admindash.php",
            data: { action: "delete_doc_type", id: id },
            success: function(response) {
                if (response === "OK") {
                    $("#type-row-" + id).remove();
                } else {
                    alert(response);
                }
            },
            error: function() {
                alert("Error deleting document type.");
            }
        });
    }
}

function openEditModal(id, name) {
    $("#edit_id").val(id);
    $("#edit_type_name").val(name);
    $("#editDocTypeModal").modal('show');
}

$("#editDocTypeForm").submit(function(e) {
    e.preventDefault();
    var formData = $(this).serialize() + "&action=edit_doc_type";
    $.ajax({
        type: "POST",
        url: "admindash.php",
        data: formData,
        success: function(response) {
            if (response === "OK") {
                location.reload();
            } else {
                alert(response);
            }
        },
        error: function() {
            alert("Error updating document type.");
        }
    });
});

$("#addDocTypeForm").submit(function(e) {
    e.preventDefault();
    $.ajax({
        type: "POST",
        url: "admindash.php",
        data: $(this).serialize(),
        success: function(response) {
            if (response === "OK") {
                location.reload();
            } else {
                alert(response);
            }
        },
        error: function() {
            alert("Error adding document type.");
        }
    });
});
</script>
</body>
</html>
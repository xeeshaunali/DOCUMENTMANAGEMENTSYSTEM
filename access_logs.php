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
$q = $con->prepare("SELECT `role`, `username` FROM `users` WHERE `id` = ?");
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

// === Summary Counts ===
$total_logs = safe_count($con, "SELECT COUNT(*) AS total FROM document_access_logs");
$view_count = safe_count($con, "SELECT COUNT(*) AS total FROM document_access_logs WHERE action = 'view'");
$download_count = safe_count($con, "SELECT COUNT(*) AS total FROM document_access_logs WHERE action = 'download'");

// Helper function (copied from admindash.php)
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

include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Access Logs | DMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
h2 { font-weight: 800; letter-spacing: 1px; }
table { font-size: 14px; }
.dataTables_wrapper .dataTables_length select { width: auto; display: inline-block; }
.summary-card { border-radius: 12px; transition: .25s; box-shadow: 0 4px 12px rgba(0,0,0,.06); background:#fff; padding:20px; text-align:center; border-left: 4px solid #0d6efd; }
.summary-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.1); }
.summary-card h4{ color:#6c757d; font-weight:700; font-size:1rem; margin-bottom: 0.5rem; }
.summary-card p{ font-size:1.6rem; color:#212529; font-weight:700; margin:0; }
@media print {
  .print-button, .dt-buttons, .dataTables_filter, .dataTables_length, .dataTables_info, .paginate_button {
      display: none !important;
  }
}
.filter-form { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,.05); }
</style>
</head>
<body>

<div class="container-fluid mt-4">
<h2 class="text-center text-success mb-4">Document Access Logs / DMS</h2>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="summary-card" style="border-left-color: #0d6efd;">
            <i class="bi bi-journal-text mb-2" style="font-size: 2rem; color: #0d6efd;"></i>
            <h4>Total Accesses</h4>
            <p><?php echo (int)$total_logs; ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="summary-card" style="border-left-color: #198754;">
            <i class="bi bi-eye-fill mb-2" style="font-size: 2rem; color: #198754;"></i>
            <h4>Views</h4>
            <p><?php echo (int)$view_count; ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="summary-card" style="border-left-color: #dc3545;">
            <i class="bi bi-download mb-2" style="font-size: 2rem; color: #dc3545;"></i>
            <h4>Downloads</h4>
            <p><?php echo (int)$download_count; ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="summary-card" style="border-left-color: #6f42c1;">
            <i class="bi bi-graph-up mb-2" style="font-size: 2rem; color: #6f42c1;"></i>
            <h4>Unique Users</h4>
            <p><?php echo (int)safe_count($con, "SELECT COUNT(DISTINCT user_id) AS total FROM document_access_logs"); ?></p>
        </div>
    </div>
</div>

<!-- Filters Form -->
<div class="filter-form mb-4">
    <h5 class="text-primary mb-3">Filters</h5>
    <form id="filterForm" method="GET" class="row g-3">
        <div class="col-md-2">
            <label for="from_date" class="form-label">From Date</label>
            <input type="date" name="from_date" id="from_date" class="form-control" value="<?php echo htmlspecialchars($_GET['from_date'] ?? ''); ?>">
        </div>
        <div class="col-md-2">
            <label for="to_date" class="form-label">To Date</label>
            <input type="date" name="to_date" id="to_date" class="form-control" value="<?php echo htmlspecialchars($_GET['to_date'] ?? ''); ?>">
        </div>
        <div class="col-md-2">
            <label for="username" class="form-label">User</label>
            <select name="username" id="username" class="form-select">
                <option value="">All Users</option>
                <?php
                $users = $con->query("SELECT DISTINCT username FROM users WHERE role IN ('user', 'admin') ORDER BY username");
                while ($u = $users->fetch_assoc()) {
                    $selected = ($_GET['username'] ?? '') === $u['username'] ? 'selected' : '';
                    echo "<option value=\"" . htmlspecialchars($u['username']) . "\" $selected>" . htmlspecialchars($u['username']) . "</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-2">
            <label for="courtname" class="form-label">Court (User)</label>
            <select name="courtname" id="courtname" class="form-select">
                <option value="">All Courts</option>
                <?php
                $courts = $con->query("SELECT DISTINCT courtname FROM users WHERE courtname != '' ORDER BY courtname");
                while ($c = $courts->fetch_assoc()) {
                    $selected = ($_GET['courtname'] ?? '') === $c['courtname'] ? 'selected' : '';
                    echo "<option value=\"" . htmlspecialchars($c['courtname']) . "\" $selected>" . htmlspecialchars($c['courtname']) . "</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-2">
            <label for="action" class="form-label">Action</label>
            <select name="action" id="action" class="form-select">
                <option value="">All Actions</option>
                <option value="view" <?php echo ($_GET['action'] ?? '') === 'view' ? 'selected' : ''; ?>>View</option>
                <option value="download" <?php echo ($_GET['action'] ?? '') === 'download' ? 'selected' : ''; ?>>Download</option>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
        </div>
    </form>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
  <div>
    <h6 class="mb-1">Results: <?php echo (int)$total_logs; ?> total logs</h6>
  </div>
  <div class="d-flex align-items-center gap-2">
    <button class="btn btn-success btn-sm print-button" onclick="window.print();">🖨️ Print</button>
    <button id="excelExport" class="btn btn-primary btn-sm">📊 Export Excel</button>
    <a href="access_logs.php" class="btn btn-secondary btn-sm">Clear Filters</a>
  </div>
</div>

<?php
// -------------------------
// Read & sanitize incoming filters
// -------------------------
$conditions = [];
$params = ['from_date','to_date','username','courtname','action'];

foreach ($params as $param) {
    $val = isset($_GET[$param]) ? trim($_GET[$param]) : '';
    ${$param} = $val === '' ? '' : $con->real_escape_string($val);
}

// -------------------------
// Date filter using accessed_at
// Only apply when BOTH from_date and to_date are provided
// -------------------------
if (!empty($from_date) && !empty($to_date)) {
    $from_ok = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from_date);
    $to_ok   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $to_date);

    if ($from_ok && $to_ok) {
        $from_dt = strtotime($from_date);
        $to_dt   = strtotime($to_date);
        if ($from_dt > $to_dt) {
            $tmp = $from_date;
            $from_date = $to_date;
            $to_date = $tmp;
        }

        $start_dt = $con->real_escape_string($from_date . ' 00:00:00');
        $end_dt   = $con->real_escape_string($to_date   . ' 23:59:59');
        $conditions[] = "dal.accessed_at BETWEEN '{$start_dt}' AND '{$end_dt}'";
    }
}

// -------------------------
// Other filters
// -------------------------
if ($username) $conditions[] = "u.username = '{$username}'";
if ($courtname) $conditions[] = "u.courtname = '{$courtname}'";
if ($action) $conditions[] = "dal.action = '{$action}'";

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// -------------------------
// Main query to fetch logs
// -------------------------
$sql = "
SELECT 
  dal.id, dal.doc_id, dal.user_id, dal.action, dal.accessed_at, dal.ip_address,
  u.username, u.courtname as user_court,
  cd.file_name, cd.case_id,
  c.courtname as doc_court
FROM document_access_logs dal 
JOIN users u ON dal.user_id = u.id 
JOIN case_documents cd ON dal.doc_id = cd.id 
LEFT JOIN ctccc c ON cd.case_id = c.id
{$where}
ORDER BY dal.accessed_at DESC
";

$result = $con->query($sql);

if ($result === false) {
    echo "<div class='alert alert-danger'>Database error: " . htmlspecialchars($con->error) . "</div>";
    include "footer.php";
    exit();
}

// -------------------------
// Collect results
// -------------------------
$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}
?>

<?php if (!empty($logs)): ?>
<div class="table-responsive">
<table id="logsTable" class="table table-bordered table-striped align-middle">
<thead class="table-success">
<tr>
  <th>ID</th>
  <th>Accessed At</th>
  <th>User</th>
  <th>User Court</th>
  <th>Doc Court</th>
  <th>Doc ID</th>
  <th>Case ID</th>
  <th>Filename</th>
  <th>Action</th>
  <th>IP Address</th>
</tr>
</thead>
<tbody>
<?php foreach ($logs as $log): ?>
<tr>
  <td><?= htmlspecialchars($log["id"]) ?></td>
  <td><?= htmlspecialchars($log["accessed_at"]) ?></td>
  <td><?= htmlspecialchars($log["username"]) ?></td>
  <td><?= htmlspecialchars($log["user_court"] ?? 'N/A') ?></td>
  <td><?= htmlspecialchars($log["doc_court"] ?? 'N/A') ?></td>
  <td><?= htmlspecialchars($log["doc_id"]) ?></td>
  <td><?= htmlspecialchars($log["case_id"] ?? 'N/A') ?></td>
  <td><?= htmlspecialchars($log["file_name"]) ?></td>
  <td><span class="badge bg-<?= $log['action'] === 'view' ? 'primary' : 'success'; ?>"><?= ucfirst($log['action']) ?></span></td>
  <td><?= htmlspecialchars($log["ip_address"] ?? 'N/A') ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php else: ?>
<div class="alert alert-warning text-center">No logs found<?php echo $where ? ' with current filters.' : '.'; ?></div>
<?php endif; ?>

<div class="text-center mt-3 small text-muted">
Generated on: <?= date('Y-m-d H:i:s'); ?>
</div>
</div>

<!-- Scripts -->
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
  let table = $('#logsTable').DataTable({
      pageLength: 25,
      lengthMenu: [10, 25, 50, 100, 500, 1000],
      dom: 'Blfrtip',
      buttons: [{
          extend: 'excelHtml5',
          text: '📊 Export Excel',
          className: 'btn btn-primary btn-sm'
      }]
  });
  $('#excelExport').on('click', function() {
      table.button('.buttons-excel').trigger();
  });

  // Auto-submit filters on change (optional enhancement)
  $('#filterForm select, #filterForm input[type="date"]').on('change', function() {
      $('#filterForm').submit();
  });
});
</script>

<?php include "footer.php"; ?>
</body>
</html>
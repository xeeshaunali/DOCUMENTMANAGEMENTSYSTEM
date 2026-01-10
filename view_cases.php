<?php
session_start();
include 'dbconn.php';
include 'header.php';

// Verify login
if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit();
}

// Fetch user info including courtname
$uid = $_SESSION['uid'];
$stmt = $con->prepare("SELECT role, username, courtname FROM users WHERE id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: login.php');
    exit();
}

$redirect_page = ($user['role'] === 'admin') ? 'admindash.php' : 'userdash.php';

// Get filter
$status_filter = $_GET['status'] ?? 'all';

// === COURT FILTER LOGIC ===
// Admin → sees all courts
// Regular user → sees only their court
$court_condition = '';
$params = [];
$types = '';

if ($user['role'] !== 'admin' && !empty($user['courtname'])) {
    $court_condition = " WHERE courtname = ?";
    $params[] = $user['courtname'];
    $types .= 's';
}

// Add status filter if provided
if ($status_filter !== 'all') {
    if (!empty($court_condition)) {
        $court_condition .= " AND status = ?";
    } else {
        $court_condition = " WHERE status = ?";
    }
    $params[] = $status_filter;
    $types .= 's';
}

// Final SQL
$sql = "SELECT * FROM ctccc" . $court_condition . " ORDER BY id DESC";

$stmt = $con->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$title = ($status_filter === 'all') 
    ? "Your Court Records" 
    : "Your Court — " . htmlspecialchars(ucfirst($status_filter));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> | Court DMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { 
            background: linear-gradient(135deg, #f0f7f4 0%, #e6f3ed 100%); 
            font-family: 'Segoe UI', sans-serif; 
            min-height: 100vh; 
        }
        .page-header {
            background: linear-gradient(135deg, #198754, #20c997);
            color: white;
            padding: 2.5rem;
            border-radius: 1.5rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            margin-bottom: 2rem;
        }
        .court-name {
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .controls-card {
            background: white;
            border-radius: 1.2rem;
            padding: 1.8rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .table-container {
            background: white;
            border-radius: 1.2rem;
            overflow: hidden;
            box-shadow: 0 12px 35px rgba(0,0,0,0.12);
        }
        .table thead {
            background: linear-gradient(135deg, #198754, #20c997);
            color: white;
            font-weight: 600;
        }
        .table th { cursor: pointer; transition: 0.3s; }
        .table th:hover { background-color: #157347 !important; }
        .badge { font-size: 0.85rem; padding: 0.5em 0.8em; }
        #searchInput {
            border: 3px solid #198754;
            border-radius: 50px;
            padding: 0.8rem 1.8rem;
            font-size: 1.1rem;
        }
        .btn-action {
            border-radius: 50px;
            padding: 0.7rem 2rem;
            font-weight: 600;
            font-size: 1rem;
        }
        @media print {
            body { background: white; }
            .no-print, .controls-card { display: none !important; }
            .table-container { box-shadow: none; }
            .table thead { background: #198754 !important; }
        }
    </style>
</head>
<body>
<div class="container-fluid py-5">
    <!-- Header -->
    <div class="page-header text-center">
        <h1 class="display-5 fw-bold mb-3"><?= $title ?></h1>
        <?php if ($user['role'] !== 'admin'): ?>
            <div class="court-name d-inline-block">
                Court: <strong><?= htmlspecialchars($user['courtname'] ?: 'Not Assigned') ?></strong>
            </div>
        <?php endif; ?>
        <p class="lead mt-3 mb-0">Total Records: <strong class="fs-3"><?= number_format($result->num_rows) ?></strong></p>
    </div>

    <!-- Controls -->
    <div class="controls-card no-print">
        <div class="row g-3 align-items-center">
            <div class="col-md-5">
                <input type="text" id="searchInput" class="form-control form-control-lg" placeholder="Search in your court records..." onkeyup="searchTable()">
            </div>
            <div class="col-md-3">
                <select id="rowsPerPage" class="form-select form-select-lg">
                    <option value="25">25 rows</option>
                    <option value="50" selected>50 rows</option>
                    <option value="100">100 rows</option>
                    <option value="200">200 rows</option>
                </select>
            </div>
            <div class="col-md-4 text-end">
                <button onclick="window.print()" class="btn btn-outline-success btn-action me-2">
                    Print
                </button>
                <button onclick="exportToCSV()" class="btn btn-success btn-action">
                    Export CSV
                </button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="table-container">
        <div class="table-responsive">
            <table id="caseTable" class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Court</th>
                        <th>Category</th>
                        <th>Case No</th>
                        <th>Year</th>
                        <th>Party One</th>
                        <th>Party Two</th>
                        <th>Status</th>
                        <th>CFMS Code</th>
                        <th>QC</th>
                        <th>Conf.</th>
                        <th>OCR</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['id']) ?></strong></td>
                            <td><?= htmlspecialchars($row['courtname']) ?></td>
                            <td><?= htmlspecialchars($row['casecateg']) ?></td>
                            <td><?= htmlspecialchars($row['caseno']) ?></td>
                            <td><?= htmlspecialchars($row['year']) ?></td>
                            <td><?= htmlspecialchars($row['partyone']) ?></td>
                            <td><?= htmlspecialchars($row['partytwo']) ?></td>
                            <td>
                                <span class="badge bg-primary"><?= htmlspecialchars($row['status'] ?: '—') ?></span>
                            </td>
                            <td><code><?= htmlspecialchars($row['cfms_dc_casecode'] ?: '—') ?></code></td>
                            <td>
                                <span class="badge <?= $row['qc_status'] === 'Approved' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                    <?= $row['qc_status'] ?: '—' ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $row['confidentiality'] === 'Restricted' ? 'bg-danger' : 'bg-secondary' ?>">
                                    <?= $row['confidentiality'] ?: '—' ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $row['ocr_complete'] === 'Yes' ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $row['ocr_complete'] ?: '—' ?>
                                </span>
                            </td>
                            <td class="text-start"><?= htmlspecialchars($row['remarks'] ?: '—') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="13" class="text-center text-danger py-5 fs-3">
                                No records found in your court.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-between align-items-center mt-5 no-print">
        <button id="prevBtn" class="btn btn-outline-success btn-lg">Previous</button>
        <span id="pageInfo" class="fw-bold fs-4 text-success"></span>
        <button id="nextBtn" class="btn btn-outline-success btn-lg">Next</button>
    </div>

    <!-- Back Button -->
    <div class="text-center mt-5">
        <a href="<?= $redirect_page ?>" class="btn btn-primary btn-lg px-5 shadow">
            Back to Dashboard
        </a>
    </div>
</div>

<script>
// Global vars
const table = document.getElementById('caseTable');
const tbody = table.querySelector('tbody');
const rows = Array.from(tbody.querySelectorAll('tr'));
let currentPage = 1;
let rowsPerPage = parseInt(document.getElementById('rowsPerPage').value);

// Search
function searchTable() {
    const query = document.getElementById('searchInput').value.toLowerCase();
    rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
    });
    currentPage = 1;
    displayPage();
}

// Sort
function sortTable(col) {
    const isAsc = table.querySelectorAll('th')[col].classList.toggle('asc');
    rows.sort((a, b) => {
        const aText = a.cells[col].textContent.trim();
        const bText = b.cells[col].textContent.trim();
        return isAsc ? aText.localeCompare(bText) : bText.localeCompare(aText);
    });
    rows.forEach(row => tbody.appendChild(row));
    displayPage();
}

// Pagination
function displayPage() {
    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    rows.forEach((row, i) => {
        row.style.display = (i >= start && i < end) ? '' : 'none';
    });
    const totalPages = Math.ceil(rows.length / rowsPerPage);
    document.getElementById('pageInfo').textContent = `Page ${currentPage} of ${totalPages}`;
    document.getElementById('prevBtn').disabled = currentPage === 1;
    document.getElementById('nextBtn').disabled = currentPage === totalPages;
}

// CSV Export
function exportToCSV() {
    let csv = [];
    const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent);
    csv.push(headers.join(','));

    rows.forEach(row => {
        if (row.style.display !== 'none') {
            const cells = Array.from(row.cells).map(cell => `"${cell.textContent.replace(/"/g, '""')}"`);
            csv.push(cells.join(','));
        }
    });

    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `my_court_cases_<?= date('Y-m-d') ?>.csv`;
    a.click();
}

// Event Listeners
document.getElementById('rowsPerPage').addEventListener('change', e => {
    rowsPerPage = parseInt(e.target.value);
    currentPage = 1;
    displayPage();
});
document.getElementById('prevBtn').addEventListener('click', () => { if (currentPage > 1) { currentPage--; displayPage(); }});
document.getElementById('nextBtn').addEventListener('click', () => { if (currentPage * rowsPerPage < rows.length) { currentPage++; displayPage(); }});

// Initialize
displayPage();
</script>

<?php include 'footer.php'; ?>
</body>
</html>
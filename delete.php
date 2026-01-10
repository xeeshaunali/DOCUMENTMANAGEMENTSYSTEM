<?php
session_start();
include 'dbconn.php';
include 'header.php';

// === Check login ===
if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit();
}

// === Fetch user ===
$uid = $_SESSION['uid'];
$stmt = $con->prepare("SELECT role, username, courtname FROM users WHERE id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("Session error.");
}

// === Access: Admin OR user with 'ALL' courts ===
$is_full_access = ($user['role'] === 'admin' || strtoupper(trim($user['courtname'])) === 'ALL');

if (!$is_full_access) {
    echo "<div class='text-center mt-5'><h1 class='text-danger'>Access Denied</h1><p>You don't have permission to delete records.</p></div>";
    include 'footer.php';
    exit();
}

$successMessage = $errorMessage = '';
$record = null;

// === Handle Search ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $searchId = intval($_POST['id']);
    if ($searchId <= 0) {
        $errorMessage = "Invalid ID.";
    } else {
        $stmt = $con->prepare("SELECT * FROM ctccc WHERE id = ?");
        $stmt->bind_param("i", $searchId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $record = $result->fetch_assoc();

            // Auto-fill created_by & created_court if missing
            if (empty($record['created_by']) || empty($record['created_court'])) {
                $update = $con->prepare("UPDATE ctccc SET created_by = ?, created_court = ? WHERE id = ?");
                $update->bind_param("ssi", $user['username'], $user['courtname'], $searchId);
                $update->execute();
                $update->close();

                // Refresh record
                $stmt = $con->prepare("SELECT * FROM ctccc WHERE id = ?");
                $stmt->bind_param("i", $searchId);
                $stmt->execute();
                $record = $stmt->get_result()->fetch_assoc();
            }
        } else {
            $errorMessage = "No record found with ID: $searchId";
        }
        $stmt->close();
    }
}

// === Handle Delete ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $deleteId = intval($_POST['id']);
    if ($deleteId <= 0) {
        $errorMessage = "Invalid ID.";
    } else {
        $stmt = $con->prepare("DELETE FROM ctccc WHERE id = ?");
        $stmt->bind_param("i", $deleteId);
        if ($stmt->execute()) {
            $successMessage = "Record ID $deleteId has been permanently deleted.";
            $record = null;
        } else {
            $errorMessage = "Failed to delete record. Please try again.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Record | Court DMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { 
            background: linear-gradient(135deg, #f0f7f4 0%, #e6f3ed 100%); 
            font-family: 'Segoe UI', sans-serif; 
            min-height: 100vh;
        }
        .card { 
            border-radius: 1.5rem; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.12); 
            border: none; 
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .card-header h3 { margin: 0; font-weight: 700; letter-spacing: 1px; }
        .form-control, .form-select {
            border-radius: 1rem;
            padding: 0.9rem 1.5rem;
            font-size: 1.1rem;
        }
        .btn-danger-custom {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            border-radius: 1rem;
            padding: 1rem 3rem;
            font-weight: 700;
            font-size: 1.2rem;
        }
        .btn-danger-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(220,53,69,0.4);
        }
        .table th {
            background: #198754;
            color: white;
            font-weight: 600;
        }
        .record-info {
            background: #f8fff9;
            border: 2px solid #d4edda;
            border-radius: 1rem;
            padding: 1.5rem;
        }
        @media print {
            body { background: white; }
            .no-print { display: none !important; }
            .card { box-shadow: none; border: 1px solid #ddd; }
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h3>Delete Court Record</h3>
                    <p class="mb-0 opacity-90">Admin Only • Permanent Action • Cannot Be Undone</p>
                </div>
                <div class="card-body p-5">
                    <?php if ($successMessage): ?>
                        <div class="alert alert-success text-center fs-4">
                            <?= htmlspecialchars($successMessage) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger text-center fs-5">
                            <?= htmlspecialchars($errorMessage) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Search Form -->
                    <div class="text-center mb-5">
                        <form method="POST" class="row g-3 justify-content-center">
                            <div class="col-md-6">
                                <input type="number" name="id" class="form-control form-control-lg text-center" 
                                       placeholder="Enter Record ID" required autofocus>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" name="search" class="btn btn-primary btn-lg w-100">
                                    Search Record
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Record Display -->
                    <?php if ($record): ?>
                        <div class="record-info mb-4">
                            <h4 class="text-danger text-center mb-4">
                                Record ID: <strong><?= $record['id'] ?></strong> 
                                <?php if (!empty($record['courtname'])): ?>
                                    — Court: <strong><?= htmlspecialchars($record['courtname']) ?></strong>
                                <?php endif; ?>
                            </h4>

                            <table class="table table-bordered">
                                <tbody>
                                    <?php 
                                    $exclude = ['crimeno','crimeyear','s_rbf','dateInst','dateSubmission','dateDisp','cost','row','shelf','bundle','file','underSection','ps'];
                                    foreach ($record as $key => $value): 
                                        if (in_array($key, $exclude)) continue;
                                        if ($value === null || $value === '') $value = '—';
                                    ?>
                                        <tr>
                                            <th class="text-success"><?= ucwords(str_replace('_', ' ', $key)) ?></th>
                                            <td><?= htmlspecialchars($value) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr>
                                        <th class="text-success">Created By</th>
                                        <td><?= htmlspecialchars($record['created_by'] ?? '—') ?></td>
                                    </tr>
                                    <tr>
                                        <th class="text-success">Created Court</th>
                                        <td><?= htmlspecialchars($record['created_court'] ?? '—') ?></td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="text-center mt-4">
                                <button onclick="printRecord()" class="btn btn-outline-primary btn-lg me-3">
                                    Print Record
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('⚠️ PERMANENT DELETION!\n\nAre you ABSOLUTELY sure you want to delete Record ID <?= $record['id'] ?>?\n\nThis action CANNOT be undone.');">
                                    <input type="hidden" name="id" value="<?= $record['id'] ?>">
                                    <button type="submit" name="delete" class="btn btn-danger-custom text-white">
                                        Permanently Delete Record
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="text-center mt-4">
                        <a href="admindash.php" class="btn btn-secondary btn-lg px-5">
                            Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Print only the record
function printRecord() {
    const printContent = document.querySelector('.record-info').outerHTML;
    const win = window.open('', '', 'width=900,height=700');
    win.document.write(`
        <html>
        <head><title>Record ID ${<?= $record['id'] ?? 0 ?>}</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { font-family: Arial; padding: 30px; }
            table { width: 100%; border-collapse: collapse; }
            th { background: #198754; color: white; padding: 12px; }
            td { padding: 10px; border: 1px solid #ddd; }
        </style>
        </head>
        <body>
        <h2 class="text-center text-danger">Court Record - ID: ${<?= $record['id'] ?? 0 ?>}</h2>
        ${printContent}
        <p class="text-center mt-5 text-muted">Printed on: ${new Date().toLocaleString()}</p>
        </body>
        </html>
    `);
    win.document.close();
    setTimeout(() => win.print(), 500);
}
</script>

<?php include 'footer.php'; ?>
</body>
</html>
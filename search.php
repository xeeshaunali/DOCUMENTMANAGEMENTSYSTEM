<?php
session_start();
include 'dbconn.php';
// Redirect if not logged in
if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit();
}
// Fetch logged-in user
$uid = (int)$_SESSION['uid'];
$stmt = $con->prepare("SELECT `role`, `username`, `courtname` FROM `users` WHERE `id` = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$user || !in_array($user['role'], ['admin', 'user', 'guest'])) {
    header('Location: unauthorized.php');
    exit();
}
// ========================
// COURT ACCESS LOGIC
// ========================
$isAdmin         = ($user['role'] === 'admin');
$isAllCourtsUser = (strtoupper(trim($user['courtname'] ?? '')) === 'ALL');
$hasFullCourtAccess = $isAdmin || $isAllCourtsUser;
$userCourtCode   = $hasFullCourtAccess ? null : trim($user['courtname']); // e.g. 'DLH01'
// If user has limited access → force their court in search
if (!$hasFullCourtAccess && $userCourtCode) {
    $_SESSION['forced_court'] = $userCourtCode;
} else {
    unset($_SESSION['forced_court']);
}
// Fetch courts for dropdown
$courts = $con->query("SELECT court_code, court_fullname FROM courts ORDER BY court_fullname ASC");
$statuses = $con->query("SELECT status_name FROM case_status ORDER BY status_name ASC");
include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Search - Court DMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="aos.css">
    <script src="aos.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #f0f7f4 0%, #e6f3ed 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        .search-card {
            background: white;
            border-radius: 1.2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: none;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #198754, #20c997);
            color: white;
            border-radius: 1.2rem 1.2rem 0 0 !important;
            padding: 1.5rem;
        }
        h2.page-title {
            font-weight: 700;
            letter-spacing: 1px;
            margin: 0;
        }
        .form-label {
            color: #198754;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .form-control, .form-select {
            border-radius: 0.8rem;
            border: 2px solid #e0e0e0;
            padding: 0.65rem 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.3rem rgba(25, 135, 84, 0.25);
        }
        .btn-search {
            background: linear-gradient(135deg, #198754, #20c997);
            border: none;
            border-radius: 0.8rem;
            padding: 0.9rem 3rem;
            font-weight: 600;
            font-size: 1.1rem;
            letter-spacing: 1px;
        }
        .btn-search:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(25, 135, 84, 0.4);
        }
        .court-locked {
            background-color: #f8f9fa !important;
            cursor: not-allowed;
        }
        .quick-search-box {
            background: linear-gradient(135deg, #0d6efd, #198754);
            color: white;
            border-radius: 1.2rem;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            margin-bottom: 2.5rem;
        }
        .quick-search-box h3 {
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .quick-search-box .form-control {
            border: 2px solid rgba(255,255,255,0.4);
            background: rgba(255,255,255,0.1);
            color: white;
            font-size: 1.2rem;
            padding: 0.8rem 1.2rem;
        }
        .quick-search-box .form-control::placeholder {
            color: rgba(255,255,255,0.7);
        }
        .quick-search-box .form-control:focus {
            border-color: white;
            background: rgba(255,255,255,0.2);
            color: white;
            box-shadow: 0 0 0 0.3rem rgba(255,255,255,0.3);
        }
        .quick-search-box .btn {
            border-radius: 0.8rem;
            padding: 0.8rem 2.5rem;
            font-size: 1.1rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="container py-5">

    <!-- NEW: Quick Search by Case ID -->
    <div class="quick-search-box text-center" data-aos="fade-down">
        <h3><i class="bi bi-search me-2"></i>Quick Search by Case ID</h3>
        <p class="lead mb-4">Enter the unique Case ID (record number) to instantly find a specific case</p>
        <form action="searchResult.php" method="GET" class="d-flex justify-content-center align-items-center gap-3 flex-wrap">
            <div class="col-lg-4 col-md-6 col-12">
                <input type="number" 
                       name="id" 
                       class="form-control text-center" 
                       placeholder="Enter Case ID (e.g. 1779)" 
                       min="1" 
                       required 
                       aria-label="Case ID">
            </div>
            <div>
                <button type="submit" class="btn btn-light btn-lg">
                    <i class="bi bi-lightning-charge me-2"></i>Search Now
                </button>
            </div>
        </form>
    </div>

    <!-- Original Advanced Search Section -->
    <div class="text-center mb-5" data-aos="fade-down">
        <h2 class="page-title text-success">
            Advanced Case Search
        </h2>
        <p class="text-muted lead">Search through all court records with powerful filters</p>
    </div>

    <div class="card search-card" data-aos="fade-up">
        <div class="card-header text-center">
            <h4 class="mb-0 text-white">
                Search Filters
            </h4>
        </div>
        <div class="card-body p-5">
            <form action="searchResult.php" method="GET" id="searchForm">
                <!-- Hidden field to force court for restricted users -->
                <?php if (!$hasFullCourtAccess && $userCourtCode): ?>
                    <input type="hidden" name="courtname" value="<?= htmlspecialchars($userCourtCode) ?>">
                <?php endif; ?>

                <div class="row g-4">
                    <!-- Case Information -->
                    <div class="col-md-4">
                        <label class="form-label">Case Number</label>
                        <input type="number" class="form-control" name="caseno" placeholder="e.g. 123">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Case Year</label>
                        <input type="number" class="form-control" name="year" placeholder="e.g. 2025">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">CFMS/DC Case Code</label>
                        <input type="text" class="form-control" name="cfms_dc_casecode" placeholder="Enter code">
                    </div>

                    <!-- Party Names -->
                    <div class="col-md-6">
                        <label class="form-label">Party One</label>
                        <input type="text" class="form-control" name="partyone" placeholder="First party name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Party Two</label>
                        <input type="text" class="form-control" name="partytwo" placeholder="Second party name">
                    </div>

                    <!-- Court Selection -->
                    <div class="col-md-6">
                        <label class="form-label">Court Name</label>
                        <select name="courtname" class="form-select" id="courtSelect" <?= !$hasFullCourtAccess ? 'disabled' : '' ?>>
                            <?php if ($hasFullCourtAccess): ?>
                                <option value="all" selected>ALL COURTS</option>
                                <?php
                                $courts->data_seek(0);
                                while ($court = $courts->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($court['court_code']) ?>">
                                        <?= htmlspecialchars($court['court_fullname']) ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <?php
                                $stmt = $con->prepare("SELECT court_fullname FROM courts WHERE court_code = ?");
                                $stmt->bind_param("s", $userCourtCode);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $courtName = $result->fetch_assoc()['court_fullname'] ?? 'Your Court';
                                $stmt->close();
                                ?>
                                <option value="<?= htmlspecialchars($userCourtCode) ?>" selected>
                                    <?= htmlspecialchars($courtName) ?> (Your Court Only)
                                </option>
                            <?php endif; ?>
                        </select>
                        <?php if (!$hasFullCourtAccess): ?>
                            <small class="text-muted d-block mt-1">
                                You can only search records from your assigned court.
                            </small>
                        <?php endif; ?>
                    </div>

                    <!-- Status -->
                    <div class="col-md-6">
                        <label class="form-label">Case Status</label>
                        <select name="status" class="form-select">
                            <option value="all" selected>ALL STATUSES</option>
                            <?php
                            $statuses->data_seek(0);
                            while ($status = $statuses->fetch_assoc()):
                            ?>
                                <option value="<?= htmlspecialchars($status['status_name']) ?>">
                                    <?= htmlspecialchars($status['status_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Date Range -->
                    <div class="col-md-6">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" name="from_date">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" name="to_date">
                    </div>

                    <!-- Advanced Filters -->
                    <div class="col-md-4">
                        <label class="form-label">QC Status</label>
                        <select name="qc_status" class="form-select">
                            <option value="all" selected>ALL</option>
                            <option value="Approved">Approved</option>
                            <option value="Pending">Pending</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Confidentiality</label>
                        <select name="confidentiality" class="form-select">
                            <option value="all" selected>ALL</option>
                            <option value="Restricted">Restricted</option>
                            <option value="Non-Restricted">Non-Restricted</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">OCR Complete</label>
                        <select name="ocr_complete" class="form-select">
                            <option value="all" selected>ALL</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                </div>

                <div class="text-center mt-5">
                    <button type="submit" class="btn btn-success btn-search text-white">
                        Search Records
                    </button>
                    <a href="admindash.php" class="btn btn-outline-secondary btn-lg ms-3">
                        Back to Dashboard
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="text-center mt-5">
        <?php include "footer.php"; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.min.js"></script>
<script src="aos.js"></script>
<script>
AOS.init({ duration: 800, once: true });

// Optional: Visual feedback for locked court
document.addEventListener('DOMContentLoaded', function() {
    const courtSelect = document.getElementById('courtSelect');
    if (courtSelect && courtSelect.disabled) {
        courtSelect.classList.add('court-locked');
    }
});
</script>
</body>
</html>
<?php
session_start();
include 'dbconn.php';

// Check login
if (!isset($_SESSION['uid'])) {
    header('Location: ../login.php');
    exit();
}

// Fetch user
$uid = $_SESSION['uid'];
$qry = "SELECT `role`, `username`, `courtname` FROM `users` WHERE `id` = ?";
$stmt = $con->prepare($qry);
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "<script>alert('Session expired. Please login again.'); window.location='login.php';</script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>alert('Invalid access.'); window.location='editrecord.php';</script>";
    exit();
}

// Get and sanitize all fields
$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    echo "<script>alert('Invalid Record ID.'); window.history.back();</script>";
    exit();
}

$courtname        = trim($_POST['courtname'] ?? '');
$casecateg        = trim($_POST['casecateg'] ?? '');
$caseno_raw       = trim($_POST['caseno'] ?? '');
$year             = trim($_POST['year'] ?? '');
$partyone         = trim($_POST['partyone'] ?? '');
$partytwo         = trim($_POST['partytwo'] ?? '');
$status           = trim($_POST['status'] ?? '');
$remarks          = trim($_POST['remarks'] ?? '');
$cfms_dc_casecode = trim($_POST['cfms_dc_casecode'] ?? '');
$qc_status        = $_POST['qc_status'] ?? 'Pending';
$confidentiality  = $_POST['confidentiality'] ?? 'Non-Restricted';
$ocr_complete     = $_POST['ocr_complete'] ?? 'No';

// Critical Fix: Convert caseno to integer safely
$caseno = ($caseno_raw !== '' && is_numeric($caseno_raw)) ? intval($caseno_raw) : 0;
if ($caseno <= 0) {
    echo "<script>alert('Case Number must be a valid number greater than 0.'); window.history.back();</script>";
    exit();
}

// Required field validation
if (empty($courtname) || empty($casecateg) || empty($year)) {
    echo "<script>alert('Court, Category, Case Number, and Year are required.'); window.history.back();</script>";
    exit();
}

// Update query
$query = "UPDATE ctccc SET
    courtname = ?,
    casecateg = ?,
    caseno = ?,
    year = ?,
    partyone = ?,
    partytwo = ?,
    status = ?,
    remarks = ?,
    cfms_dc_casecode = ?,
    qc_status = ?,
    confidentiality = ?,
    ocr_complete = ?,
    last_updated = CURRENT_TIMESTAMP
    WHERE id = ?";

$stmt = $con->prepare($query);
if ($stmt === false) {
    die("Prepare failed: " . $con->error);
}

// Correct bind_param types: caseno and id are integers → "i"
$stmt->bind_param(
    "ssisssssssssi",    // s=string, i=integer
    $courtname,
    $casecateg,
    $caseno,           // now proper integer
    $year,
    $partyone,
    $partytwo,
    $status,
    $remarks,
    $cfms_dc_casecode,
    $qc_status,
    $confidentiality,
    $ocr_complete,
    $id                // integer
);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo "<script>
            alert('Record ID $id updated successfully!');
            window.location.href = 'editrecord.php?id=$id';
        </script>";
    } else {
        echo "<script>
            alert('No changes were made (or record not found).');
            window.location.href = 'editrecord.php?id=$id';
        </script>";
    }
} else {
    $error = addslashes($stmt->error);
    echo "<script>
        alert('Database Error: $error');
        window.history.back();
    </script>";
}

$stmt->close();
$con->close();
?>
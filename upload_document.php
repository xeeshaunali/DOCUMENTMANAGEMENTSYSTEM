<?php
session_start();
include 'dbconn.php';

// Check login
if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit();
}

// Fetch user info
$uid = $_SESSION['uid'];
$stmt = $con->prepare("SELECT username, role, courtname FROM users WHERE id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "<script>alert('Session expired. Please login again.'); window.location='login.php';</script>";
    exit();
}

$uploaded_by = $user['username'] ?? 'Unknown';
$redirect_page = ($user['role'] === 'admin') ? 'admindash.php' : 'userdash.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>alert('Invalid access.'); window.location='{$redirect_page}';</script>";
    exit();
}

// Get and validate inputs
$case_id = intval($_POST['case_id'] ?? 0);
$type_id = intval($_POST['type_id'] ?? ($_POST['document_type_id'] ?? 0));

if ($case_id <= 0) {
    echo "<script>alert('Invalid Case ID.'); window.history.back();</script>";
    exit();
}
if ($type_id <= 0) {
    echo "<script>alert('Please select a Document Type.'); window.history.back();</script>";
    exit();
}
if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
    echo "<script>alert('No file uploaded or upload failed.'); window.history.back();</script>";
    exit();
}

// Step 1: Get case details
$stmt = $con->prepare("SELECT cfms_dc_casecode, courtname FROM ctccc WHERE id = ?");
$stmt->bind_param("i", $case_id);
$stmt->execute();
$case = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$case) {
    echo "<script>alert('Case ID $case_id not found.'); window.history.back();</script>";
    exit();
}

$case_code = preg_replace('/\s+/', '_', $case['cfms_dc_casecode'] ?: "Case{$case_id}");
$court_folder = preg_replace('/\s+/', '_', $case['courtname']);

// Step 2: Get document type name
$stmt = $con->prepare("SELECT type_name FROM document_types WHERE id = ?");
$stmt->bind_param("i", $type_id);
$stmt->execute();
$type = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$type) {
    echo "<script>alert('Invalid Document Type.'); window.history.back();</script>";
    exit();
}

$type_name = preg_replace('/\s+/', '_', $type['type_name']);

// Step 3: File validation
$fileTmpPath = $_FILES['document']['tmp_name'];
$fileExt = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
$allowed = ['jpg','jpeg','png','pdf','doc','docx','xls','xlsx','csv'];

if (!in_array($fileExt, $allowed)) {
    echo "<script>alert('Invalid file type. Allowed: " . implode(', ', $allowed) . "'); window.history.back();</script>";
    exit();
}

// Step 4: Versioning
$stmt = $con->prepare("SELECT COUNT(*) FROM case_documents WHERE case_id = ? AND type_id = ?");
$stmt->bind_param("ii", $case_id, $type_id);
$stmt->execute();
$version = ($stmt->get_result()->fetch_row()[0] ?? 0) + 1;
$stmt->close();

$newFileName = "{$case_code}_{$type_name}_v{$version}.{$fileExt}";

// Step 5: Create folders and move file
$uploadDir = __DIR__ . "/uploads/{$court_folder}/{$case_code}";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$destPath = "$uploadDir/$newFileName";

if (!move_uploaded_file($fileTmpPath, $destPath)) {
    echo "<script>alert('Failed to save file. Check folder permissions.'); window.history.back();</script>";
    exit();
}

// Step 6: Save to database
$dbFilePath = "uploads/{$court_folder}/{$case_code}/{$newFileName}";

$stmt = $con->prepare("
    INSERT INTO case_documents 
    (case_id, type_id, file_name, file_path, uploaded_by, courtname, qc_status, confidentiality)
    VALUES (?, ?, ?, ?, ?, ?, 'Pending', 'Non-Restricted')
");
$stmt->bind_param("iissss", $case_id, $type_id, $newFileName, $dbFilePath, $uploaded_by, $case['courtname']);

if ($stmt->execute()) {
    echo "<script>
        alert('Document uploaded successfully!\\nCase ID: {$case_id}\\nFile: {$newFileName}');
        window.location.href = '{$redirect_page}';
    </script>";
} else {
    // Rollback: delete file if DB failed
    if (file_exists($destPath)) unlink($destPath);
    echo "<script>
        alert('Database error: " . addslashes($stmt->error) . "');
        window.history.back();
    </script>";
}

$stmt->close();
$con->close();
?>
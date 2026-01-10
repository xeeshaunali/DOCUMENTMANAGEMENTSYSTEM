<?php
ob_start();
session_start();

// Security: Block if no session
if (!isset($_SESSION['uid'])) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!file_exists('dbconn.php')) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'System error']);
    exit;
}
include 'dbconn.php';

// Get and validate input
$input_pin = trim($_POST['pin'] ?? '');
$action    = $_POST['action'] ?? '';
$doc_id    = intval($_POST['doc_id'] ?? 0);

if ($doc_id <= 0 || !in_array($action, ['view', 'download'])) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if (!preg_match('/^\d{6}$/', $input_pin)) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'PIN must be exactly 6 digits']);
    exit;
}

// === Fetch current user and their PIN ===
$uid = $_SESSION['uid'];
$stmt = $con->prepare("SELECT pin, username, role, courtname FROM users WHERE id = ?");
if (!$stmt) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || empty($user['pin'])) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No PIN set for your account']);
    exit;
}

// === Verify PIN ===
if (!password_verify($input_pin, $user['pin'])) {
    // Log failed attempt (optional security enhancement)
    $log_stmt = $con->prepare("INSERT INTO document_access_logs (doc_id, user_id, action, ip_address, user_agent) VALUES (?, ?, 'failed_pin', ?, ?, ?)");
    if ($log_stmt) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $log_stmt->bind_param("iisss", $doc_id, $uid, $ip, $ua);
        $log_stmt->execute();
        $log_stmt->close();
    }
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid PIN']);
    exit;
}

// === Fetch document path ===
$stmt = $con->prepare("SELECT file_path, file_name, confidentiality FROM case_documents WHERE id = ?");
if (!$stmt) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}
$stmt->bind_param("i", $doc_id);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$doc || !file_exists($doc['file_path'])) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'File not found or deleted']);
    exit;
}

// === OPTIONAL: Re-allow access even if Restricted (since you removed court restriction) ===
// No court check → all users can access any document after correct PIN

// === Log successful access ===
$log_stmt = $con->prepare("
    INSERT INTO document_access_logs 
    (doc_id, user_id, action, ip_address, user_agent) 
    VALUES (?, ?, ?, ?, ?)
");
if ($log_stmt) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $log_stmt->bind_param("iisss", $doc_id, $uid, $action, $ip, $ua);
    $log_stmt->execute();
    $log_stmt->close();
}

// === SUCCESS: Return file path ===
ob_end_clean();
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'path'    => $doc['file_path'],
    'action'  => $action,
    'filename'=> $doc['file_name']
]);
exit;
?>
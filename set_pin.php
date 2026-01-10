<?php
session_start();
include 'dbconn.php';
// === NO OUTPUT BEFORE THIS ===
if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}
$stmt = $con->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['uid']);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($admin['role'] !== 'admin') {
    die("Access denied.");
}
$message = '';
$error = '';
$target_user_id = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_user_id = intval($_POST['user_id'] ?? 0);
} else {
    $target_user_id = intval($_GET['user_id'] ?? 0);
}
// === HANDLE PIN UPDATE ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $target_user_id > 0) {
    $pin = trim($_POST['pin'] ?? '');
    if (strlen($pin) !== 6 || !ctype_digit($pin)) {
        $error = "PIN must be exactly 6 digits.";
    } else {
        $hashed = password_hash($pin, PASSWORD_DEFAULT);
        $stmt = $con->prepare("UPDATE users SET pin = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $target_user_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "PIN updated successfully!";
        } else {
            $error = "Failed to update. User not found or same PIN.";
        }
        $stmt->close();
    }
}
// === FETCH USERS ===
$users = [];
$res = $con->query("SELECT id, username, courtname, role FROM users ORDER BY username");
while ($row = $res->fetch_assoc()) {
    $users[] = $row;
}
// === CHECK CURRENT PIN STATUS ===
$current_pin_set = false;
if ($target_user_id > 0) {
    $stmt = $con->prepare("SELECT pin FROM users WHERE id = ?");
    $stmt->bind_param("i", $target_user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $current_pin_set = !empty($row['pin']);
    $stmt->close();
}
?>
<?php include 'header.php'; ?>
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-body">
            <h3 class="text-success text-center mb-4">Set User PIN</h3>
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" id="pinForm">
                <div class="mb-3">
                    <label class="form-label fw-bold">Select User</label>
                    <select name="user_id" class="form-select" required onchange="this.form.submit()">
                        <option value="">-- Select User --</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?php echo $u['id']; ?>" <?php echo $u['id'] == $target_user_id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($u['username'] . " - " . $u['courtname'] . " (" . $u['role'] . ")"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($target_user_id > 0): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Enter 6-Digit PIN</label>
                        <input type="text" name="pin" class="form-control text-center"
                               maxlength="6" pattern="\d{6}" inputmode="numeric"
                               placeholder="123456" required autofocus>
                        <div class="form-text">Current: <strong><?php echo $current_pin_set ? 'Set' : 'Not Set'; ?></strong></div>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Update PIN</button>
                <?php endif; ?>
            </form>
            <div class="mt-3 text-center">
                <a href="admindash.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
<script>
// Auto-focus PIN input
document.querySelector('input[name="pin"]')?.focus();
</script>
</body>
</html>
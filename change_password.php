<?php
session_start();
include('dbconn.php');

// ✅ Check if the user is logged in
if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

$successMessage = '';
$errorMessage = '';
$currentUserId = $_SESSION['uid'];
$isAdmin = $_SESSION['role'] === 'admin';

// ✅ Fetch users (admin can see all, user sees only self)
$users = [];
if ($isAdmin) {
    $query = "SELECT id, username FROM users";
    $result = $con->query($query);
    if ($result) {
        $users = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $errorMessage = 'Error fetching users: ' . $con->error;
    }
} else {
    $query = "SELECT id, username FROM users WHERE id = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param('i', $currentUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $users = [$result->fetch_assoc()];
    } else {
        $errorMessage = 'Error fetching user data: ' . $stmt->error;
    }
    $stmt->close();
}

// ✅ Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? $currentUserId;
    $newPassword = trim($_POST['new_password']);
    $confirmPassword = trim($_POST['confirm_password']);

    if ($newPassword !== $confirmPassword) {
        $errorMessage = '❌ Passwords do not match!';
    } elseif (strlen($newPassword) < 4) {
        $errorMessage = '⚠️ Password must be at least 4 characters long.';
    } elseif (!$isAdmin && $userId != $currentUserId) {
        $errorMessage = '⛔ You can only change your own password.';
    } else {
        $query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $con->prepare($query);
        if ($stmt === false) {
            $errorMessage = 'Database error: ' . $con->error;
        } else {
            $stmt->bind_param('si', $newPassword, $userId);
            if ($stmt->execute()) {
                $successMessage = '✅ Password updated successfully.';
                if ($isAdmin) {
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = 'admindash.php';
                        }, 2000);
                    </script>";
                }
            } else {
                $errorMessage = 'Error updating password: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<?php include 'header.php'; ?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5 shadow p-4 rounded bg-light" data-aos="fade-up">
            <h3 class="text-center mb-4 text-success fw-bold">Change Password</h3>

            <!-- Success / Error Messages -->
            <?php if ($successMessage): ?>
                <div class="alert alert-success text-center"><?php echo $successMessage; ?></div>
            <?php endif; ?>
            <?php if ($errorMessage): ?>
                <div class="alert alert-danger text-center"><?php echo $errorMessage; ?></div>
            <?php endif; ?>

            <!-- Password Change Form -->
            <form method="POST" action="">
                <?php if ($isAdmin): ?>
                    <!-- Admin: Select user -->
                    <div class="mb-3">
                        <label for="user_id" class="form-label text-success fw-bold">Select User</label>
                        <select class="form-select text-center shadow-sm" name="user_id" id="user_id" required>
                            <option value="" disabled selected>Choose user</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo htmlspecialchars($user['id']); ?>">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <!-- User: Only own info -->
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($currentUserId); ?>">
                    <div class="mb-3">
                        <label class="form-label text-success fw-bold">Username</label>
                        <input type="text" class="form-control text-center shadow-sm" value="<?php echo htmlspecialchars($users[0]['username']); ?>" disabled>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label for="new_password" class="form-label text-success fw-bold">New Password</label>
                    <input type="password" class="form-control text-center shadow-sm" name="new_password" id="new_password" required placeholder="Enter new password">
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label text-success fw-bold">Confirm Password</label>
                    <input type="password" class="form-control text-center shadow-sm" name="confirm_password" id="confirm_password" required placeholder="Confirm new password">
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-success btn-lg w-50">Update</button>
                </div>

                <div class="text-center mt-3">
                    <a href="<?php echo $isAdmin ? 'admindash.php' : 'userdash.php'; ?>" class="btn btn-outline-secondary">← Back</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
AOS.init({ duration: 800, once: true });
</script>

<script src="js/bootstrap.min.js"></script>
</body>
</html>

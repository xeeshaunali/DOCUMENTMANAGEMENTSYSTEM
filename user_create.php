<?php 
session_start();
include('dbconn.php');

// ✅ Restrict access to admin only
if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$successMessage = '';
$errorMessage = '';

// ✅ Fetch courts dynamically
$courts = [];
$courtQuery = "SELECT court_code, court_fullname FROM courts ORDER BY id ASC";
$result = $con->query($courtQuery);
if ($result && $result->num_rows > 0) {
    $courts = $result->fetch_all(MYSQLI_ASSOC);
}

// ✅ Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];
    $courtname = trim($_POST['courtname'] ?? '');

    if (empty($username) || empty($password) || empty($role)) {
        $errorMessage = 'Username, password, and role are required.';
    } elseif (strlen($username) < 3) {
        $errorMessage = 'Username must be at least 3 characters long.';
    } elseif (strlen($password) < 4) {
        $errorMessage = 'Password must be at least 4 characters long.';
    } elseif (!in_array($role, ['admin', 'user', 'guest'])) {
        $errorMessage = 'Invalid role selected.';
    } else {
        // Check for duplicate username
        $stmt = $con->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errorMessage = 'Username already exists.';
        } else {
            // Insert new user
            $stmt = $con->prepare("INSERT INTO users (username, password, role, courtname) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $password, $role, $courtname);
            if ($stmt->execute()) {
                $successMessage = '✅ User created successfully.';
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'admindash.php';
                    }, 2000);
                </script>";
            } else {
                $errorMessage = 'Database error: ' . $stmt->error;
            }
        }
        $stmt->close();
    }
}

// ✅ Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    if ($user_id == $_SESSION['uid']) {
        $errorMessage = '⚠️ You cannot delete your own account.';
    } else {
        $stmt = $con->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $successMessage = '✅ User deleted successfully.';
        } else {
            $errorMessage = 'Error deleting user: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// ✅ Fetch all users
$stmt = $con->prepare("SELECT id, username, role, courtname FROM users ORDER BY id ASC");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<?php include 'header.php'; ?>

<div class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 shadow p-4 rounded bg-light" data-aos="fade-up">
            <h3 class="text-center mb-4 text-success fw-bold">Create New User</h3>

            <!-- Success / Error Messages -->
            <?php if ($successMessage): ?>
                <div class="alert alert-success text-center"><?php echo $successMessage; ?></div>
            <?php endif; ?>
            <?php if ($errorMessage): ?>
                <div class="alert alert-danger text-center"><?php echo $errorMessage; ?></div>
            <?php endif; ?>

            <!-- Create User Form -->
            <form method="POST" action="">
                <input type="hidden" name="create_user" value="1">

                <div class="mb-3">
                    <label for="username" class="form-label text-success fw-bold">Username</label>
                    <input type="text" class="form-control text-center shadow-sm" name="username" id="username" required placeholder="Enter username">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label text-success fw-bold">Password</label>
                    <input type="password" class="form-control text-center shadow-sm" name="password" id="password" required placeholder="Enter password">
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label text-success fw-bold">Role</label>
                    <select class="form-select text-center shadow-sm" name="role" id="role" required>
                        <option value="" disabled selected>Select a role</option>
                        <option value="admin">Admin</option>
                        <option value="user">User</option>
                        <option value="guest">Guest</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="courtname" class="form-label text-success fw-bold">Court Name</label>
                    <select class="form-select text-center shadow-sm" name="courtname" id="courtname">
                        <option value="" selected>Select a court (optional)</option>
                        <?php foreach ($courts as $court): ?>
                            <option value="<?php echo htmlspecialchars($court['court_code']); ?>">
                                <?php echo htmlspecialchars($court['court_fullname']); ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="ALL">ALL</option>
                    </select>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-success btn-lg w-50">Create User</button>
                </div>

                <div class="text-center mt-3">
                    <a href="admindash.php" class="btn btn-outline-secondary">← Back</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Manage Users Table -->
    <div class="row justify-content-center mt-5">
        <div class="col-lg-8 shadow p-4 rounded bg-white" data-aos="fade-up">
            <h3 class="text-center mb-4 text-success fw-bold">Manage Users</h3>

            <?php if (empty($users)): ?>
                <p class="text-center text-muted">No users found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center">
                        <thead class="table-success">
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Court Name</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['role']); ?></td>
                                    <td><?php echo htmlspecialchars($user['courtname'] ?? '—'); ?></td>
                                    <td>
                                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete user <?php echo htmlspecialchars($user['username']); ?>?');">
                                            <input type="hidden" name="delete_user" value="1">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
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

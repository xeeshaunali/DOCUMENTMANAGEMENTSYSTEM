<?php
session_start();
include 'dbconn.php';

// ✅ Check if user logged in
if (!isset($_SESSION['uid'])) {
    header('Location: ../login.php');
    exit();
}

// ✅ Fetch user info
$uid = $_SESSION['uid'];
$qry = "SELECT `role`, `username` FROM `users` WHERE `id` = ?";
$stmt = $con->prepare($qry);
$stmt->bind_param("i", $uid);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// ✅ Allow only admin access
if ($user_data['role'] !== 'admin') {
    header('Location: unauthorized.php');
    exit();
}

// ✅ Handle DELETE request
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $del_stmt = $con->prepare("DELETE FROM case_status WHERE id = ?");
    $del_stmt->bind_param("i", $delete_id);
    if ($del_stmt->execute()) {
        echo "<script>alert('Status deleted successfully!'); window.location='status.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error deleting status.');</script>";
    }
    $del_stmt->close();
}

// ✅ Handle form submission for adding new status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status_name'])) {
    $new_status = trim($_POST['status_name']);

    if (!empty($new_status)) {
        // Prevent duplicates
        $check = $con->prepare("SELECT id FROM case_status WHERE status_name = ?");
        $check->bind_param("s", $new_status);
        $check->execute();
        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {
            echo "<script>alert('Status already exists in database.');</script>";
        } else {
            $insert = $con->prepare("INSERT INTO case_status (status_name) VALUES (?)");
            $insert->bind_param("s", $new_status);
            if ($insert->execute()) {
                echo "<script>alert('New status added successfully!'); window.location='status.php';</script>";
                exit();
            } else {
                echo "<script>alert('Database error while adding status.');</script>";
            }
            $insert->close();
        }
        $check->close();
    } else {
        echo "<script>alert('Please enter a status name.');</script>";
    }
}

// ✅ Fetch all statuses from database
$status_result = $con->query("SELECT * FROM case_status ORDER BY status_name ASC");

include 'header.php';
?>

<style>
    label {
        color: green;
        font-weight: bold;
        letter-spacing: 0.05rem;
    }
    .table th {
        background: #198754;
        color: white;
    }
    .table td, .table th {
        text-align: center;
        vertical-align: middle;
    }
    .btn-delete {
        color: #fff;
        background-color: #dc3545;
        border: none;
        padding: 4px 10px;
        border-radius: 4px;
        transition: 0.2s;
    }
    .btn-delete:hover {
        background-color: #c82333;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto mt-5">
            <h3 class="text-center text-success fw-bold mb-4">📋 Manage Case Status</h3>

            <!-- Add Status Form -->
            <form method="POST" class="row g-3 mb-5 shadow p-4 rounded bg-light">
                <div class="col-md-8 mx-auto">
                    <label for="status_name" class="form-label">Add New Case Status</label>
                    <input type="text" class="form-control text-center shadow-sm rounded" id="status_name" name="status_name" placeholder="Enter new case status" required>
                </div>
                <div class="col-md-12 text-center mt-3">
                    <button type="submit" class="btn btn-success w-50">Add Status</button>
                </div>
            </form>

            <!-- Status List -->
            <div class="table-responsive shadow rounded">
                <table class="table table-bordered table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Case Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($status_result->num_rows > 0) {
                            $count = 1;
                            while ($row = $status_result->fetch_assoc()) {
                                echo "<tr>
                                    <td>{$count}</td>
                                    <td>" . htmlspecialchars($row['status_name']) . "</td>
                                    <td>
                                        <a href='status.php?delete_id={$row['id']}' onclick=\"return confirm('Are you sure you want to delete this status?');\" class='btn-delete'>Delete</a>
                                    </td>
                                </tr>";
                                $count++;
                            }
                        } else {
                            echo "<tr><td colspan='3' class='text-center text-danger'>No case statuses found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="text-center mt-4 mb-5">
                <a href="admindash.php" class="btn btn-outline-success">← Back to Admin Dashboard</a>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12 text-center">
            <?php include "footer.php"; ?>
        </div>
    </div>
</div>

</body>
</html>

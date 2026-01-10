<?php
session_start();
include 'dbconn.php';

// Check admin role
if (!isset($_SESSION['uid'])) {
    header('Location: ../login.php');
    exit();
}

$uid = $_SESSION['uid'];
$q = $con->prepare("SELECT role FROM users WHERE id=?");
$q->bind_param("i", $uid);
$q->execute();
$role = $q->get_result()->fetch_assoc()['role'];
if ($role !== 'admin') {
    header('Location: ../unauthorized.php');
    exit();
}

// ADD COURT
if (isset($_POST['add_court'])) {
    $code = trim($_POST['court_code']);
    $name = trim($_POST['court_fullname']);
    if ($code && $name) {
        $stmt = $con->prepare("INSERT INTO courts (court_code, court_fullname) VALUES (?, ?)");
        $stmt->bind_param("ss", $code, $name);
        $stmt->execute();
        echo "<script>alert('Court added successfully');</script>";
    }
}

// DELETE COURT
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $con->query("DELETE FROM courts WHERE id=$id");
    echo "<script>alert('Court deleted'); window.location='manage_courts.php';</script>";
}

include 'header.php';
?>
<div class="container mt-4">
    <h2 class="text-center mb-4">Manage Courts</h2>
    <form method="POST" class="row g-3 mb-4">
        <div class="col-md-4">
            <input type="text" name="court_code" class="form-control" placeholder="Court Code (e.g., DJ)" required>
        </div>
        <div class="col-md-6">
            <input type="text" name="court_fullname" class="form-control" placeholder="Full Court Name" required>
        </div>
        <div class="col-md-2 text-center">
            <button type="submit" name="add_court" class="btn btn-success w-100">Add Court</button>
        </div>
    </form>

    <table class="table table-bordered table-striped">
        <thead class="table-success">
            <tr>
                <th>ID</th>
                <th>Code</th>
                <th>Full Name</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $res = $con->query("SELECT * FROM courts ORDER BY id DESC");
        while ($r = $res->fetch_assoc()) {
            echo "<tr>
                <td>{$r['id']}</td>
                <td>{$r['court_code']}</td>
                <td>{$r['court_fullname']}</td>
                <td><a href='?delete={$r['id']}' onclick='return confirm(\"Delete this court?\")' class='btn btn-danger btn-sm'>Delete</a></td>
            </tr>";
        }
        ?>
        </tbody>
    </table>
</div>
<?php include 'footer.php'; ?>

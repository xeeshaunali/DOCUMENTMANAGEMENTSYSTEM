<?php
session_start();
include 'dbconn.php';

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

// ADD PS
if (isset($_POST['add_ps'])) {
    $ps = trim($_POST['ps_name']);
    if ($ps) {
        $stmt = $con->prepare("INSERT INTO policestations (ps_name) VALUES (?)");
        $stmt->bind_param("s", $ps);
        $stmt->execute();
        echo "<script>alert('Police Station added');</script>";
    }
}

// DELETE PS
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $con->query("DELETE FROM policestations WHERE id=$id");
    echo "<script>alert('Police Station deleted'); window.location='manage_ps.php';</script>";
}

include 'header.php';
?>
<div class="container mt-4">
    <h2 class="text-center mb-4">Manage Police Stations</h2>
    <form method="POST" class="row g-3 mb-4">
        <div class="col-md-10">
            <input type="text" name="ps_name" class="form-control" placeholder="Enter Police Station Name" required>
        </div>
        <div class="col-md-2 text-center">
            <button type="submit" name="add_ps" class="btn btn-success w-100">Add PS</button>
        </div>
    </form>

    <table class="table table-bordered table-striped">
        <thead class="table-success">
            <tr>
                <th>ID</th>
                <th>Police Station Name</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $res = $con->query("SELECT * FROM policestations ORDER BY id DESC");
        while ($r = $res->fetch_assoc()) {
            echo "<tr>
                <td>{$r['id']}</td>
                <td>{$r['ps_name']}</td>
                <td><a href='?delete={$r['id']}' onclick='return confirm(\"Delete this police station?\")' class='btn btn-danger btn-sm'>Delete</a></td>
            </tr>";
        }
        ?>
        </tbody>
    </table>
</div>
<?php include 'footer.php'; ?>

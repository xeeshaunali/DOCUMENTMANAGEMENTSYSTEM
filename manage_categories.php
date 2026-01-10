<?php
session_start();
include 'dbconn.php';
include 'header.php';

// Allow only admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$success = $error = "";

// Handle new category addition
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    if (!empty($category_name)) {
        $stmt = $con->prepare("INSERT INTO case_categories (category_name) VALUES (?)");
        $stmt->bind_param("s", $category_name);
        if ($stmt->execute()) {
            $success = "✅ Category added successfully.";
        } else {
            $error = "❌ Error adding category: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "⚠️ Category name cannot be empty.";
    }
}

// Handle category deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $con->prepare("DELETE FROM case_categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = "🗑️ Category deleted successfully.";
    } else {
        $error = "❌ Error deleting category: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch all categories
$result = $con->query("SELECT * FROM case_categories ORDER BY category_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Case Categories</title>
    <link rel="stylesheet" href="css/bootstrap.css">
    <style>
        .container {
            margin-top: 3rem;
        }
        .form-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 2rem;
        }
        .table-box {
            background: #fff;
            border-radius: 10px;
            padding: 1.5rem;
        }
    </style>
</head>
<body>
<div class="container">
    <h3 class="text-center text-success mb-4">Manage Case Categories</h3>

    <?php if ($success): ?>
        <div class="alert alert-success text-center"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger text-center"><?= $error ?></div>
    <?php endif; ?>

    <!-- Add New Category Form -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-6 form-box shadow">
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="category_name" class="form-label text-success fw-bold">Category Name</label>
                    <input type="text" id="category_name" name="category_name" class="form-control text-center shadow-sm" placeholder="Enter category name" required>
                </div>
                <div class="text-center">
                    <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                    <a href="admindash.php" class="btn btn-secondary">Back</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Categories Table -->
    <div class="row justify-content-center">
        <div class="col-md-8 table-box shadow">
            <h5 class="text-success text-center mb-3">Existing Categories</h5>
            <table class="table table-bordered table-hover text-center">
                <thead class="table-success">
                    <tr>
                        <th>ID</th>
                        <th>Category Name</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']); ?></td>
                            <td><?= htmlspecialchars($row['category_name']); ?></td>
                            <td>
                                <a href="?delete=<?= $row['id']; ?>" onclick="return confirm('Are you sure?');" class="btn btn-danger btn-sm">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if ($result->num_rows === 0): ?>
                        <tr><td colspan="3">No categories found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>

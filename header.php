<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not authenticated
if (!isset($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

// Determine dashboard URL
$validRoles = ['admin', 'user'];
$dashboard = in_array($_SESSION['role'] ?? '', $validRoles) && $_SESSION['role'] === 'admin'
    ? 'admindash.php'
    : 'userdash.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DMS | District & Sessions Court Malir Karachi</title>

    <link rel="stylesheet" href="../css/bootstrap.css">
    <!-- CSS -->
<link rel="stylesheet" href="assets/css/bootstrap.min.css">

<!-- JS (at bottom of body) -->
<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>


    <style>
        .navbar-custom {
            background: linear-gradient(90deg, #198754, #28a745) !important;
            box-shadow: 0 4px 10px rgba(0,0,0,0.20);
        }
        .navbar-custom .navbar-brand,
        .navbar-custom .nav-link {
            color: white !important;
            font-weight: 600;
        }
        .navbar-custom .nav-link:hover {
            color: #d1ffd8 !important;
        }
        .navbar-brand small {
            display: block;
            font-size: 0.75rem;
            color: #e1f7e9;
        }
        @media (max-width: 768px) {
            .navbar-brand small {
                display: none;
            }
        }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
  <div class="container-fluid px-4">

    <a class="navbar-brand" href="<?php echo $dashboard; ?>">
        District & Sessions Court Malir Karachi
        <small>Digital Record Management System</small>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMain">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">

        <li class="nav-item">
          <a class="nav-link" href="<?php echo $dashboard; ?>">🏠 Dashboard</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="change_password.php">🔒 Change Password</a>
        </li>

        <?php if ($_SESSION['role'] === 'admin'): ?>
        <li class="nav-item">
          <a class="nav-link" href="user_create.php">👥 Manage Users</a>
        </li>
        <?php endif; ?>

        <li class="nav-item">
            <a class="nav-link text-warning fw-bold" href="logout.php">🚪 Logout</a>
        </li>

      </ul>
    </div>

  </div>
</nav>

<script src="../js/bootstrap.min.js"></script>

</body>
</html>

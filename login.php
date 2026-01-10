<?php 
session_start();
ob_start(); // Start output buffering

if (isset($_POST['login'])) {
    include "dbconn.php";
    $username = mysqli_real_escape_string($con, $_POST['uname']);
    $password = mysqli_real_escape_string($con, $_POST['pass']);

    // ✅ Fetch user with credentials
    $qry = "SELECT * FROM `users` WHERE `username` = '$username' AND `password` = '$password';";
    $run = mysqli_query($con, $qry);

    if (mysqli_num_rows($run) < 1) {
        echo "<script>alert('INVALID LOGIN!!');</script>";
    } else {
        $data = mysqli_fetch_assoc($run);

        // ✅ Store user info in session
        $_SESSION['uid'] = $data['id'];
        $_SESSION['role'] = $data['role'];
        $_SESSION['username'] = $data['username'];   // <-- Added
        $_SESSION['courtname'] = $data['courtname']; // <-- Added

        // ✅ Redirect based on role
        if ($data['role'] == 'admin') {
            header('Location: admindash.php');
        } else {
            header('Location: userdash.php');
        }
        exit();
    }
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_end_flush(); // Flush output buffering
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/bootstrap.css">
    <script src="datatable/jquery-3.7.0.js"></script>
    <title>DMS || Sessions Courts</title>
    <link rel="stylesheet" href="aos.css">
    <script src="aos.js"></script>
    <style>
        .login-card {
            background: linear-gradient(145deg, #f8f9fa, #ffffff);
            border: 1px solid #28a745;
        }
        .intro-text {
            background-color: rgba(40, 167, 69, 0.1);
            border-left: 4px solid #28a745;
            padding: 10px;
            margin-bottom: 12px;
            font-size: 0.95rem;
            color: #333;
        }
        .btn-success {
            padding: 0.5rem 1.5rem;
            font-size: 1rem;
        }
        @media (max-width: 767px) {
            .login-card {
                padding: 12px !important;
            }
            .intro-text {
                font-size: 0.85rem;
                padding: 8px;
                margin-bottom: 10px;
            }
            .btn-lg {
                width: 100% !important;
                font-size: 0.9rem;
            }
            .form-control {
                font-size: 0.85rem;
            }
            h1 {
                font-size: 1.3rem;
            }
            h2 {
                font-size: 1rem;
            }
            .form-label {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12 text-center text-primary shadow rounded mt-1" data-aos="fade-down">
                <h4 class="fw-bold text-success">District & Sessions Court Jamshorsdsdo</h4>
                <h5 class="text-success">|| Document Management System || </h5>
            </div>
        </div>
    </div>

    <div class="container-fluid text-center">
        <div class="row justify-content-center mt-4 mb-4">
            <div class="col-12 col-md-5 col-lg-3 login-card shadow rounded p-3" data-aos="zoom-in">
                <div class="intro-text" data-aos="fade-right">
                    Welcome to the Document Management System, a secure platform for managing court  Digital / Scanned || Documents  District & Sessions Court Jamshoro. Log in to access case records and administrative tools.
                </div>
                <form action="login.php" method="POST" class="form">
                    <div class="row g-3 mb-2">
                        <div class="col-12 col-md-6" data-aos="fade-right">
                            <label for="uname" class="form-label text-success fw-bold">Username</label>
                            <input type="text" name="uname" id="uname" class="form-control rounded shadow" style="border:1px solid green" placeholder="Enter Username" required aria-label="Username">
                        </div>
                        <div class="col-12 col-md-6" data-aos="fade-right">
                            <label for="pass" class="form-label text-success fw-bold">Password</label>
                            <input type="password" name="pass" id="pass" class="form-control rounded shadow" style="border:1px solid green" placeholder="Enter Password" required aria-label="Password">
                        </div>
                    </div>
                    <button class="btn btn-success btn-lg rounded shadow mt-3 w-50" name="login" type="submit" style="text-decoration:none; font-weight:bold; margin-bottom:1.5rem;" data-aos="fade-up">LOGIN</button>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 text-center" data-aos="fade-up">
            <?php include "footer.php" ?>
        </div>
    </div>

    <script src="js/bootstrap.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true
        });
    </script>
</body>
</html>

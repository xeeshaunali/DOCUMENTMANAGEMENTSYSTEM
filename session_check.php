<?php
session_start();

if (isset($_SESSION['uid'])) {
    include "dbconn.php";
    $uid = $_SESSION['uid'];
    $qry = "SELECT `role`, `username` FROM `users` WHERE `id` = '$uid';";
    $run = mysqli_query($con, $qry);
    $data = mysqli_fetch_assoc($run);

    if ($data['role'] == 'admin') {
        header('Location: admindash.php');
    } else {
        $username = $data['username'];
        header("Location: ./$username/dash.php");
    }
    exit();
}
?>

<?php
session_start();
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    $user_level = $_SESSION['level'];
    
    if ($user_level == 'kepala') {
        header('Location: kepala/dashboard2/index.php');
    } elseif ($user_level == 'admin') {
        header('Location: dashboard/index.php');
    } elseif ($user_level == 'user') {
        header('Location: pengguna/dashboard/index.php');
    }
    exit;
} else {
    header('Location: login.php');
    exit;
}

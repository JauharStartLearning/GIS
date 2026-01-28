<?php
$dbhost = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "sekolah_db";

$connection = mysqli_connect($dbhost, $dbusername, $dbpassword,  $dbname);
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}
?>

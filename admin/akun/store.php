<?php
session_start();
require_once '../helper/connection.php';

  $nama = htmlspecialchars($_POST['nama'], ENT_QUOTES, 'UTF-8');
  $username = htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8');
  $password = htmlspecialchars($_POST['password'], ENT_QUOTES, 'UTF-8');
  $level = 'admin';

  $query = "INSERT INTO tbl_user (nama, username, password, level) VALUES ('$nama', '$username', '$password', '$level')";
  if (mysqli_query($connection, $query)) {
    $_SESSION['info'] = [
      'status' => 'success',
      'message' => 'Berhasil menambah data pengembalian'
    ];
    header('Location: ./index.php');
  } else {
    $_SESSION['info'] = [
      'status' => 'failed',
      'message' => mysqli_error($connection)
    ];
    header('Location: ./index.php');
  }
?>
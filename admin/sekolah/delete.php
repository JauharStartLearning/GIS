<?php
session_start();
require_once '../helper/connection.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    $_SESSION['info'] = [
        'status' => 'failed',
        'message' => 'ID Sekolah tidak ditemukan!'
    ];
    header('Location: ./index.php');
    exit;
}

// Hapus data sekolah berdasarkan ID
$result = mysqli_query($connection, "DELETE FROM sekolah WHERE id='$id'");

if (mysqli_affected_rows($connection) > 0) {
    $_SESSION['info'] = [
        'status' => 'success',
        'message' => 'Berhasil menghapus data!'
    ];
} else {
    $_SESSION['info'] = [
        'status' => 'failed',
        'message' => 'Gagal menghapus data: ' . mysqli_error($connection)
    ];
}

header('Location: ./index.php');
exit;
?>

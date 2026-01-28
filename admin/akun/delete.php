<?php
session_start();
require_once '../helper/connection.php';

// Cek apakah id_user ada dan tidak kosong
if (isset($_GET['id_user']) && !empty($_GET['id_user'])) {
    $id_user = mysqli_real_escape_string($connection, $_GET['id_user']); // Sanitasi input
    
    // Eksekusi query DELETE
    $result = mysqli_query($connection, "DELETE FROM tbl_user WHERE id_user='$id_user'");
    
    // Cek apakah ada baris yang terpengaruh (data berhasil dihapus)
    if (mysqli_affected_rows($connection) > 0) {
        $_SESSION['info'] = [
            'status' => 'success',
            'message' => 'Berhasil menghapus data'
        ];
    } else {
        // Jika tidak ada data yang dihapus, mungkin data tidak ditemukan
        $_SESSION['info'] = [
            'status' => 'failed',
            'message' => 'Gagal menghapus data atau data tidak ditemukan'
        ];
    }
} else {
    // Jika id_user tidak ada atau kosong
    $_SESSION['info'] = [
        'status' => 'failed',
        'message' => 'ID user tidak valid'
    ];
}

// Redirect ke halaman index setelah proses
header('Location: ./index.php');
exit();
?>

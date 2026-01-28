<?php
session_start();
require_once '../helper/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = mysqli_real_escape_string($connection, htmlspecialchars($_POST['nama'], ENT_QUOTES, 'UTF-8'));
    $telepon = mysqli_real_escape_string($connection, htmlspecialchars($_POST['telepon'], ENT_QUOTES, 'UTF-8'));
    $latitude = mysqli_real_escape_string($connection, htmlspecialchars($_POST['latitude'], ENT_QUOTES, 'UTF-8'));
    $longitude = mysqli_real_escape_string($connection, htmlspecialchars($_POST['longitude'], ENT_QUOTES, 'UTF-8'));

    // Cek apakah file foto diunggah
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
        $file_name = $_FILES['foto']['name'];
        $file_tmp = $_FILES['foto']['tmp_name'];
        $file_size = $_FILES['foto']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png'];

        // Validasi ekstensi file
        if (in_array($file_ext, $allowed_ext)) {
            // Tentukan path penyimpanan
            $upload_dir = "../../uploads/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true); // Buat folder jika belum ada
            }

            // Cek dan tambahkan prefix unik berdasarkan waktu untuk nama file baru
            $new_file_name = time() . "_" . uniqid() . "." . $file_ext;
            $upload_path = $upload_dir . $new_file_name;

            // Pindahkan file ke folder tujuan
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $foto_path = "uploads/" . $new_file_name; // Simpan path relatif untuk database
            } else {
                $_SESSION['info'] = [
                    'status' => 'failed',
                    'message' => 'Gagal mengupload foto.'
                ];
                header('Location: ./index.php');
                exit;
            }
        } else {
            $_SESSION['info'] = [
                'status' => 'failed',
                'message' => 'Hanya file JPG, JPEG, dan PNG yang diizinkan.'
            ];
            header('Location: ./index.php');
            exit;
        }
    } else {
        $foto_path = null; // Tidak ada file foto
    }

    // Simpan data ke database
    $query = "INSERT INTO sekolah (nama_sekolah, telepon, latitude, longitude, foto) VALUES ('$nama', '$telepon', '$latitude', '$longitude', '$foto_path')";
    if (mysqli_query($connection, $query)) {
        $_SESSION['info'] = [
            'status' => 'success',
            'message' => 'Data berhasil disimpan!'
        ];
    } else {
        $_SESSION['info'] = [
            'status' => 'failed',
            'message' => mysqli_error($connection)
        ];
    }
    header('Location: ./index.php');
    exit;
}
?>

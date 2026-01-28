<?php
// Set header untuk response JSON
header("Content-Type: application/json");

// Include file koneksi database
include "db.php";

// Base URL ke folder project Anda
$base_url = "../web_gis_sekolah_revisi/";

// Query untuk mengambil semua data sekolah
$sql = "SELECT id, nama_sekolah, telepon, latitude, longitude, foto FROM sekolah";
$result = mysqli_query($conn, $sql);

// Cek jika query gagal
if (!$result) {
    $error_message = "Terjadi kesalahan pada query: " . mysqli_error($conn);
    error_log($error_message);
    echo json_encode(["error" => $error_message]);
    exit;
}

// Menyiapkan array untuk menyimpan data sekolah
$data = array();

// Loop melalui hasil query dan format data
while ($row = mysqli_fetch_assoc($result)) {
    // Tambahkan base URL ke path foto jika foto ada
    if (!empty($row['foto'])) {
        if (strpos($row['foto'], 'uploads/') === 0) {
            $row['foto'] = $base_url . $row['foto'];
        } else {
            $row['foto'] = $base_url . "uploads/" . $row['foto'];
        }
    } else {
        $row['foto'] = null; // Jika tidak ada foto
    }
    $data[] = $row;
}

// Menyusun data untuk respons JSON
$response = [
    'data' => $data // Semua data sekolah
];

// Debugging: Catat data yang akan dikembalikan
error_log("Data yang akan dikembalikan: " . json_encode($response));

// Output data dalam format JSON
echo json_encode($response, JSON_PRETTY_PRINT);

// Tutup koneksi database
mysqli_close($conn);
?>
<?php
require_once 'helper/connection.php';
require_once 'helper/auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $inputData = json_decode(file_get_contents('php://input'), true);
    $user_id = $inputData['user_id'] ?? null;
    $face_image_base64 = $inputData['face_image'] ?? null;

    if (!$user_id || !$face_image_base64) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        exit;
    }

    // Dekode gambar dari Base64 dan simpan ke folder temp
    $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $face_image_base64));
    $imagePath = 'temp/' . $user_id . '.jpg';

    if (!file_exists('temp')) {
        mkdir('temp', 0777, true);
    }

    if (!file_put_contents($imagePath, $imageData)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save image']);
        exit;
    }

    // Jalankan perintah Python untuk verifikasi wajah
    $pythonCommand = escapeshellcmd("python face_test.py " . escapeshellarg($user_id) . " " . escapeshellarg($imagePath));
    exec($pythonCommand, $output, $return_var);

    if ($return_var === 0) {
        $_SESSION['login'] = $_SESSION['login_temp'];
        unset($_SESSION['login_temp']);
        echo json_encode(['success' => true, 'message' => 'Face verified successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => implode("\n", $output)]);
    }

    // Hapus file gambar dari folder temp setelah proses selesai
    if (file_exists($imagePath)) {
        unlink($imagePath);
    }
}
?>
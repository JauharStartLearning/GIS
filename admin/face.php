<?php
require_once 'helper/connection.php';
require_once 'helper/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Face Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 20px;
            background-color: #f9f9f9;
        }
        video {
            border: 2px solid #333;
            border-radius: 10px;
            margin: 20px 0;
        }
        #cameraSelect {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>Face Verification</h1>

    <!-- Dropdown untuk memilih kamera -->
    <select id="cameraSelect"></select>

    <!-- Webcam video element -->
    <video id="videoElement" width="640" height="480" autoplay playsinline></video>

    <script>
        const video = document.getElementById('videoElement');
        const cameraSelect = document.getElementById('cameraSelect');

        let currentStream;

        // Fungsi untuk memulai kamera
        async function startCamera(deviceId = null) {
            if (currentStream) {
                currentStream.getTracks().forEach(track => track.stop());
            }

            const constraints = {
                video: deviceId ? { deviceId: { exact: deviceId } } : true
            };

            try {
                const stream = await navigator.mediaDevices.getUserMedia(constraints);
                video.srcObject = stream;
                currentStream = stream;

                // Tangkap gambar otomatis setelah beberapa detik
                setTimeout(captureImage, 3000);
            } catch (err) {
                console.error("Error accessing webcam: ", err);
                alert('Error accessing the webcam: ' + err.message);
            }
        }

        // Fungsi untuk mendapatkan daftar kamera
        async function getCameras() {
            try {
                const devices = await navigator.mediaDevices.enumerateDevices();
                const videoDevices = devices.filter(device => device.kind === 'videoinput');

                cameraSelect.innerHTML = '';
                videoDevices.forEach((device, index) => {
                    const option = document.createElement('option');
                    option.value = device.deviceId;
                    option.text = device.label || Camera ${index + 1};
                    cameraSelect.appendChild(option);
                });

                // Pilih kamera pertama secara default
                if (videoDevices.length > 0) {
                    startCamera(videoDevices[0].deviceId);
                }
            } catch (err) {
                console.error("Error getting cameras: ", err);
            }
        }

        // Fungsi untuk menangkap gambar dari webcam
        function captureImage() {
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);

            const dataURL = canvas.toDataURL('image/jpeg');
            verifyFace(dataURL);
        }

        // Fungsi untuk mengirimkan gambar ke server untuk verifikasi
        function verifyFace(dataURL) {
            const userId = '<?php echo isset($_SESSION['login_temp']['id_user']) ? $_SESSION['login_temp']['id_user'] : ""; ?>';

            if (!userId) {
                alert('User not logged in or session expired');
                return;
            }

            const data = { face_image: dataURL, user_id: userId };

            fetch('verify_face.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Face verified successfully!');
                    window.location.href = 'dashboard/index.php'; // Redirect jika berhasil
                } else {
                    alert('Face verification failed: ' + data.message);
                    window.location.href = 'logout.php'; // Redirect jika berhasil
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error during verification: ' + error.message);
                window.location.href = 'logout.php'; // Redirect jika berhasil
            });
        }

        // Event listener untuk memilih kamera
        cameraSelect.addEventListener('change', (e) => {
            startCamera(e.target.value);
        });

        // Jalankan saat halaman dimuat
        getCameras();
    </script>
</body>
</html>
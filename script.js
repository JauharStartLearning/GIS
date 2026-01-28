// Cek apakah Geolocation API didukung
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition((position) => {
        const userLat = position.coords.latitude;
        const userLon = position.coords.longitude;

        // Tampilkan lokasi di konsol
        console.log(`User 's location: Latitude: ${userLat}, Longitude: ${userLon}`);

        // Tampilkan lokasi di halaman web
        document.getElementById('location').textContent = `Latitude: ${userLat}, Longitude: ${userLon}`;
    }, (error) => {
        console.error("Error getting location:", error);
        document.getElementById('location').textContent = "Unable to retrieve your location.";
    });
} else {
    alert("Geolocation is not supported by this browser.");
    document.getElementById('location').textContent = "Geolocation is not supported by this browser.";
}
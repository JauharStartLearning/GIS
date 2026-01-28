document.addEventListener("DOMContentLoaded", function () {
    const tableBody = document.getElementById("schoolData");
    const searchInput = document.getElementById("searchInput");
    const entriesSelect = document.getElementById("entries");
    const paginationWrapper = document.getElementById("pagination");
    const entriesInfo = document.getElementById("entriesInfo");
    const hamburgerBtn = document.getElementById("hamburgerBtn");
    const navbarMenu = document.querySelector(".navbar-menu");
    let allSchoolData = [];
    let filteredData = [];
    let currentPage = 1;
    let entriesPerPage = 10;
    let userLat, userLon; // Tambahkan variabel global

    // Fetch data and initialize
    function fetchData() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(async (position) => {
                userLat = position.coords.latitude;
                userLon = position.coords.longitude;

                console.log(`User 's location: ${userLat}, ${userLon}`); // Debugging: Tampilkan lokasi pengguna

                try {
                    const response = await fetch("get_data.php");
                    const data = await response.json();

                    // Debugging: Tampilkan data yang diterima
                    console.log("Data received from server:", data);

                    // Pastikan data yang diterima adalah array
                    if (!Array.isArray(data.data)) {
                        throw new Error("Data yang diterima bukan array");
                    }

                    // Hitung jarak menggunakan API
                    // 1. TAMPILKAN DATA DULU
                    allSchoolData = data.data.map(item => ({
                        ...item,
                        distance: 'Loading...'
                    }));

                    filteredData = allSchoolData;
                    updatePagination();
                    displayData();

                    // 2. HITUNG JARAK DI BACKGROUND
                    calculateDistances(userLat, userLon, data.data)
                        .then(distances => {
                            distances.forEach((d, index) => {
                                if (allSchoolData[index]) {
                                    allSchoolData[index].distance =
                                        d && d.distance ? d.distance + ' km' : 'Tidak ada rute';
                                }
                            });

                            displayData(); // refresh tabel
                        })
                        .catch(() => {
                            allSchoolData.forEach(item => {
                                item.distance = 'Gagal hitung';
                            });
                            displayData();
                        });

                } catch (error) {
                    console.error("Error fetching data:", error);
                }
            }, (error) => {
                console.error("Error getting location:", error);
                alert("Gagal mendapatkan lokasi pengguna.");
            });
        } else {
            alert("Geolocation tidak didukung di browser ini.");
        }
    }

    // Toggle navbar menu for mobile view
    hamburgerBtn.addEventListener("click", () => {
        navbarMenu.classList.toggle("active");
    });

    // Display data in the table
    function displayData() {
        tableBody.innerHTML = "";
        const startIndex = (currentPage - 1) * entriesPerPage;
        const endIndex = Math.min(startIndex + entriesPerPage, filteredData.length);
    
        filteredData.slice(startIndex, endIndex).forEach((item, index) => {
            const photoCell = item.foto
                ? `<img src="${item.foto}" alt="Foto Lokasi" style="width: 100px; height: 100px; object-fit: cover;">`
                : "Foto tidak tersedia";
    
            const row = `
                <tr>
                    <td>${startIndex + index + 1}</td>
                    <td>${item.nama_sekolah}</td>
                    <td>${item.telepon || "-"}</td>
                    <td>
                        <button class="route-btn"
                            data-lat="${item.latitude}"
                            data-lon="${item.longitude}">
                            Lihat Rute
                        </button>
                    </td>
                    <td>
                        ${item.distance === 'Loading...'
                            ? '<span class="loading">Loading...</span>'
                            : item.distance}
                    </td>
                    <td>${photoCell}</td>
                </tr>
            `;
            tableBody.insertAdjacentHTML("beforeend", row);
        });
    
        // Tambahkan event listener ke tombol yang baru dibuat
        document.querySelectorAll(".route-btn").forEach(btn => {
            btn.addEventListener("click", function () {
                const lat = this.getAttribute("data-lat");
                const lon = this.getAttribute("data-lon");

                const index = this.closest("tr").rowIndex - 1;
                const school = filteredData[startIndex + index];
                openRoute(lat, lon, school.nama_sekolah, school.distance);
            });
        });
    
        updateEntriesInfo(startIndex + 1, endIndex, filteredData.length);
    }

    // Calculate distance using cari_jarak.py
    function calculateDistances(userLat, userLon, schools) {
        // Pastikan userLat dan userLon tidak null
        if (userLat == null || userLon == null) {
            console.error("User  latitude or longitude is null");
            return Promise.resolve([]); // Kembalikan array kosong jika tidak valid
        }

        // Debugging: Tampilkan data yang akan dikirim ke server
        console.log("Data to be sent for distance calculation:", {
            user_lat: userLat,
            user_lon: userLon,
            schools: schools
        });

        return fetch('http://localhost:5000/calculate_distance', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_lat: userLat,
                user_lon: userLon,
                schools: schools
            }),
        })
        .then((response) => {
            if (!response.ok) {
                throw new Error('Failed to calculate distances');
            }
            return response.json();
        })
        .then((data) => {
            console.log("Distances calculated:", data); // Debugging: Tampilkan jarak yang dihitung
            return data; // Return the distances data
        })
        .catch((error) => {
            console.error('Error:', error);
            return [];
        });
    }

    function openRoute(destLat, destLon, namaSekolah, jarak) {
        if (userLat !== undefined && userLon !== undefined) {

            const url =
                `http://localhost:5000/lihat_rute` +
                `?user_lat=${userLat}` +
                `&user_lon=${userLon}` +
                `&school_lat=${destLat}` +
                `&school_lon=${destLon}` +
                `&school_name=${encodeURIComponent(namaSekolah)}` +
                `&distance=${jarak}`;

            window.open(url, "_blank"); // buka TAB BARU

        } else {
            alert("Lokasi pengguna belum tersedia.");
        }
    }



    // Handle search functionality
    function searchSchool() {
        const searchText = searchInput.value.toLowerCase();
        filteredData = allSchoolData.filter(item =>
            item.nama_sekolah.toLowerCase().includes(searchText)
        );
        currentPage = 1;
        updatePagination();
        displayData();
    }

    // Update pagination buttons
    function updatePagination() {
        paginationWrapper.innerHTML = "";
        const totalPages = Math.ceil(filteredData.length / entriesPerPage);

        if (currentPage > 1) {
            paginationWrapper.innerHTML += `<button class="page-btn" data-page="${currentPage - 1}">Previous</button>`;
        }

        const maxVisiblePages = 5; // Maximum number of visible page buttons
        const startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        const endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

        if (startPage > 1) {
            paginationWrapper.innerHTML += `<button class="page-btn" data-page="1">1</button>`;
            if (startPage > 2) {
                paginationWrapper.innerHTML += `<span class="ellipsis">...</span>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            paginationWrapper.innerHTML += `<button class="page-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationWrapper.innerHTML += `<span class="ellipsis">...</span>`;
            }
            paginationWrapper.innerHTML += `<button class="page-btn" data-page="${totalPages}">${totalPages}</button>`;
        }

        if (currentPage < totalPages) {
            paginationWrapper.innerHTML += `<button class="page-btn" data-page="${currentPage + 1}">Next</button>`;
        }

        document.querySelectorAll(".page-btn").forEach(btn => {
            btn.addEventListener("click", function () {
                currentPage = parseInt(this.dataset.page);
                displayData();
                updatePagination();
            });
        });
    }

    // Update the "Showing X to Y of Z entries" text
    function updateEntriesInfo(start, end, total) {
        entriesInfo.textContent = `Showing ${start} to ${end} of ${total} entries`;
    }

    // Handle entries dropdown change
    entriesSelect.addEventListener("change", function () {
        entriesPerPage = parseInt(this.value);
        currentPage = 1;
        updatePagination();
        displayData();
    });

    // Event listener for search input
    searchInput.addEventListener("input", searchSchool);

    fetchData();
});
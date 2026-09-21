<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

// Array nama hari
$nama_hari = array(
  'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'
);

// Ambil hari dan tanggal
$hari_ini = $nama_hari[date('w')];
$tanggal = date('d') . ' ' . date('F') . ' ' . date('Y');
$tanggal = str_replace(
  ['January','February','March','April','May','June','July','August','September','October','November','December'],
  ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'],
  $tanggal
);
include '../admin/config.php'; // Sesuaikan path jika berbeda
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Absensi QR Siswa & Guru - Sistem Presensi MIDU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        body {
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .container {
            max-width: 1024px;
            margin: 0 auto;
            padding: 20px;
        }
        h2 {
            color: #2c6e49;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }
        #preview {
            width: 100%;
            max-width: 400px;
            height: 300px;
            border: 2px solid #2c6e49;
            border-radius: 8px;
            position: relative;
            overflow: hidden;
            margin: 0 auto;
        }
        #preview::before {
            content: 'Sedang memindai ...';
            position: absolute;
            top: 0px;
            left: 118px;
            color: #f8fcfaff;
            font-weight: light;
            background: rgba(12, 12, 12, 0.8);
            padding: 5px 10px;
            border-radius: 5px;
        }
        #preview.scanning::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 60%;
            height: 60%;
            border: 2px dashed #28a745;
            transform: translate(-50%, -50%);
        }
        #foto {
            width: 100px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        .result-card {
            border: 2px solid #28a745;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            background: #fff;
            animation: fadeIn 0.5s ease-in-out;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .result-card.notfound {
            border-color: #dc3545;
        }
        #clock {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2c6e49;
        }
        .loading {
            display: none;
            color: #2c6e49;
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
        }
        #manualInput {
            max-width: 400px;
            margin: 0 auto;
        }
        .form-text {
            color: #3d8b5e;
        }
        #info-jadwal .badge {
            font-size: 1rem;
            padding: 8px 12px;
        }
        #audio-error-message {
            display: none;
            color: #dc3545;
            text-align: center;
            margin-top: 10px;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        @media (max-width: 576px) {
            #preview {
                max-width: 100%;
                height: 250px;
            }
            h2 img {
                width: 80px;
            }
        }
    </style>
</head>
<body>
    <div class="container py-4">
      <div class="row">
        <div class="col-md-1 text-center" style="margin-left: 30px;">
            <img src="../assets/img/logo.png" width="85" class="me-2">
          
        </div>
        <div class="col-md-10">
          <h2 class="mb-3">
               SISTEM PRESENSI QRCODE MIDU GROGOL
          </h2>
        </div>
      </div>
        <div class="text-center mb-2">
            <div class="text-center">
                <strong><?= $hari_ini . ', ' . $tanggal; ?></strong>
            </div>
            <div id="clock" class="text-primary"></div>
        </div>
        <div class="text-center mb-3" id="info-jadwal">
            <span class="badge bg-info">Mengambil info jadwal hari ini...</span>
        </div>

        <!-- Webcam Preview -->
        <div class="d-flex justify-content-center mb-3">
            <div id="preview" class="scanning"></div>
        </div>
        <div class="text-center mb-3">
            <span id="camera-error" class="text-danger" style="display: none;">Gagal mengakses kamera. Silakan gunakan input manual.</span>
        </div>

        <!-- Input Manual QR Code -->
        <div class="mb-4 text-center">
            <form id="formManual" class="d-inline-block">
                <input type="text" id="manualInput" class="form-control text-center" placeholder="Scan atau ketik kode QR" autofocus>
                <div class="form-text">Gunakan input manual jika kamera tidak mendeteksi</div>
            </form>
        </div>

        <!-- Test Audio Button -->
        <div class="text-center mb-3">
            <button id="test-audio" class="btn btn-outline-success btn-sm" style="display: none;">Uji Audio</button>
        </div>

        <!-- Audio Error Message -->
        <div id="audio-error-message">Gagal memutar audio. Periksa file audio atau pengaturan browser.</div>

        <!-- Loading Indicator -->
        <div id="loading" class="loading">Memproses...</div>

        <!-- Hasil Scan -->
        <div id="result" class="my-4"></div>
        <h5>Riwayat Presensi Terakhir:</h5>
        <ul id="history" class="list-group"></ul>
    </div>

    <!-- Audio Feedback -->
    <audio id="audio-success" src="../assets/audio/success.mp3" preload="auto"></audio>
    <audio id="audio-duplicate" src="../assets/audio/duplicate.mp3" preload="auto"></audio>
    <audio id="audio-error" src="../assets/audio/error.mp3" preload="auto"></audio>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const resultDiv = document.getElementById('result');
        const historyList = document.getElementById('history');
        const infoJadwal = document.getElementById('info-jadwal');
        const clock = document.getElementById('clock');
        const preview = document.getElementById('preview');
        const cameraError = document.getElementById('camera-error');
        const loading = document.getElementById('loading');
        const audioErrorMessage = document.getElementById('audio-error-message');

        let lokasi = '';
        let alamat = '';
        let delay = false;
        let lastScan = '';

        // Fungsi untuk membersihkan HTML dari teks
        function stripHtml(text) {
            const div = document.createElement('div');
            div.innerHTML = text;
            return div.textContent || div.innerText || '';
        }

        // Jam real-time
        setInterval(() => {
            const now = new Date();
            clock.textContent = now.toLocaleTimeString('id-ID', { hour12: false });
        }, 1000);

        // Ambil jadwal hari ini
        fetch('jadwal.php')
            .then(res => res.json())
            .then(data => {
                if (data.jam_masuk) {
                    infoJadwal.innerHTML = `
                        <span class="badge bg-success me-2">Masuk: ${data.jam_masuk}</span>
                        <span class="badge bg-danger">Pulang: ${data.jam_pulang}</span>`;
                } else {
                    infoJadwal.innerHTML = `<span class="badge bg-warning">Tidak ada jadwal hari ini</span>`;
                }
            })
            .catch(() => {
                infoJadwal.innerHTML = `<span class="badge bg-danger">Gagal mengambil jadwal</span>`;
            });

        // Deteksi lokasi + reverse geocoding
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(pos => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                lokasi = `${lat},${lng}`;
                fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`)
                    .then(res => res.json())
                    .then(data => {
                        alamat = data.display_name || 'Alamat tidak tersedia';
                    })
                    .catch(() => {
                        alamat = 'Gagal mengambil alamat';
                    });
            }, () => {
                lokasi = '';
                alamat = 'Lokasi tidak tersedia';
            });
        }

        function tampilkanData(res) {
            const kelasJabatan = res.tingkat_rombel ? `Kelas: ${res.tingkat_rombel}` : (res.jabatan ? `Jabatan: ${res.jabatan}` : '');
            const card = `
                <div class="result-card ${res.status === 'notfound' ? 'notfound' : ''}">
                    <div class="row align-items-center">
                        <div class="col-md-2 col-4">
                            <img src="../admin/uploads/${res.foto}" alt="foto" class="img-fluid rounded" style="max-width: 100px;">
                        </div>
                        <div class="col">
                            <h5 class="mb-0">${res.nama || 'Tidak ditemukan'}</h5>
                            <div>${kelasJabatan}</div>
                            <div>${res.pesan}</div>
                        </div>
                    </div>
                </div>
            `;
            resultDiv.innerHTML = card;
            if (res.nama) {
                historyList.insertAdjacentHTML('afterbegin', `<li class="list-group-item">${res.nama} - ${res.pesanb}</li>`);
            }
            Swal.fire({
                icon: res.status === 'success' ? 'success' : (res.status === 'already' ? 'warning' : 'error'),
                title: res.status === 'success' ? 'Berhasil!' : (res.status === 'already' ? 'Sudah Absen!' : 'Gagal!'),
                text: stripHtml(res.pesan),
                showConfirmButton: false,
                timer: 500,
                position: 'top-end',
                toast: true
            });
        }

        function playAudio(audioElement) {
            const playPromise = audioElement.play();
            if (playPromise !== undefined) {
                playPromise.catch(error => {
                    console.error('Gagal memutar audio:', error);
                    audioErrorMessage.style.display = 'block';
                });
            }
        }

        function prosesAbsen(kode) {
            if (!kode || delay || kode === lastScan) return;
            delay = true;
            lastScan = kode;
            loading.style.display = 'block';
            preview.classList.remove('scanning');

            fetch('proses_absen.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `qrcode=${encodeURIComponent(kode)}&lokasi=${encodeURIComponent(lokasi)}&alamat=${encodeURIComponent(alamat)}`
            })
            .then(response => {
                if (!response.ok) throw new Error('Koneksi ke server gagal');
                return response.json();
            })
            .then(res => {
                tampilkanData(res);
                const audio = res.status === 'success' ? document.getElementById('audio-success') :
                             res.status === 'already' ? document.getElementById('audio-duplicate') :
                             document.getElementById('audio-error');
                playAudio(audio);
                setTimeout(() => {
                    delay = false;
                    lastScan = '';
                    loading.style.display = 'none';
                    preview.classList.add('scanning');
                    audioErrorMessage.style.display = 'none';
                }, 1000);
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Terjadi kesalahan: ' + err.message,
                    showConfirmButton: false,
                    timer: 1000,
                    position: 'top-end',
                    toast: true
                });
                playAudio(document.getElementById('audio-error'));
                loading.style.display = 'none';
                preview.classList.add('scanning');
                audioErrorMessage.style.display = 'block';
                delay = false;
            });
        }

        // QR Webcam
        const html5QrCode = new Html5Qrcode("preview");
        Html5Qrcode.getCameras()
            .then(cameras => {
                if (cameras.length) {
                    const cameraId = cameras[cameras.length - 1].id; // Pilih kamera terakhir (biasanya belakang)
                    html5QrCode.start(
                        cameraId,
                        { fps: 20, qrbox: { width: 250, height: 250 } },
                        decodedText => {
                            document.getElementById('manualInput').value = decodedText;
                            prosesAbsen(decodedText);
                        },
                        error => {
                            // Tidak menampilkan error setiap frame
                        }
                    ).catch(err => {
                        cameraError.style.display = 'block';
                        console.error('Gagal memulai kamera:', err);
                    });
                } else {
                    cameraError.style.display = 'block';
                }
            })
            .catch(err => {
                cameraError.style.display = 'block';
                console.error('Gagal mengakses kamera:', err);
            });

        // Input manual handler
        document.getElementById('formManual').addEventListener('submit', function(e) {
            e.preventDefault();
            const kode = document.getElementById('manualInput').value.trim();
            if (kode) {
                prosesAbsen(kode);
                document.getElementById('manualInput').value = '';
            }
        });

        // Auto-submit QR manual input tanpa Enter
        let inputTimeout;
        document.getElementById('manualInput').addEventListener('input', function() {
            const kode = this.value.trim();
            if (kode.length >= 7) {
                clearTimeout(inputTimeout);
                inputTimeout = setTimeout(() => {
                    prosesAbsen(kode);
                    this.value = '';
                }, 500);
            }
        });

        // Test audio button
        document.getElementById('test-audio').addEventListener('click', function() {
            audioErrorMessage.style.display = 'none';
            const audio = document.getElementById('audio-success');
            playAudio(audio);
        });
    </script>
</body>
</html>

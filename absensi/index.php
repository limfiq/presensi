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

// Count Students and Teachers Not Yet Present
$current_date = date('Y-m-d');
$sql_belum_presensi = "
  SELECT 'siswa' AS jenis_pengguna, s.id
  FROM siswa s
  LEFT JOIN presensi p ON s.id = p.id_siswa AND p.tanggal = ?
  LEFT JOIN ketidakhadiran k ON s.id = k.id_siswa AND k.tanggal = ?
  WHERE p.id IS NULL AND k.id IS NULL
  UNION
  SELECT 'guru' AS jenis_pengguna, g.id
  FROM guru g
  LEFT JOIN presensi p ON g.id = p.id_guru AND p.tanggal = ?
  LEFT JOIN ketidakhadiran k ON g.id = k.id_guru AND k.tanggal = ?
  WHERE p.id IS NULL AND k.id IS NULL";
$stmt = $conn->prepare($sql_belum_presensi);
$stmt->bind_param("ssss", $current_date, $current_date, $current_date, $current_date);
$stmt->execute();
$belum_presensi = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Count siswa and guru separately
$belum_presensi_siswa = 0;
$belum_presensi_guru = 0;
foreach ($belum_presensi as $row) {
  if ($row['jenis_pengguna'] === 'siswa') {
    $belum_presensi_siswa++;
  } else {
    $belum_presensi_guru++;
  }
}
$stmt->close();
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
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        h3 {
            color: #2c6e49;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }
        #preview {
            width: 100%;
            max-width: 400px;
            height: 375px;
            border: 3px solid #2c6e49;
            border-radius: 12px;
            position: relative;
            overflow: hidden;
            margin: 0 auto;
            background: #000;
        }
        #preview::before {
            content: 'Sedang Memindai...';
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            color: #fff;
            font-weight: 500;
            background: rgba(44, 110, 73, 0.8);
            padding: 5px 15px;
            border-radius: 5px;
            z-index: 10;
        }
        #preview.scanning::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 70%;
            height: 70%;
            border: 3px dashed #28a745;
            transform: translate(-50%, -50%);
            animation: pulse 2s infinite;
        }
        .history-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            background: #fff;
            transition: transform 0.2s ease;
        }
        .history-card-masuk {
            border-color: #28a745;
        }
        .history-card-pulang {
            border-color: #dc3545;
        }
        .history-card-sudah {
            border-color: #ffc107;
        }
        .history-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .history-card img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        #history {
            max-height: 500px;
            overflow-y: auto;
            padding-right: 10px;
        }
        #history::-webkit-scrollbar {
            width: 8px;
        }
        #history::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        #history::-webkit-scrollbar-thumb {
            background: #2c6e49;
            border-radius: 4px;
        }
        #history::-webkit-scrollbar-thumb:hover {
            background: #3d8b5e;
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
            font-weight: bold;
        }
        .form-control {
            border-radius: 8px;
            padding: 10px;
            transition: border-color 0.3s ease;
        }
        .form-control:focus {
            border-color: #2c6e49;
            box-shadow: 0 0 5px rgba(44, 110, 73, 0.3);
        }
        .form-text {
            color: #3d8b5e;
        }
        #info-jadwal .badge {
            font-size: 1rem;
            padding: 8px 12px;
            background-color: #2c6e49;
            color: white;
        }
        #info-jadwal .badge.bg-danger {
            background-color: #dc3545;
        }
        .badge-belum-presensi {
            cursor: pointer;
            font-size: 1rem;
            padding: 10px 15px;
        }
        #camera-error {
            color: #dc3545;
            text-align: center;
            margin-top: 10px;
        }
        #audio-error-message {
            display: none;
            color: #dc3545;
            text-align: center;
            margin-top: 10px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
            font-size: 0.9rem;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        @keyframes pulse {
            0% { border-color: #28a745; }
            50% { border-color: #3d8b5e; }
            100% { border-color: #28a745; }
        }
        @media (max-width: 768px) {
            #preview {
                max-width: 100%;
                height: 250px;
            }
            h3 img {
                width: 80px;
            }
            .history-card img {
                width: 50px;
                height: 50px;
            }
            #history {
                max-height: 400px;
            }
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row mb-4 align-items-center">
            <div class="col-md-1 text-center">
                <img src="../assets/img/logo.png" width="85" class="me-2">
            </div>
            <div class="col-md-10 text-center">
                <h3>SISTEM PRESENSI QRCODE MIDU GROGOL</h3>
                <div>
                    <strong><?= $hari_ini . ', ' . $tanggal; ?></strong>
                </div>
                <div id="clock" class="text-primary"></div>
            </div>
        </div>

        <div class="text-center mb-2" id="info-jadwal">
            <span class="badge bg-info">Mengambil info jadwal hari ini...</span>
        </div>

        <!-- Belum Presensi Info -->
        <div class="text-center mb-3" id="belum-presensi-info">
            <a href="../admin/home.php#belumPresensi" class="badge badge-belum-presensi bg-warning text-dark">
                <i class="fas fa-exclamation-circle me-2"></i>
                Belum Presensi: <?php echo $belum_presensi_siswa; ?> Siswa, <?php echo $belum_presensi_guru; ?> Guru
            </a>
        </div>

        <div class="row g-3">
            <!-- Kolom Kiri: Webcam dan Input Manual -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-transparent text-center">
                        <h5 class="mb-0">Pindai QR Code</h5>
                    </div>
                    <div class="card-body text-center">
                        <div id="preview" class="scanning mb-3"></div>
                        <div id="camera-error" style="display: none;">Gagal mengakses kamera. Silakan gunakan input manual.</div>
                        <form id="formManual" class="mt-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-qrcode"></i></span>
                                <input type="text" id="manualInput" class="form-control text-center" placeholder="Scan atau ketik kode QR" autofocus>
                            </div>
                            <div class="form-text mt-2">Gunakan input manual jika kamera tidak mendeteksi</div>
                        </form>
                        <div id="loading" class="loading mt-3">Memproses...</div>
                        <div id="audio-error-message">Gagal memutar audio. Periksa file audio atau pengaturan browser.</div>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Riwayat Presensi Terbaru -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0">Riwayat Presensi Terbaru</h5>
                    </div>
                    <div class="card-body" id="history">
                        <!-- Riwayat akan diisi oleh JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p class="text-muted small">© 2025 Copyrighted MIDU Grogol</p>
    </div>

    <!-- Audio Feedback -->
    <audio id="audio-success" src="../assets/audio/berhasil.mp3" preload="auto"></audio>
    <audio id="audio-duplicate" src="../assets/audio/sudah.mp3" preload="auto"></audio>
    <audio id="audio-error" src="../assets/audio/error.mp3" preload="auto"></audio>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const historyDiv = document.getElementById('history');
        const infoJadwal = document.getElementById('info-jadwal');
        const belumPresensiInfo = document.getElementById('belum-presensi-info');
        const clock = document.getElementById('clock');
        const preview = document.getElementById('preview');
        const cameraError = document.getElementById('camera-error');
        const loading = document.getElementById('loading');
        const audioErrorMessage = document.getElementById('audio-error-message');

        let lokasi = '';
        let alamat = '';
        let delay = false;
        let lastScan = '';
        let historyData = []; // Array untuk menyimpan riwayat presensi

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

        // Ambil jadwal hari ini dan kontrol visibilitas belum presensi
        fetch('jadwal.php')
            .then(res => res.json())
            .then(data => {
                if (data.jam_masuk) {
                    infoJadwal.innerHTML = `
                        <span class="badge me-2">Masuk: ${data.jam_masuk}</span>
                        <span class="badge bg-danger">Pulang: ${data.jam_pulang}</span>`;
                    // Kontrol visibilitas badge belum presensi
                    const [hours, minutes] = data.jam_masuk.split(':').map(Number);
                    const now = new Date();
                    const jamMasukTime = new Date();
                    jamMasukTime.setHours(hours, minutes, 0, 0);
                    const bufferTime = new Date(jamMasukTime.getTime() + 15 * 60000); // Tambah 30 menit
                    if (now < bufferTime) {
                        belumPresensiInfo.style.display = 'none';
                    } else {
                        belumPresensiInfo.style.display = 'block';
                    }
                } else {
                    infoJadwal.innerHTML = `<span class="badge bg-warning">Tidak ada jadwal hari ini</span>`;
                    belumPresensiInfo.style.display = 'none';
                }
            })
            .catch(() => {
                infoJadwal.innerHTML = `<span class="badge bg-danger">Gagal mengambil jadwal</span>`;
                belumPresensiInfo.style.display = 'block'; // Tampilkan sebagai fallback
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

        // Fungsi untuk menentukan kelas berdasarkan status
        function getStatusClass(pesanb, status) {
            const pesanLower = pesanb.toLowerCase();
            if (pesanLower.includes('masuk')) {
                return 'history-card-masuk';
            } else if (pesanLower.includes('pulang')) {
                return 'history-card-pulang';
            } else if (status === 'already' || pesanLower.includes('sudah')) {
                return 'history-card-sudah';
            }
            return '';
        }

        // Fungsi untuk memperbarui riwayat presensi
        function updateHistory(res) {
            if (res.nama && res.status !== 'notfound') {
                historyData.unshift({
                    nama: res.nama,
                    foto: res.foto,
                    kelasJabatan: res.tingkat_rombel ? `Kelas: ${res.tingkat_rombel}` : (res.jabatan ? `Jabatan: ${res.jabatan}` : ''),
                    keterangan: res.pesanb,
                    statusClass: getStatusClass(res.pesanb, res.status)
                });
                // Batasi hingga 10 entri
                if (historyData.length > 10) {
                    historyData.pop();
                }
                // Render ulang riwayat
                historyDiv.innerHTML = historyData.map(item => `
                    <div class="history-card ${item.statusClass}">
                        <div class="d-flex align-items-center">
                            <img src="../admin/uploads/${item.foto}" alt="foto" class="me-3">
                            <div>
                                <h6 class="mb-1">${item.nama}</h6>
                                <small>${item.kelasJabatan}</small><br>
                                <small><strong>Keterangan:</strong> ${item.keterangan}</small>
                            </div>
                        </div>
                    </div>
                `).join('');
            }
        }

        function tampilkanData(res) {
            Swal.fire({
                icon: res.status === 'success' ? 'success' : (res.status === 'already' ? 'warning' : 'error'),
                title: res.status === 'success' ? 'Berhasil!' : (res.status === 'already' ? 'Sudah Absen!' : 'Gagal!'),
                text: stripHtml(res.pesan),
                showConfirmButton: false,
                timer: 1000,
                position: 'top-end',
                toast: true
            });
            updateHistory(res);
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
                }, 200);
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
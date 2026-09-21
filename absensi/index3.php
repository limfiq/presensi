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


?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Absensi QR Siswa & Guru</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://unpkg.com/html5-qrcode"></script>
  <style>
    body { background: #f8f9fa; }
    .highlight { animation: fadeIn 0.5s ease-in-out; }
    @keyframes fadeIn {
      from { opacity: 0; transform: scale(0.95); }
      to { opacity: 1; transform: scale(1); }
    }
    #preview {
      width: 320px;
      height: 240px;
      border: 1px solid #ccc;
      border-radius: 8px;
    }
    #foto {
      width: 150px;
      border: 1px solid #ccc;
      border-radius: 8px;
    }
    .result-card {
      border: 2px solid #198754;
      border-radius: 10px;
      padding: 15px;
      margin-bottom: 20px;
      background: #fff;
    }
    .result-card.notfound { border-color: #dc3545; }
    #clock { font-size: 1.2rem; font-weight: bold; }
  </style>
</head>
<body>
  <div class="container py-4">
    <h3 class="text-center mb-3">
      <img src="../assets/img/logo.png" width="120" class="me-2"> SISTEM PRESENSI QRCODE MIDU GROGOL
    </h3>
    <div class="text-center mb-2">
      <div class="text-center">
        <strong><?= $hari_ini . ', ' . $tanggal; ?></strong>
      </div>
      <div id="clock" class="text-success"></div>
    </div>
    <div class="text-center mb-3" id="info-jadwal">
      <span class="badge bg-info">Mengambil info jadwal hari ini...</span>
    </div>

    <!-- Webcam Preview -->
    <div class="d-flex justify-content-center mb-3">
      <div id="preview"></div>
    </div>

    <!-- Input Manual QR Code -->
    <div class="mb-4 text-center">
      <form id="formManual" class="d-inline-block">
        <input type="text" id="manualInput" class="form-control text-center" placeholder="Scan atau ketik kode QR" style="max-width:325px;" autofocus>
        <div class="form-text">Gunakan input manual jika kamera tidak mendeteksi</div>
      </form>
    </div>

    <!-- Hasil Scan -->
    <div id="result" class="my-4"></div>
    <h5>Riwayat Presensi Terakhir:</h5>
    <ul id="history" class="list-group"></ul>
  </div>

  <!-- Audio Feedback -->
  <audio id="audio-success" src="../assets/audio/success.mp3"></audio>
  <audio id="audio-duplicate" src="../assets/audio/duplicate.mp3"></audio>
  <audio id="audio-error" src="../assets/audio/error.mp3"></audio>

  <script>
    const resultDiv = document.getElementById('result');
    const historyList = document.getElementById('history');
    const infoJadwal = document.getElementById('info-jadwal');
    const clock = document.getElementById('clock');

    let lokasi = '';
    let alamat = '';
    let delay = false;
    let lastScan = '';

    // Jam real-time
    setInterval(() => {
      const now = new Date();
      clock.textContent = now.toLocaleTimeString('id-ID');
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
            alamat = data.display_name;
          });
      }, () => {
        lokasi = '';
        alamat = 'Lokasi tidak tersedia';
      });
    }

    function tampilkanData(res) {
      const kelasJabatan = res.tingkat_rombel ? `Kelas: ${res.tingkat_rombel}` : (res.jabatan ? `Jabatan: ${res.jabatan}` : '');
      const card = `
        <div class="result-card ${res.status === 'notfound' ? 'notfound' : ''} highlight">
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
    }

    function prosesAbsen(kode) {
      if (!kode || delay || kode === lastScan) return;
      delay = true;
      lastScan = kode;

      fetch('proses_absen.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `qrcode=${encodeURIComponent(kode)}&lokasi=${encodeURIComponent(lokasi)}&alamat=${encodeURIComponent(alamat)}`
      })
      .then(response => response.json())
      .then(res => {
        tampilkanData(res);
        if (res.status === 'success') {
          document.getElementById('audio-success').play();
        } else if (res.status === 'already') {
          document.getElementById('audio-duplicate').play();
        } else {
          document.getElementById('audio-error').play();
        }
        setTimeout(() => {
          delay = false;
          lastScan = '';
        }, 5000);
      })
      .catch(err => {
        document.getElementById('audio-error').play();
        resultDiv.innerHTML = `<div class="alert alert-danger">Terjadi kesalahan koneksi atau respon tidak valid.</div>`;
        delay = false;
      });
    }

    // QR Webcam
    const html5QrCode = new Html5Qrcode("preview");
    Html5Qrcode.getCameras().then(cameras => {
      if (cameras.length) {
        html5QrCode.start(
          { facingMode: "environment" },
          { fps: 10},
          decodedText => {
            document.getElementById('manualInput').value = decodedText;
            prosesAbsen(decodedText);
          },
          error => {}
        );
      }
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
  </script>
  <script>
  // Auto-submit QR manual input tanpa Enter
  let inputTimeout;

  document.getElementById('manualInput').addEventListener('input', function () {
    const kode = this.value.trim();

    if (kode.length >= 7) { // asumsi QR minimal 5 karakter
      clearTimeout(inputTimeout); // reset jika user masih mengetik atau scan lambat

      inputTimeout = setTimeout(() => {
        prosesAbsen(kode);
        this.value = ''; // kosongkan field setelah kirim
      }, 400); // delay aman
    }
  });
</script>

</body>
</html>

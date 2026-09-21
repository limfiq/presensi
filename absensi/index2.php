<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Absensi QR Siswa & Guru</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
    @media (max-width: 576px) {
      #preview {
        width: 100% !important;
        height: auto !important;
      }
    }
    .result-card {
      border: 2px solid #198754;
      border-radius: 10px;
      padding: 15px;
      margin-bottom: 20px;
      background: #fff;
    }
    .result-card.notfound {
      border-color: #dc3545;
    }
  </style>
</head>
<body>
  <div class="container py-5">
    <div class="text-center mb-3">
      <h4 id="clock" class="fw-bold"></h4>
      <h5 id="jadwal-info" class="text-muted"></h5>
    </div>
    <h2 class="text-center mb-4">
      <img src="../assets/img/logo.png" width="40" class="me-2"> Absensi QR Siswa & Guru
    </h2>

    <!-- Webcam Preview -->
    <div class="d-flex justify-content-center mb-3">
      <video id="preview" playsinline></video>
    </div>

    <!-- Input Manual QR Code -->
    <div class="mb-4 text-center">
      <form id="formManual" class="d-inline-block">
        <input type="text" id="manualInput" class="form-control text-center" placeholder="Scan atau ketik kode QR" style="max-width:300px;" autofocus>
        <div class="form-text">Gunakan input manual jika kamera tidak mendeteksi</div>
      </form>
    </div>

    <!-- Hasil Scan -->
    <div id="result" class="my-4"></div>

    <!-- Riwayat -->
    <h5>Riwayat Presensi Terakhir:</h5>
    <ul id="history" class="list-group"></ul>
  </div>

  <!-- Audio Feedback -->
  <audio id="audio-success" src="../assets/audio/success.mp3"></audio>
  <audio id="audio-duplicate" src="../assets/audio/duplicate.mp3"></audio>
  <audio id="audio-error" src="../assets/audio/error.mp3"></audio>

  <!-- JS Library -->
  <script src="https://unpkg.com/html5-qrcode"></script>
  <script>
    const resultDiv = document.getElementById('result');
    const historyList = document.getElementById('history');
    const audioSuccess = document.getElementById('audio-success');
    const audioDuplicate = document.getElementById('audio-duplicate');
    const audioError = document.getElementById('audio-error');
    let delay = false;
    let lastScan = '';
    let currentLocation = { latlng: '', alamat: '' };

    function tampilkanData(res) {
      const kelasOrJabatan = res.tingkat_rombel ? `Kelas: ${res.tingkat_rombel}` : `Jabatan: ${res.jabatan}`;
      const card = `
        <div class="result-card ${res.status === 'notfound' ? 'notfound' : ''} highlight">
          <div class="row align-items-center">
            <div class="col-md-2 col-4">
              <img src="../assets/foto/${res.foto}" alt="foto" class="img-fluid rounded">
            </div>
            <div class="col">
              <h5 class="mb-0">${res.nama || 'Tidak ditemukan'}</h5>
              <div>${kelasOrJabatan || ''}</div>
              <div><small class="text-muted">${res.pesan}</small></div>
              <div><small class="text-muted">${res.lokasi || ''}</small></div>
            </div>
          </div>
        </div>`;
      resultDiv.innerHTML = card;
      historyList.insertAdjacentHTML('afterbegin', `<li class="list-group-item">${res.nama} - ${res.pesan}</li>`);
    }

    function prosesAbsen(kode) {
      if (!kode || delay || kode === lastScan) return;
      delay = true;
      lastScan = kode;

      const data = new URLSearchParams();
      data.append('qrcode', kode);
      data.append('lokasi', currentLocation.latlng);
      data.append('alamat', currentLocation.alamat);

      fetch('proses_absen.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: data
      })
      .then(response => response.json())
      .then(res => {
        tampilkanData(res);
        if (res.status === 'success') audioSuccess.play();
        else if (res.status === 'already') audioDuplicate.play();
        else audioError.play();
        setTimeout(() => { delay = false; lastScan = ''; }, 5000);
      })
      .catch(err => {
        audioError.play();
        resultDiv.innerHTML = `<div class="alert alert-danger">Gagal terhubung ke server.</div>`;
        delay = false;
      });
    }

    document.getElementById('formManual').addEventListener('submit', function(e) {
      e.preventDefault();
      const kode = document.getElementById('manualInput').value.trim();
      if (kode) {
        prosesAbsen(kode);
        document.getElementById('manualInput').value = '';
      }
    });

    const html5QrCode = new Html5Qrcode("preview");
    Html5Qrcode.getCameras().then(cameras => {
      if (cameras.length) {
        html5QrCode.start(
          { facingMode: "environment" },
          { fps: 10, qrbox: 250 },
          decodedText => {
            document.getElementById('manualInput').value = decodedText;
            prosesAbsen(decodedText);
          }, error => {}
        );
      }
    }).catch(err => {
      resultDiv.innerHTML = `<div class="alert alert-danger">Kamera tidak tersedia: ${err}</div>`;
    });

    // Jam real-time
    setInterval(() => {
      const now = new Date();
      const jam = now.toLocaleTimeString();
      document.getElementById('clock').textContent = jam;
    }, 1000);

    // Ambil jadwal hari ini
    fetch('jadwal.php')
      .then(res => res.json())
      .then(data => {
        document.getElementById('jadwal-info').textContent =
          `Hari ini: Masuk ${data.jam_masuk || '-'} - Pulang ${data.jam_pulang || '-'}`;
      });

    // Lokasi (lat-long + alamat)
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(pos => {
        const lat = pos.coords.latitude;
        const lon = pos.coords.longitude;
        currentLocation.latlng = `${lat},${lon}`;

        // Reverse geocoding (menggunakan Nominatim)
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`)
          .then(res => res.json())
          .then(data => {
            currentLocation.alamat = data.display_name;
          })
          .catch(() => {
            currentLocation.alamat = "(Alamat tidak ditemukan)";
          });
      }, () => {
        currentLocation.latlng = "(Lokasi tidak tersedia)";
        currentLocation.alamat = "(Lokasi tidak tersedia)";
      });
    }
  </script>
</body>
</html>

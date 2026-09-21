<?php
// index.php - Halaman Absensi QRCode (Desain Lama + Sistem Baru)
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Absensi QR Siswa & Guru</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

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
      #preview { width: 100% !important; height: auto !important; }
    }
  </style>
</head>
<body>
  <div class="container py-5">
    <h2 class="text-center mb-4">
      <img src="../assets/img/logo.png" width="40" class="me-2">
      Absensi QR Siswa & Guru
    </h2>

    <!-- Webcam Preview -->
    <div class="d-flex justify-content-center mb-3">
      <video id="preview" playsinline></video>
    </div>

    <!-- Input Manual QR Code -->
    <div class="mb-4 text-center">
      <form id="formManual" class="d-inline-block">
        <input type="text" id="manualInput" class="form-control text-center" placeholder="Scan atau ketik kode QR" style="max: width 325px;" autofocus>
        <div class="form-text">Gunakan input manual jika kamera tidak mendeteksi</div>
      </form>
    </div>

    <!-- Hasil Scan -->
    <div id="result" class="my-4 text-center"></div>

    <!-- Riwayat -->
    <h5>Riwayat Presensi Terakhir:</h5>
    <ul id="history" class="list-group"></ul>
  </div>


  <!-- Audio Feedback -->
  <audio id="audio-success" src="../assets/audio/success.mp3"></audio>
  <audio id="audio-duplicate" src="../assets/audio/duplicate.mp3"></audio>
  <audio id="audio-error" src="../assets/audio/error.mp3"></audio>

  <script>
    let lastScan = "";
    let delay = false;

    // Kirim ke server
    function prosesAbsen(kode) {
      if (kode === "" || delay || kode === lastScan) return;
      delay = true;
      lastScan = kode;

      fetch('proses_absen.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'qrcode=' + encodeURIComponent(kode)
      })
      .then(res => res.json())
      .then(res => {
        // Audio
        if (res.status === 'success') {
          document.getElementById('audio-success').play();
        } else if (res.status === 'already') {
          document.getElementById('audio-duplicate').play();
        } else {
          document.getElementById('audio-error').play();
        }

        // Hasil utama
        const html = `
          <div class="card shadow highlight p-3 rounded ${
            res.status === 'success' ? (res.jenis === 'masuk' ? 'border-success' : 'border-primary')
            : res.status === 'already' ? 'border-warning'
            : 'border-danger'
          } ">
            <div class="card-body">
            
            <h4>${res.nama ?? '-'}</h4>
           
            <p>${res.pesan ?? ''}</p>
            <img src="../uploads/${res.foto ?? 'default.jpeg'}" alt="Foto" height="100" class="rounded mt-2">
            
            </div>
          </div>
        `;
        document.getElementById('result').innerHTML = html;


        // Tambah ke riwayat
        const waktu = new Date().toLocaleTimeString();
        const riwayat = `<li class="list-group-item">${waktu} - ${res.nama ?? '-'} (${res.jenis ?? '-'})</li>`;
        document.getElementById('history').insertAdjacentHTML('afterbegin', riwayat);

        // Reset
        setTimeout(() => { delay = false; }, 3000);
      })
      .catch(err => {
        console.error("Fetch error:", err);
        document.getElementById('audio-error').play();
        delay = false;
      });
    }

    // Handle input manual
    document.getElementById('formManual').addEventListener('submit', function(e) {
      e.preventDefault();
      const kode = document.getElementById('manualInput').value.trim();
      prosesAbsen(kode);
      document.getElementById('manualInput').value = '';
    });

    // Jalankan scanner webcam
    const video = document.getElementById('preview');
    navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } })
    .then(stream => {
      video.srcObject = stream;
      video.setAttribute("playsinline", true);
      video.play();
      const canvas = document.createElement("canvas");
      const ctx = canvas.getContext("2d");

      setInterval(() => {
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
          canvas.width = video.videoWidth;
          canvas.height = video.videoHeight;
          ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
          const imageData = canvas.toDataURL("image/png");

          // Gunakan QR library untuk membaca kode (pakai plugin lain jika perlu)
          // Jika menggunakan ZXing atau lainnya, bisa diterapkan di sini
        }
      }, 1000);
    })
    .catch(err => {
      console.error("Camera error:", err);
      alert("Kamera tidak tersedia");
    });
  </script>
</body>
</html>

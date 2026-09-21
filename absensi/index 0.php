<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Absensi QR</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
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
  </style>
</head>
<body>
  <div class="container py-5">
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

    <h5>Riwayat Presensi Terakhir:</h5>
    <ul id="history" class="list-group"></ul>
  </div>

  <!-- Audio Feedback -->
  <audio id="audio-success" src="../assets/audio/success.mp3"></audio>
  <audio id="audio-duplicate" src="../assets/audio/duplicate.mp3"></audio>
  <audio id="audio-error" src="../assets/audio/error.mp3"></audio>

  <!-- Script Scanner -->
<script src="https://unpkg.com/@zxing/library@latest"></script>
<script>
  const codeReader = new ZXing.BrowserQRCodeReader();
  const videoElement = document.getElementById('preview');
  let scannerPaused = false;

  function handleScan(qrcode) {
    if (scannerPaused) return;
    scannerPaused = true;

    document.getElementById('manualInput').value = qrcode;

    fetch("proses_absen.php?qrcode=" + encodeURIComponent(qrcode))
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          document.getElementById("audio-success").play();
          document.getElementById("result").innerHTML = `
            <div class="card shadow border-success highlight">
              <div class="card-body d-flex align-items-center">
                <img src="../uploads/${data.foto}" width="100" class="rounded me-3">
                <div>
                  <h4 class="text-success mb-0">${data.nama}</h4>
                  <p class="mb-0">${data.kelas || data.jabatan}</p>
                  <small>${data.waktu}</small>
                  <div><span class="badge bg-success mt-1">Presensi Tercatat</span></div>
                </div>
              </div>
            </div>
          `;
          document.getElementById("history").insertAdjacentHTML('afterbegin',
            `<li class="list-group-item">${data.nama} (${data.waktu})</li>`);
        } else if (data.status === 'duplikat') {
          document.getElementById("audio-duplicate").play();
          document.getElementById("result").innerHTML = `
            <div class="card shadow border-warning highlight">
              <div class="card-body d-flex align-items-center">
                <img src="../uploads/${data.foto}" width="100" class="rounded me-3">
                <div>
                  <h4 class="text-warning mb-0">${data.nama}</h4>
                  <p class="mb-0">${data.kelas || data.jabatan}</p>
                  <small>${data.waktu}</small>
                  <div><span class="badge bg-warning mt-1">Sudah Absen Hari Ini</span></div>
                </div>
              </div>
            </div>
          `;
        } else {
          document.getElementById("audio-error").play();
          document.getElementById("result").innerHTML = `
            <div class="alert alert-danger">QR tidak ditemukan dalam database.</div>
          `;
        }

        setTimeout(() => {
          scannerPaused = false;
        }, 5000);
      });
  }

  // Aktifkan kamera
  codeReader
    .listVideoInputDevices()
    .then(devices => {
      const selectedDeviceId = devices[0].deviceId;
      return codeReader.decodeFromVideoDevice(selectedDeviceId, videoElement, (result, err) => {
        if (result) {
          handleScan(result.getText());
        }
      });
    })
    .catch(err => console.error('Error webcam:', err));

  // Tangani input manual
  document.getElementById("formManual").addEventListener("submit", function(e) {
    e.preventDefault();
    const kode = document.getElementById("manualInput").value.trim();
    if (kode !== "") {
      handleScan(kode);
    }
  });
</script>


</body>
</html>
